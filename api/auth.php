<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$isAuth = Auth::check();
$user = $isAuth ? Auth::user() : null;

echo json_encode([
    'authenticated' => $isAuth,
    'user' => $user ? [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'balance' => $user['wallet_balance'] ?? $user['balance'],
        'role' => $user['role']
    ] : null
]);
exit;
