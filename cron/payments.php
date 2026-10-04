<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/logger.php';

$cronKey = $_GET['key'] ?? null;
$expectedKey = getenv('CRON_KEY') ?: 'smm_cron_secret';
if (php_sapi_name() !== 'cli' && $cronKey !== $expectedKey) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

echo "[" . date('Y-m-d H:i:s') . "] Starting Payments Cleanup & Reconciliation Cron...\n";

$db = getDB();

// Expire pending payments older than 24 hours
$stmt = $db->prepare("
    UPDATE payments 
    SET status = 'canceled', updated_at = NOW() 
    WHERE status = 'pending' 
      AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$stmt->execute();
$expired = $stmt->rowCount();

echo "Expired {$expired} abandoned pending payments.\n";
echo "[" . date('Y-m-d H:i:s') . "] Finished Payments Cron.\n";
