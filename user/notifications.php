<?php
declare(strict_types=1);

$pageTitle = 'Notifications';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Notifications/NotificationManager.php';

use App\Notifications\NotificationManager;

$allNotifications = NotificationManager::getUserNotifications($userId, 50);

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">Notification Center</h2>
            <p class="text-xs text-zinc-500 mt-1">System updates, order dispatch confirmations, and automated account notices.</p>
        </div>
        <?php if (!empty($allNotifications)): ?>
            <button onclick="ApexNotifications.markAllAsRead()" class="px-4 py-2 rounded-2xl bg-purple-50 text-purple-700 hover:bg-purple-100 text-xs font-bold transition-colors">
                Mark All as Read
            </button>
        <?php endif; ?>
    </div>

    <!-- Notification List -->
    <div class="bg-white rounded-3xl border border-zinc-200/80 shadow-sm divide-y divide-zinc-100 overflow-hidden">
        <?php if (empty($allNotifications)): ?>
            <div class="py-16 text-center text-zinc-400">
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-3">
                    <?= heroicon('bell', 'w-6 h-6') ?>
                </div>
                <h4 class="text-sm font-bold text-zinc-800">No new notifications</h4>
                <p class="text-xs text-zinc-400 mt-1">You are all caught up on system alerts and order updates.</p>
            </div>
        <?php else: ?>
            <?php foreach ($allNotifications as $n): ?>
                <div class="p-5 text-left transition-colors notification-item flex items-start gap-4 <?= empty($n['is_read']) ? 'bg-purple-50/30' : 'hover:bg-zinc-50' ?>">
                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0 <?= empty($n['is_read']) ? 'bg-purple-600 text-white shadow-md shadow-purple-600/20' : 'bg-zinc-100 text-zinc-400' ?>">
                        <?= heroicon('bell', 'w-5 h-5') ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <h4 class="text-sm font-bold text-zinc-900"><?= e($n['title']) ?></h4>
                            <span class="text-[11px] text-zinc-400 whitespace-nowrap"><?= timeAgo($n['created_at']) ?></span>
                        </div>
                        <p class="text-xs text-zinc-600 mt-1 leading-relaxed"><?= e($n['message']) ?></p>
                        <?php if (empty($n['is_read'])): ?>
                            <button onclick="ApexNotifications.markAsRead(<?= $n['id'] ?>, this)" class="mark-read-btn text-xs font-semibold text-purple-600 hover:text-purple-800 mt-2 inline-block">
                                Mark as read
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
