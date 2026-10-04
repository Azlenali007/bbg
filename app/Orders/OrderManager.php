<?php
declare(strict_types=1);

namespace App\Orders;

use App\FlashSales\FlashSaleManager;
use App\Services\ServiceManager;
use App\Wallet\WalletManager;
use Database;
use Logger;
use Providers\ProviderManager;
use Throwable;

require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/logger.php';
require_once BASE_PATH . '/app/Services/ServiceManager.php';
require_once BASE_PATH . '/app/Wallet/WalletManager.php';
require_once BASE_PATH . '/providers/provider-manager.php';
require_once BASE_PATH . '/app/FlashSales/FlashSaleManager.php';

class OrderManager
{
    /**
     * Create and process a new order with server-side validation and financial safety.
     */
    public static function createOrder(int $userId, int $serviceId, string $link, int $quantity): array
    {
        $link = trim($link);
        if (empty($link) || !filter_var($link, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => 'Please provide a valid target URL or link.'];
        }

        $service = ServiceManager::getServiceById($serviceId);
        if (!$service || $service['status'] !== 'active') {
            return ['success' => false, 'message' => 'Selected service is unavailable or inactive.'];
        }

        if ($quantity < (int)$service['min_quantity']) {
            return ['success' => false, 'message' => "Quantity must be at least {$service['min_quantity']}."];
        }

        if ($quantity > (int)$service['max_quantity']) {
            return ['success' => false, 'message' => "Quantity cannot exceed {$service['max_quantity']}."];
        }

        // Server-side calculated rate (apply Flash Sale discount if active)
        $effectiveRate = (float)$service['rate'];
        $activeSale = FlashSaleManager::getActiveSaleForService($serviceId, (int)$service['category_id']);
        if ($activeSale) {
            if (!empty($activeSale['special_rate']) && (float)$activeSale['special_rate'] > 0) {
                $effectiveRate = (float)$activeSale['special_rate'];
            } elseif (!empty($activeSale['discount_percentage']) && (float)$activeSale['discount_percentage'] > 0) {
                $effectiveRate = $effectiveRate * (1 - ((float)$activeSale['discount_percentage'] / 100));
            }
        }

        // Price per 1,000
        $totalCharge = round(($effectiveRate * $quantity) / 1000, 4);
        if ($totalCharge <= 0) {
            return ['success' => false, 'message' => 'Calculated order charge is invalid.'];
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // Debit user wallet atomically
            $debitResult = WalletManager::debit(
                $userId,
                $totalCharge,
                "Order for Service #{$serviceId} ({$service['name']}) - Qty: {$quantity}",
                'order',
                'pending'
            );

            if (!$debitResult['success']) {
                $db->rollBack();
                return $debitResult;
            }

            // Insert order into orders table
            $stmt = $db->prepare("
                INSERT INTO orders (
                    user_id, service_id, provider_id, link, quantity, charge,
                    start_counter, remains, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 0, ?, 'pending', NOW())
            ");
            $providerId = !empty($service['provider_id']) ? (int)$service['provider_id'] : null;
            $stmt->execute([
                $userId,
                $serviceId,
                $providerId,
                $link,
                $quantity,
                $totalCharge,
                $quantity
            ]);
            $orderId = (int)$db->lastInsertId();

            // Insert order log
            $logStmt = $db->prepare("INSERT INTO order_logs (order_id, action, details, created_at) VALUES (?, 'created', 'Order placed by user', NOW())");
            $logStmt->execute([$orderId]);

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Order placement transaction error: " . $e->getMessage(), ['user_id' => $userId, 'service_id' => $serviceId]);
            return ['success' => false, 'message' => 'Failed to place order. No funds were deducted.'];
        }

        // Now dispatch to provider API if service has configured provider
        if ($providerId && !empty($service['provider_service_id'])) {
            self::dispatchToProvider($orderId, $service, $link, $quantity);
        }

        Logger::info("Order #{$orderId} placed successfully", ['user_id' => $userId, 'charge' => $totalCharge]);

        return [
            'success' => true,
            'order_id' => $orderId,
            'charge' => $totalCharge,
            'balance' => $debitResult['balance_after'],
            'message' => "Order #{$orderId} placed successfully."
        ];
    }

    private static function dispatchToProvider(int $orderId, array $service, string $link, int $quantity): void
    {
        $providerId = (int)$service['provider_id'];
        $adapter = ProviderManager::getAdapter($providerId);
        if (!$adapter) {
            return;
        }

        try {
            $resp = $adapter->addOrder($service['provider_service_id'], $link, $quantity);
            $db = Database::getConnection();

            if (isset($resp['order']) && !empty($resp['order'])) {
                $providerOrderId = (string)$resp['order'];
                $update = $db->prepare("UPDATE orders SET provider_order_id = ?, status = 'processing' WHERE id = ?");
                $update->execute([$providerOrderId, $orderId]);

                $log = $db->prepare("INSERT INTO order_logs (order_id, action, details, created_at) VALUES (?, 'provider_sent', ?, NOW())");
                $log->execute([$orderId, "Sent to provider. Provider Order ID: {$providerOrderId}"]);
            } elseif (isset($resp['error'])) {
                $errorMsg = is_string($resp['error']) ? $resp['error'] : json_encode($resp['error']);
                $update = $db->prepare("UPDATE orders SET error_message = ? WHERE id = ?");
                $update->execute([$errorMsg, $orderId]);

                $log = $db->prepare("INSERT INTO order_logs (order_id, action, details, created_at) VALUES (?, 'provider_error', ?, NOW())");
                $log->execute([$orderId, "Provider error: {$errorMsg}"]);
                Logger::error("Provider dispatch error on Order #{$orderId}: {$errorMsg}");
            }
        } catch (Throwable $e) {
            Logger::error("Exception dispatching Order #{$orderId} to provider: " . $e->getMessage());
        }
    }

    public static function getUserOrders(
        int $userId,
        ?string $status = null,
        ?string $search = null,
        int $page = 1,
        int $perPage = 20
    ): array {
        $db = Database::getConnection();
        $where = ["o.user_id = ?"];
        $params = [$userId];

        if ($status && $status !== 'all') {
            $where[] = "o.status = ?";
            $params[] = $status;
        }

        if ($search && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $where[] = "(o.id = ? OR s.name LIKE ? OR o.link LIKE ?)";
            $params[] = is_numeric($search) ? (int)$search : 0;
            $params[] = $term;
            $params[] = $term;
        }

        $whereClause = implode(' AND ', $where);

        // Count total
        $countSql = "SELECT COUNT(*) FROM orders o JOIN services s ON o.service_id = s.id WHERE {$whereClause}";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $totalOrders = (int)$countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "
            SELECT o.*, s.name as service_name, c.name as category_name
            FROM orders o
            JOIN services s ON o.service_id = s.id
            JOIN categories c ON s.category_id = c.id
            WHERE {$whereClause}
            ORDER BY o.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        return [
            'orders' => $orders,
            'total' => $totalOrders,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($totalOrders / $perPage)
        ];
    }

    public static function getOrderById(int $orderId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT o.*, s.name as service_name, s.type as service_type, c.name as category_name,
                   u.username, u.email, p.name as provider_name
            FROM orders o
            JOIN services s ON o.service_id = s.id
            JOIN categories c ON s.category_id = c.id
            JOIN users u ON o.user_id = u.id
            LEFT JOIN providers p ON o.provider_id = p.id
            WHERE o.id = ?
            LIMIT 1
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    public static function getUserStats(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END) as refunded,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                COALESCE(SUM(charge), 0) as total_spent
            FROM orders
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: [
            'total_orders' => 0,
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'partial' => 0,
            'cancelled' => 0,
            'refunded' => 0,
            'failed' => 0,
            'total_spent' => 0.0
        ];
    }
}
