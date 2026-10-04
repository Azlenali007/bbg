<?php
declare(strict_types=1);

$pageTitle = 'Ticket Details';
require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Tickets/TicketManager.php';
require_once BASE_PATH . '/app/Notifications/NotificationManager.php';

use App\Tickets\TicketManager;
use App\Notifications\NotificationManager;

$db = getDB();
$ticketId = (int)($_GET['id'] ?? 0);

if (!$ticketId) {
    flash('danger', 'Invalid ticket ID.');
    header('Location: /admin/tickets.php');
    exit;
}

$stmt = $db->prepare("
    SELECT t.*, u.username as user_name, u.email as user_email
    FROM tickets t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    flash('danger', 'Ticket not found.');
    header('Location: /admin/tickets.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'reply') {
        $message = trim($_POST['message'] ?? '');
        $newStatus = $_POST['status'] ?? 'answered';

        if (empty($message)) {
            $error = 'Reply message cannot be empty.';
        } else {
            try {
                // Add message
                $msgStmt = $db->prepare("
                    INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message, created_at)
                    VALUES (?, ?, 1, ?, NOW())
                ");
                $msgStmt->execute([$ticketId, $adminId, $message]);

                // Update ticket
                $upStmt = $db->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?");
                $upStmt->execute([$newStatus, $ticketId]);

                // Send notification to customer
                NotificationManager::create(
                    (int)$ticket['user_id'],
                    'Support Replied: #' . $ticketId,
                    'Support staff has responded to your ticket: "' . substr($ticket['subject'], 0, 50) . '"',
                    'info'
                );

                logAudit($adminId, true, 'reply_ticket', "Replied to ticket #{$ticketId}");
                flash('success', 'Reply posted successfully.');
                header("Location: /admin/ticket-view.php?id={$ticketId}");
                exit;
            } catch (Throwable $e) {
                $error = 'Error posting reply: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? $ticket['status'];
        $priority = $_POST['priority'] ?? $ticket['priority'];

        $upStmt = $db->prepare("UPDATE tickets SET status = ?, priority = ?, updated_at = NOW() WHERE id = ?");
        $upStmt->execute([$newStatus, $priority, $ticketId]);

        logAudit($adminId, true, 'update_ticket', "Updated ticket #{$ticketId} status to {$newStatus}");
        flash('success', 'Ticket updated successfully.');
        header("Location: /admin/ticket-view.php?id={$ticketId}");
        exit;
    }
}

// Fetch messages
$msgQuery = $db->prepare("
    SELECT m.*, u.username as author_name, a.username as admin_name
    FROM ticket_messages m
    LEFT JOIN users u ON (!m.is_admin AND m.user_id = u.id)
    LEFT JOIN admins a ON (m.is_admin AND m.user_id = a.id)
    WHERE m.ticket_id = ?
    ORDER BY m.id ASC
");
$msgQuery->execute([$ticketId]);
$messages = $msgQuery->fetchAll();
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-5xl w-full mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="/admin/tickets.php" class="text-xs font-semibold text-purple-600 hover:text-purple-700 flex items-center gap-1">
                    <?= heroicon('arrow-left', 'w-3.5 h-3.5') ?> Back to Tickets
                </a>
            </div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight mt-1">Ticket #<?= $ticket['id'] ?>: <?= e($ticket['subject']) ?></h1>
            <p class="text-xs text-zinc-500">Opened by <a href="/admin/user-view.php?id=<?= $ticket['user_id'] ?>" class="text-purple-600 font-bold"><?= e($ticket['user_name']) ?></a> on <?= date('M d, Y H:i', strtotime($ticket['created_at'])) ?></p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 text-xs font-bold rounded-full <?= $ticket['status'] === 'open' ? 'bg-blue-100 text-blue-800' : 'bg-zinc-100 text-zinc-700' ?>">
                <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
            </span>
            <span class="px-3 py-1 text-xs font-bold rounded-full bg-slate-100 text-slate-700 uppercase">
                <?= e($ticket['priority']) ?>
            </span>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Status & Priority Controls -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-4 shadow-sm">
        <form action="" method="POST" class="flex flex-wrap items-center justify-between gap-3 text-xs">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_status">
            
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-zinc-500">Status:</span>
                    <select name="status" class="px-3 py-1.5 rounded-xl border border-zinc-200 font-semibold focus:outline-none focus:border-purple-600">
                        <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                        <option value="user_reply" <?= $ticket['status'] === 'user_reply' ? 'selected' : '' ?>>User Reply</option>
                        <option value="answered" <?= $ticket['status'] === 'answered' ? 'selected' : '' ?>>Answered</option>
                        <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-bold text-zinc-500">Priority:</span>
                    <select name="priority" class="px-3 py-1.5 rounded-xl border border-zinc-200 font-semibold focus:outline-none focus:border-purple-600">
                        <option value="low" <?= $ticket['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                        <option value="medium" <?= $ticket['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="high" <?= $ticket['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                        <option value="urgent" <?= $ticket['priority'] === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="px-4 py-1.5 rounded-xl bg-zinc-900 text-white font-bold hover:bg-zinc-800 transition-all">
                Update Status
            </button>
        </form>
    </div>

    <!-- Messages Thread -->
    <div class="space-y-4">
        <?php foreach ($messages as $msg): 
            $isAdmin = (bool)$msg['is_admin'];
        ?>
            <div class="bg-white border <?= $isAdmin ? 'border-purple-200 shadow-purple-500/5' : 'border-zinc-200/80' ?> rounded-3xl p-6 shadow-sm">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 mb-3 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="font-bold <?= $isAdmin ? 'text-purple-600' : 'text-zinc-900' ?>">
                            <?= $isAdmin ? ('Support Staff (' . e($msg['admin_name'] ?? 'Admin') . ')') : e($msg['author_name'] ?? 'Customer') ?>
                        </span>
                        <?php if ($isAdmin): ?>
                            <span class="px-2 py-0.5 rounded-md bg-purple-100 text-purple-700 text-[10px] font-black uppercase">Staff</span>
                        <?php endif; ?>
                    </div>
                    <span class="text-zinc-400"><?= date('M d, Y H:i:s', strtotime($msg['created_at'])) ?></span>
                </div>
                <div class="text-xs text-zinc-700 leading-relaxed whitespace-pre-wrap">
                    <?= nl2br(e($msg['message'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Reply Box -->
    <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-bold text-zinc-900 flex items-center gap-2">
            <?= heroicon('chat-bubble-left-right', 'w-5 h-5 text-purple-600') ?> Post Official Staff Reply
        </h2>
        <form action="" method="POST" class="space-y-4 text-xs">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reply">

            <div>
                <textarea name="message" rows="5" required placeholder="Type your response to the customer here..." 
                          class="w-full p-4 rounded-2xl border border-zinc-200 focus:outline-none focus:border-purple-600 text-xs text-zinc-900 leading-relaxed"></textarea>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-zinc-500">Set status to:</span>
                    <select name="status" class="px-3 py-1.5 rounded-xl border border-zinc-200 font-semibold focus:outline-none focus:border-purple-600">
                        <option value="answered" selected>Answered</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold shadow-md shadow-purple-600/20 transition-all">
                    Send Reply to Customer
                </button>
            </div>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
