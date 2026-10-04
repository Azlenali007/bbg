<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/app/Orders/OrderManager.php';

use App\Orders\OrderManager;

header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

$user = Auth::user();
$userId = (int)$user['id'];

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'calculate') {
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $service = \App\Services\ServiceManager::getServiceById($serviceId);
    if (!$service) {
        echo json_encode(['error' => 'Service not found']);
        exit;
    }
    $rate = (float)$service['rate'];
    $price = round(($rate * $quantity) / 1000, 4);
    echo json_encode(['success' => true, 'price' => number_format($price, 4, '.', '')]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
exit;
