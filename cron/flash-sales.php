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

echo "[" . date('Y-m-d H:i:s') . "] Starting Flash Sales Expiration Cron...\n";

$db = getDB();

$stmt = $db->prepare("
    UPDATE flash_sales 
    SET is_active = 0, updated_at = NOW() 
    WHERE is_active = 1 
      AND end_time < NOW()
");
$stmt->execute();
$ended = $stmt->rowCount();

echo "Deactivated {$ended} expired flash sales.\n";
echo "[" . date('Y-m-d H:i:s') . "] Finished Flash Sales Cron.\n";
