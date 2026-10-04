<?php
declare(strict_types=1);

namespace App\Wallet;

use Database;
use Logger;
use PDO;
use Throwable;

require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/logger.php';

class WalletManager
{
    public static function getBalance(int $userId): float
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (float)$val : 0.0;
    }

    /**
     * Debit user wallet atomically within a database transaction.
     */
    public static function debit(
        int $userId,
        float $amount,
        string $description,
        string $referenceType,
        string $referenceId
    ): array {
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid debit amount.'];
        }

        $db = Database::getConnection();
        $hasOuterTx = $db->inTransaction();
        if (!$hasOuterTx) {
            $db->beginTransaction();
        }

        try {
            // Lock wallet row FOR UPDATE
            $stmt = $db->prepare("SELECT balance, total_spent FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch();

            if (!$wallet) {
                // Initialize wallet if missing
                $initStmt = $db->prepare("INSERT INTO wallets (user_id, balance, total_deposited, total_spent, updated_at) VALUES (?, 0, 0, 0, NOW())");
                $initStmt->execute([$userId]);
                $wallet = ['balance' => 0.0000, 'total_spent' => 0.0000];
            }

            $currentBalance = (float)$wallet['balance'];
            if ($currentBalance < $amount) {
                if (!$hasOuterTx) {
                    $db->rollBack();
                }
                return [
                    'success' => false,
                    'message' => 'Insufficient funds. Please add funds to your wallet.',
                    'current_balance' => $currentBalance
                ];
            }

            $newBalance = $currentBalance - $amount;
            $newTotalSpent = (float)$wallet['total_spent'] + $amount;

            // Update wallet
            $updateWallet = $db->prepare("
                UPDATE wallets 
                SET balance = ?, total_spent = ?, updated_at = NOW() 
                WHERE user_id = ?
            ");
            $updateWallet->execute([$newBalance, $newTotalSpent, $userId]);

            // Sync users table balance & spent
            $updateUser = $db->prepare("
                UPDATE users 
                SET balance = ?, spent = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $updateUser->execute([$newBalance, $newTotalSpent, $userId]);

            // Insert transaction record
            $txStmt = $db->prepare("
                INSERT INTO transactions (user_id, type, amount, fee, balance_before, balance_after, reference_type, reference_id, description, status, created_at)
                VALUES (?, 'order', ?, 0.0000, ?, ?, ?, ?, ?, 'completed', NOW())
            ");
            $txStmt->execute([$userId, $amount, $currentBalance, $newBalance, $referenceType, $referenceId, $description]);
            $txId = (int)$db->lastInsertId();

            if (!$hasOuterTx) {
                $db->commit();
            }

            Logger::info("Wallet debited", [
                'user_id' => $userId,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'tx_id' => $txId
            ]);

            return [
                'success' => true,
                'balance_before' => $currentBalance,
                'balance_after' => $newBalance,
                'transaction_id' => $txId
            ];
        } catch (Throwable $e) {
            if (!$hasOuterTx && $db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Wallet debit failed: " . $e->getMessage(), ['user_id' => $userId, 'amount' => $amount]);
            return ['success' => false, 'message' => 'Wallet transaction failed.'];
        }
    }

    /**
     * Credit user wallet atomically.
     */
    public static function credit(
        int $userId,
        float $amount,
        string $description,
        string $referenceType,
        string $referenceId,
        string $type = 'deposit'
    ): array {
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid credit amount.'];
        }

        $db = Database::getConnection();
        $hasOuterTx = $db->inTransaction();
        if (!$hasOuterTx) {
            $db->beginTransaction();
        }

        try {
            // Lock wallet row FOR UPDATE
            $stmt = $db->prepare("SELECT balance, total_deposited FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch();

            if (!$wallet) {
                $initStmt = $db->prepare("INSERT INTO wallets (user_id, balance, total_deposited, total_spent, updated_at) VALUES (?, 0, 0, 0, NOW())");
                $initStmt->execute([$userId]);
                $wallet = ['balance' => 0.0000, 'total_deposited' => 0.0000];
            }

            $currentBalance = (float)$wallet['balance'];
            $newBalance = $currentBalance + $amount;
            $newDeposited = (float)$wallet['total_deposited'] + ($type === 'deposit' ? $amount : 0.0);

            // Update wallet
            $updateWallet = $db->prepare("
                UPDATE wallets 
                SET balance = ?, total_deposited = ?, updated_at = NOW() 
                WHERE user_id = ?
            ");
            $updateWallet->execute([$newBalance, $newDeposited, $userId]);

            // Sync users table balance
            $updateUser = $db->prepare("
                UPDATE users 
                SET balance = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $updateUser->execute([$newBalance, $userId]);

            // Insert transaction record
            $txStmt = $db->prepare("
                INSERT INTO transactions (user_id, type, amount, fee, balance_before, balance_after, reference_type, reference_id, description, status, created_at)
                VALUES (?, ?, ?, 0.0000, ?, ?, ?, ?, ?, 'completed', NOW())
            ");
            $txStmt->execute([$userId, $type, $amount, $currentBalance, $newBalance, $referenceType, $referenceId, $description]);
            $txId = (int)$db->lastInsertId();

            if (!$hasOuterTx) {
                $db->commit();
            }

            Logger::info("Wallet credited", [
                'user_id' => $userId,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'tx_id' => $txId
            ]);

            return [
                'success' => true,
                'balance_before' => $currentBalance,
                'balance_after' => $newBalance,
                'transaction_id' => $txId
            ];
        } catch (Throwable $e) {
            if (!$hasOuterTx && $db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Wallet credit failed: " . $e->getMessage(), ['user_id' => $userId, 'amount' => $amount]);
            return ['success' => false, 'message' => 'Wallet credit failed.'];
        }
    }
}
