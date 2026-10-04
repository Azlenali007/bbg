<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/csrf.php';
require_once BASE_PATH . '/app/Tickets/TicketManager.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

$user = Auth::user();
$userId = (int)$user['id'];

if (!verify_csrf()) {
    http_response_code(419);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$ticketId = (int)($_POST['ticket_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($ticketId <= 0 || empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

$ok = \App\Tickets\TicketManager::addReply($ticketId, $userId, false, $message);
echo json_encode(['success' => $ok]);
exit;
