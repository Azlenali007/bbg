<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/app/Orders/OrderManager.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

$user = Auth::user();
$userId = (int)$user['id'];

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId > 0) {
    $order = \App\Orders\OrderManager::getOrderById($orderId);
    if (!$order || (int)$order['user_id'] !== $userId) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }
    echo json_encode(['status' => 'success', 'data' => $order]);
    exit;
}

$orders = \App\Orders\OrderManager::getUserOrders($userId);
echo json_encode(['status' => 'success', 'data' => $orders]);
exit;
