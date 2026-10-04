<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/csrf.php';
require_once BASE_PATH . '/app/Notifications/NotificationManager.php';

use App\Notifications\NotificationManager;

header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

$user = Auth::user();
$userId = (int)$user['id'];

$action = $_POST['action'] ?? '';
$token = $_POST['csrf_token'] ?? null;

if (!verify_csrf($token)) {
    http_response_code(419);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

if ($action === 'mark_read') {
    $id = (int)($_POST['id'] ?? 0);
    $ok = NotificationManager::markAsRead($id, $userId);
    echo json_encode(['success' => $ok]);
    exit;
}

if ($action === 'mark_all_read') {
    $ok = NotificationManager::markAllAsRead($userId);
    echo json_encode(['success' => $ok]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
exit;
