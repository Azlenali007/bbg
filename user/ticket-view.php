<?php
declare(strict_types=1);

$pageTitle = 'View Ticket';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Tickets/TicketManager.php';
require_once BASE_PATH . '/includes/permissions.php';

use App\Tickets\TicketManager;

$ticketId = (int)($_GET['id'] ?? 0);
if ($ticketId <= 0 || !Permissions::userOwnsTicket($ticketId, $userId)) {
    renderErrorPage(404, 'Ticket Not Found', 'The requested support ticket does not exist or you do not have permission to view it.');
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    require_csrf();
    $message = trim($_POST['message'] ?? '');
    if (!empty($message)) {
        TicketManager::addReply($ticketId, $userId, false, $message);
        flash('success', 'Your reply has been posted.');
        header("Location: /user/ticket-view.php?id={$ticketId}");
        exit;
    }
}

// Handle close ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'close') {
    require_csrf();
    TicketManager::closeTicket($ticketId);
    flash('success', 'Ticket closed.');
    header("Location: /user/ticket-view.php?id={$ticketId}");
    exit;
}

$ticket = TicketManager::getTicketDetails($ticketId, $userId);
if (!$ticket) {
    renderErrorPage(404, 'Ticket Not Found', 'Ticket could not be loaded.');
}

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header & Navigation -->
    <div class="flex items-center justify-between">
        <a href="/user/tickets.php" class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-500 hover:text-purple-600 transition-colors">
            <?= heroicon('arrow-left-on-rectangle', 'w-4 h-4') ?>
            <span>Back to Tickets</span>
        </a>
        <div class="flex items-center gap-2">
            <?php if ($ticket['status'] !== 'closed'): ?>
                <form action="/user/ticket-view.php?id=<?= $ticketId ?>" method="POST" onsubmit="return confirm('Are you sure you want to close this ticket?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="close">
                    <button type="submit" class="px-3 py-1.5 rounded-xl border border-zinc-200 text-xs font-semibold text-zinc-600 hover:bg-zinc-100 transition-colors">
                        Close Ticket
                    </button>
                </form>
            <?php endif; ?>
            <span class="text-xs font-mono font-bold text-purple-700 bg-purple-50 border border-purple-200 px-3 py-1 rounded-full">
                Ticket #<?= $ticket['id'] ?>
            </span>
        </div>
    </div>

    <!-- Ticket Summary Card -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-zinc-200/80 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-zinc-100 gap-3">
            <div>
                <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider block">
                    Category: <?= ucfirst($ticket['category']) ?> &bull; Priority: <?= ucfirst($ticket['priority']) ?>
                </span>
                <h2 class="text-lg font-bold text-zinc-900 mt-0.5"><?= e($ticket['subject']) ?></h2>
            </div>
            <div>
                <?php
                $st = $ticket['status'];
                $badge = match($st) {
                    'open' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'answered' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'user_reply' => 'bg-purple-50 text-purple-700 border-purple-200',
                    'closed' => 'bg-zinc-100 text-zinc-600 border-zinc-200',
                    default => 'bg-zinc-100 text-zinc-600 border-zinc-200'
                };
                ?>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border <?= $badge ?>">
                    <?= str_replace('_', ' ', ucfirst($st)) ?>
                </span>
            </div>
        </div>

        <!-- Conversation Stream -->
        <div class="space-y-4 pt-2">
            <?php foreach ($ticket['messages'] as $msg): ?>
                <?php $isAdmin = (bool)$msg['is_admin']; ?>
                <div class="p-5 rounded-2xl border <?= $isAdmin ? 'bg-purple-50/40 border-purple-100 ml-4 sm:ml-8' : 'bg-zinc-50 border-zinc-200/70 mr-4 sm:mr-8' ?>">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold <?= $isAdmin ? 'text-purple-900' : 'text-zinc-900' ?>">
                                <?= e($msg['author_name']) ?>
                            </span>
                            <?php if ($isAdmin): ?>
                                <span class="px-1.5 py-0.2 rounded bg-purple-600 text-white text-[9px] font-black uppercase tracking-wider">
                                    Support Staff
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="text-[10px] text-zinc-400"><?= timeAgo($msg['created_at']) ?></span>
                    </div>
                    <div class="text-xs text-zinc-700 leading-relaxed whitespace-pre-line">
                        <?= e($msg['message']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Reply Form -->
        <?php if ($ticket['status'] !== 'closed'): ?>
            <div class="pt-6 border-t border-zinc-100">
                <form action="/user/ticket-view.php?id=<?= $ticketId ?>" method="POST" class="space-y-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reply">
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700">Add Reply</label>
                    <textarea name="message" rows="3" required placeholder="Type your response here..." 
                              class="w-full px-4 py-3 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 transition-all"></textarea>
                    <div class="text-right">
                        <button type="submit" class="px-5 py-2.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/20 transition-all">
                            Send Reply
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="p-4 bg-zinc-50 rounded-2xl text-center text-xs text-zinc-500">
                This support ticket is closed. If you have an unresolved issue, please open a new ticket.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
