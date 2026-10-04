<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/admin-auth.php';
require_once BASE_PATH . '/includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if (!AdminAuth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!verify_csrf()) {
    http_response_code(419);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($action === 'toggle' && $id > 0) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE flash_sales SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = ?");
    $ok = $stmt->execute([$id]);
    echo json_encode(['success' => $ok]);
    exit;
}

if ($action === 'delete' && $id > 0) {
    $ok = \App\FlashSales\FlashSaleManager::deleteSale($id);
    echo json_encode(['success' => $ok]);
    exit;
}

echo json_encode(['error' => 'Invalid request']);
exit;
