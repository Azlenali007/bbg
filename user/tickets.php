<?php
declare(strict_types=1);

$pageTitle = 'Support Tickets';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Tickets/TicketManager.php';

use App\Tickets\TicketManager;

$createError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? 'orders');
    $priority = trim($_POST['priority'] ?? 'medium');
    $message = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($message)) {
        $createError = 'Subject and message are required.';
    } else {
        $res = TicketManager::createTicket($userId, $subject, $category, $priority, $message);
        if ($res['success']) {
            flash('success', "Support ticket #{$res['ticket_id']} opened successfully.");
            header("Location: /user/ticket-view.php?id={$res['ticket_id']}");
            exit;
        } else {
            $createError = $res['message'];
        }
    }
}

$tickets = TicketManager::getUserTickets($userId);

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="space-y-6" x-data="{ createModal: false }">

    <!-- Header & Action -->
    <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">Customer Support</h2>
            <p class="text-xs text-zinc-500 mt-1">Get 24/7 technical guidance, order investigation, or custom enterprise assistance.</p>
        </div>
        <button type="button" 
                @click="createModal = true"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/25 transition-all">
            <?= heroicon('plus', 'w-4 h-4') ?>
            <span>Open New Ticket</span>
        </button>
    </div>

    <?php if ($createError): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-3">
            <?= heroicon('x-circle', 'w-5 h-5 text-rose-600 flex-shrink-0') ?>
            <span><?= e($createError) ?></span>
        </div>
    <?php endif; ?>

    <!-- Tickets Table -->
    <div class="bg-white rounded-3xl border border-zinc-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50/80 text-zinc-500 font-semibold border-b border-zinc-100">
                    <tr>
                        <th class="py-3.5 px-6">ID</th>
                        <th class="py-3.5 px-6">Subject</th>
                        <th class="py-3.5 px-6">Category</th>
                        <th class="py-3.5 px-6">Priority</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Last Update</th>
                        <th class="py-3.5 px-6 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-700">
                    <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="7" class="py-12 text-center text-zinc-400">
                                <div class="w-10 h-10 rounded-2xl bg-zinc-100 text-zinc-400 flex items-center justify-center mx-auto mb-2">
                                    <?= heroicon('ticket', 'w-5 h-5') ?>
                                </div>
                                <p class="text-xs font-medium text-zinc-600">No support tickets found</p>
                                <p class="text-[11px] text-zinc-400 mt-0.5">Need help? Open a new ticket anytime.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                            <tr class="hover:bg-zinc-50/80 transition-colors">
                                <td class="py-4 px-6 font-mono font-bold text-purple-700">#<?= $t['id'] ?></td>
                                <td class="py-4 px-6 font-bold text-zinc-900 max-w-sm truncate">
                                    <a href="/user/ticket-view.php?id=<?= $t['id'] ?>" class="hover:text-purple-600 transition-colors">
                                        <?= e($t['subject']) ?>
                                    </a>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-zinc-100 text-zinc-600 text-[10px] font-semibold">
                                        <?= ucfirst($t['category']) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="text-[11px] font-semibold <?= $t['priority'] === 'urgent' ? 'text-rose-600' : ($t['priority'] === 'high' ? 'text-amber-600' : 'text-zinc-600') ?>">
                                        <?= ucfirst($t['priority']) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <?php
                                    $st = $t['status'];
                                    $badge = match($st) {
                                        'open' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'answered' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'user_reply' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'closed' => 'bg-zinc-100 text-zinc-600 border-zinc-200',
                                        default => 'bg-zinc-100 text-zinc-600 border-zinc-200'
                                    };
                                    ?>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border <?= $badge ?>">
                                        <?= str_replace('_', ' ', ucfirst($st)) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-zinc-400 whitespace-nowrap">
                                    <?= timeAgo($t['last_reply_at'] ?? $t['created_at']) ?>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="/user/ticket-view.php?id=<?= $t['id'] ?>" 
                                       class="inline-flex items-center gap-1 px-3 py-1 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-700 text-xs font-semibold transition-colors">
                                        <span>View</span>
                                        <?= heroicon('chevron-right', 'w-3 h-3') ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Ticket Modal -->
    <div x-show="createModal" x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-zinc-200 space-y-4"
             @click.away="createModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-zinc-100">
                <h3 class="text-base font-bold text-zinc-900">Open New Support Ticket</h3>
                <button @click="createModal = false" class="text-zinc-400 hover:text-zinc-600">
                    <?= heroicon('x-mark', 'w-5 h-5') ?>
                </button>
            </div>

            <form action="/user/tickets.php" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Subject</label>
                    <input type="text" name="subject" required placeholder="e.g. Order #1234 refilling request" 
                           class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 transition-all">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Category</label>
                        <select name="category" class="w-full px-3 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 transition-all">
                            <option value="orders">Orders Issue</option>
                            <option value="payment">Payment / Deposit</option>
                            <option value="service">Service Inquiry</option>
                            <option value="bug">Technical Bug</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Priority</label>
                        <select name="priority" class="w-full px-3 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 transition-all">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Detailed Message</label>
                    <textarea name="message" rows="4" required placeholder="Describe your question or issue in detail..." 
                              class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 transition-all"></textarea>
                </div>

                <div class="pt-3 border-t border-zinc-100 flex items-center justify-end gap-2">
                    <button type="button" @click="createModal = false" class="px-4 py-2.5 rounded-2xl border border-zinc-200 text-xs font-semibold text-zinc-700 hover:bg-zinc-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/25">
                        Submit Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
