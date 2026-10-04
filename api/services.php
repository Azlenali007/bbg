<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/app/Services/ServiceManager.php';

header('Content-Type: application/json; charset=utf-8');

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = trim($_GET['search'] ?? '');

$services = \App\Services\ServiceManager::getActiveServices($categoryId, $search ?: null);
echo json_encode([
    'status' => 'success',
    'count' => count($services),
    'data' => $services
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;
