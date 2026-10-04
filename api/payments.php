<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/logger.php';
require_once BASE_PATH . '/payments/payment-manager.php';

use Payments\PaymentManager;

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true) ?: $_POST;

$gatewayCode = trim($_GET['gateway'] ?? '');
if (empty($gatewayCode)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing gateway identifier']);
    exit;
}

Logger::info("Payment webhook received for gateway: {$gatewayCode}", ['payload' => substr($rawPayload, 0, 500)]);

$transactionRef = $data['transaction_ref'] ?? ($data['reference'] ?? ($data['custom'] ?? null));
$gatewayTxId = (string)($data['id'] ?? ($data['txn_id'] ?? bin2hex(random_bytes(8))));

if (empty($transactionRef)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing transaction reference']);
    exit;
}

$res = PaymentManager::completePayment((string)$transactionRef, $gatewayTxId, $data);

if ($res['success']) {
    echo json_encode(['status' => 'success', 'message' => $res['message'] ?? 'Payment processed']);
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $res['message']]);
}
exit;
