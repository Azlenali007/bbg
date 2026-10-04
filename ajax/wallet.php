<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/app/Wallet/WalletManager.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

$user = Auth::user();
$balance = \App\Wallet\WalletManager::getBalance((int)$user['id']);

echo json_encode([
    'success' => true,
    'balance' => $balance,
    'formatted' => formatCurrency($balance)
]);
exit;
