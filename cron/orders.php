<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/logger.php';
require_once BASE_PATH . '/providers/provider-manager.php';
require_once BASE_PATH . '/app/Wallet/WalletManager.php';

use App\Wallet\WalletManager;

// CLI or Cron Secret check
$cronKey = $_GET['key'] ?? null;
$expectedKey = getenv('CRON_KEY') ?: 'smm_cron_secret';
if (php_sapi_name() !== 'cli' && $cronKey !== $expectedKey) {
    http_response_code(403);
    echo "Forbidden: Invalid cron key\n";
    exit;
}

echo "[" . date('Y-m-d H:i:s') . "] Starting Orders Status Cron Job...\n";

$db = getDB();

// Fetch open orders with provider ID
$stmt = $db->query("
    SELECT o.*, p.id as p_id
    FROM orders o
    JOIN providers p ON o.provider_id = p.id
    WHERE o.status IN ('pending', 'processing', 'inprogress')
      AND o.provider_order_id IS NOT NULL
    LIMIT 100
");
$orders = $stmt->fetchAll();

$processed = 0;
$updated = 0;

foreach ($orders as $order) {
    $processed++;
    try {
        $provider = ProviderManager::getProviderById((int)$order['provider_id']);
        if (!$provider) {
            continue;
        }

        $adapter = ProviderManager::getAdapter($provider);
        $statusResp = $adapter->status((string)$order['provider_order_id']);

        if (!$statusResp || isset($statusResp['error'])) {
            Logger::warning("Cron order #{$order['id']} status check error", ['resp' => $statusResp]);
            continue;
        }

        $pStatus = strtolower(trim((string)($statusResp['status'] ?? '')));
        $remains = isset($statusResp['remains']) ? (int)$statusResp['remains'] : (int)$order['remains'];
        $startCount = isset($statusResp['start_count']) ? (int)$statusResp['start_count'] : (int)$order['start_counter'];

        // Map provider status to local status
        $newStatus = match ($pStatus) {
            'completed' => 'completed',
            'processing' => 'processing',
            'in progress', 'inprogress' => 'inprogress',
            'partial' => 'partial',
            'canceled', 'cancelled' => 'canceled',
            'refunded' => 'refunded',
            default => $order['status']
        };

        if ($newStatus !== $order['status'] || $remains !== (int)$order['remains']) {
            // Handle partial refund
            if ($newStatus === 'partial' && $remains > 0 && (int)$order['quantity'] > 0) {
                $unitRate = (float)$order['charge'] / (int)$order['quantity'];
                $refundAmount = round($unitRate * $remains, 4);
                if ($refundAmount > 0) {
                    WalletManager::refundOrder((int)$order['user_id'], (int)$order['id'], $refundAmount, "Partial order refund for {$remains} undelivered units");
                }
            } elseif (in_array($newStatus, ['canceled', 'refunded'], true)) {
                WalletManager::refundOrder((int)$order['user_id'], (int)$order['id'], (float)$order['charge'], "Full order refund: Order {$newStatus}");
            }

            $up = $db->prepare("
                UPDATE orders 
                SET status = ?, remains = ?, start_counter = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $up->execute([$newStatus, $remains, $startCount, $order['id']]);
            $updated++;
            echo "Order #{$order['id']} updated to {$newStatus} (remains: {$remains})\n";
        }
    } catch (Throwable $e) {
        Logger::error("Cron Order #{$order['id']} failed: " . $e->getMessage());
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Finished. Processed: {$processed}, Updated: {$updated}.\n";
