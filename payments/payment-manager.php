<?php
declare(strict_types=1);

namespace Payments;

use App\Wallet\WalletManager;
use Database;
use Logger;
use Throwable;

require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/logger.php';
require_once BASE_PATH . '/app/Wallet/WalletManager.php';

class PaymentManager
{
    public static function getActiveGateways(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM payment_gateways WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll();
    }

    public static function getGatewayByCode(string $code): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM payment_gateways WHERE code = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$code]);
        $gw = $stmt->fetch();
        return $gw ?: null;
    }

    public static function createPayment(int $userId, int $gatewayId, float $amount): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM payment_gateways WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$gatewayId]);
        $gateway = $stmt->fetch();

        if (!$gateway) {
            return ['success' => false, 'message' => 'Payment gateway is not available.'];
        }

        if ($amount < (float)$gateway['min_amount']) {
            return ['success' => false, 'message' => "Minimum deposit amount is " . formatCurrency((float)$gateway['min_amount']) . "."];
        }

        if ($amount > (float)$gateway['max_amount']) {
            return ['success' => false, 'message' => "Maximum deposit amount is " . formatCurrency((float)$gateway['max_amount']) . "."];
        }

        $feePercent = (float)$gateway['fee_percentage'];
        $feeFixed = (float)$gateway['fee_fixed'];
        $fee = round(($amount * ($feePercent / 100)) + $feeFixed, 2);
        $netAmount = $amount - $fee;

        $transactionRef = 'DEP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(6)));

        try {
            $insert = $db->prepare("
                INSERT INTO payments (
                    user_id, gateway_id, transaction_ref, amount, fee,
                    net_amount, currency, status, ip_address, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
            ");
            $insert->execute([
                $userId,
                $gatewayId,
                $transactionRef,
                $amount,
                $fee,
                $netAmount,
                $gateway['currency'] ?? 'USD',
                getClientIP()
            ]);
            $paymentId = (int)$db->lastInsertId();

            return [
                'success' => true,
                'payment_id' => $paymentId,
                'transaction_ref' => $transactionRef,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'gateway' => $gateway
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to create payment record: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to initialize deposit transaction.'];
        }
    }

    public static function completePayment(string $transactionRef, string $gatewayTxId, array $payload = []): array
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // Lock payment record FOR UPDATE to prevent race condition or duplicate credit
            $stmt = $db->prepare("
                SELECT p.*, g.name as gateway_name 
                FROM payments p
                JOIN payment_gateways g ON p.gateway_id = g.id
                WHERE p.transaction_ref = ? 
                FOR UPDATE
            ");
            $stmt->execute([$transactionRef]);
            $payment = $stmt->fetch();

            if (!$payment) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Payment reference not found.'];
            }

            if ($payment['status'] === 'completed') {
                $db->rollBack();
                return ['success' => true, 'message' => 'Payment has already been processed.'];
            }

            if ($payment['status'] !== 'pending') {
                $db->rollBack();
                return ['success' => false, 'message' => "Payment cannot be processed with status: {$payment['status']}"];
            }

            // Update payment to completed
            $update = $db->prepare("
                UPDATE payments 
                SET status = 'completed', 
                    payment_data = ?, 
                    completed_at = NOW() 
                WHERE id = ?
            ");
            $update->execute([json_encode($payload), $payment['id']]);

            // Credit user wallet atomically
            $creditResult = WalletManager::credit(
                (int)$payment['user_id'],
                (float)$payment['net_amount'],
                "Deposit via {$payment['gateway_name']} (Ref: {$transactionRef})",
                'payment',
                (string)$payment['id'],
                'deposit'
            );

            if (!$creditResult['success']) {
                $db->rollBack();
                return $creditResult;
            }

            $db->commit();
            Logger::info("Payment completed and wallet credited", [
                'payment_id' => $payment['id'],
                'user_id' => $payment['user_id'],
                'net_amount' => $payment['net_amount']
            ]);

            return ['success' => true, 'payment' => $payment];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Complete payment failed: " . $e->getMessage(), ['ref' => $transactionRef]);
            return ['success' => false, 'message' => 'Transaction error while completing payment.'];
        }
    }
}
