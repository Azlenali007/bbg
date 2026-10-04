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

echo "[" . date('Y-m-d H:i:s') . "] Starting Notifications Cleanup Cron...\n";

$db = getDB();

// Delete read notifications older than 30 days
$stmt = $db->prepare("
    DELETE FROM notifications 
    WHERE is_read = 1 
      AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute();
$cleaned = $stmt->rowCount();

echo "Cleaned {$cleaned} old read notifications.\n";
echo "[" . date('Y-m-d H:i:s') . "] Finished Notifications Cron.\n";
