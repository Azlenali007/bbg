<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/logger.php';
require_once BASE_PATH . '/providers/provider-manager.php';

$cronKey = $_GET['key'] ?? null;
$expectedKey = getenv('CRON_KEY') ?: 'smm_cron_secret';
if (php_sapi_name() !== 'cli' && $cronKey !== $expectedKey) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

echo "[" . date('Y-m-d H:i:s') . "] Starting Provider Sync Cron Job...\n";

$db = getDB();
$providers = ProviderManager::getAllProviders();

foreach ($providers as $provider) {
    if ($provider['status'] !== 'active') {
        continue;
    }

    try {
        $adapter = ProviderManager::getAdapter($provider);
        $balance = $adapter->balance();

        if (is_array($balance) && isset($balance['balance'])) {
            $numBalance = (float)$balance['balance'];
            $curr = (string)($balance['currency'] ?? 'USD');
            $up = $db->prepare("UPDATE providers SET balance = ?, currency = ?, updated_at = NOW() WHERE id = ?");
            $up->execute([$numBalance, $curr, $provider['id']]);

            echo "Provider #{$provider['id']} ({$provider['name']}) Balance: {$curr} {$numBalance}\n";

            if ($numBalance < 10.0) {
                Logger::warning("Low provider balance alert: {$provider['name']} has only {$curr} {$numBalance} remaining.");
            }
        }
    } catch (Throwable $e) {
        Logger::error("Provider #{$provider['id']} sync error: " . $e->getMessage());
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Finished Provider Sync.\n";
