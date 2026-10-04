<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/security.php';
require_once BASE_PATH . '/app/Services/ServiceManager.php';
require_once BASE_PATH . '/app/Orders/OrderManager.php';
require_once BASE_PATH . '/app/Wallet/WalletManager.php';

use App\Services\ServiceManager;
use App\Orders\OrderManager;
use App\Wallet\WalletManager;

header('Content-Type: application/json; charset=utf-8');

// Rate limiting by IP
if (!checkRateLimit('user_api', 120, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded. Maximum 120 requests per minute.'], JSON_PRETTY_PRINT);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed. Use HTTP POST.'], JSON_PRETTY_PRINT);
    exit;
}

$key = trim($_POST['key'] ?? '');
$action = trim($_POST['action'] ?? '');

if (empty($key)) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing API Key.'], JSON_PRETTY_PRINT);
    exit;
}

// Authenticate user by API key
$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE api_key = ? AND status = 'active' LIMIT 1");
$stmt->execute([$key]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or inactive API Key.'], JSON_PRETTY_PRINT);
    exit;
}

$userId = (int)$user['id'];

// Process API Action
switch ($action) {
    case 'services':
        $rawServices = ServiceManager::getActiveServices();
        $formatted = [];
        foreach ($rawServices as $s) {
            $formatted[] = [
                'service' => (int)$s['id'],
                'name' => $s['name'],
                'type' => $s['type'] ?? 'Default',
                'category' => $s['category_name'],
                'rate' => (float)$s['rate'],
                'min' => (int)$s['min_quantity'],
                'max' => (int)$s['max_quantity'],
                'refill' => (bool)$s['refill_supported'],
                'cancel' => (bool)$s['cancel_supported']
            ];
        }
        echo json_encode($formatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;

    case 'balance':
        $balance = WalletManager::getBalance($userId);
        echo json_encode([
            'balance' => number_format($balance, 4, '.', ''),
            'currency' => CURRENCY
        ], JSON_PRETTY_PRINT);
        exit;

    case 'add':
        $serviceId = (int)($_POST['service'] ?? 0);
        $link = trim($_POST['link'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);

        if ($serviceId <= 0 || empty($link) || $quantity <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing or invalid parameters: service, link, quantity.'], JSON_PRETTY_PRINT);
            exit;
        }

        $res = OrderManager::createOrder($userId, $serviceId, $link, $quantity);
        if ($res['success']) {
            echo json_encode(['order' => $res['order_id']], JSON_PRETTY_PRINT);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $res['message']], JSON_PRETTY_PRINT);
        }
        exit;

    case 'status':
        $orderId = (int)($_POST['order'] ?? 0);
        if ($orderId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing order ID parameter.'], JSON_PRETTY_PRINT);
            exit;
        }

        $order = OrderManager::getOrderById($orderId);
        if (!$order || (int)$order['user_id'] !== $userId) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found.'], JSON_PRETTY_PRINT);
            exit;
        }

        echo json_encode([
            'charge' => number_format((float)$order['charge'], 4, '.', ''),
            'start_count' => (int)$order['start_counter'],
            'status' => ucfirst($order['status']),
            'remains' => (int)$order['remains'],
            'currency' => CURRENCY
        ], JSON_PRETTY_PRINT);
        exit;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Incorrect request. Supported actions: services, balance, add, status.'], JSON_PRETTY_PRINT);
        exit;
}
