<?php
declare(strict_types=1);

$pageTitle = 'Notifications Dispatch';
require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Notifications/NotificationManager.php';

use App\Notifications\NotificationManager;

$db = getDB();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $target = $_POST['target'] ?? 'all';
    $userId = (int)($_POST['user_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $type = $_POST['type'] ?? 'info';

    if (empty($title) || empty($message)) {
        $error = 'Notification title and message are required.';
    } elseif ($target === 'single' && $userId <= 0) {
        $error = 'Please enter a valid user ID for single user dispatch.';
    } else {
        try {
            if ($target === 'all') {
                // Broadcast to all users (NULL user_id)
                NotificationManager::create(null, $title, $message, $type);
                logAudit($adminId, true, 'broadcast_notification', "Broadcast: {$title}");
                flash('success', 'Global broadcast notification dispatched successfully.');
            } else {
                NotificationManager::create($userId, $title, $message, $type);
                logAudit($adminId, true, 'send_notification', "Sent notification to user #{$userId}: {$title}");
                flash('success', "Notification dispatched to user #{$userId}.");
            }
            header('Location: /admin/notifications.php');
            exit;
        } catch (Throwable $e) {
            $error = 'Dispatch failed: ' . $e->getMessage();
        }
    }
}

// Fetch recent notifications
$notifStmt = $db->query("
    SELECT n.*, u.username 
    FROM notifications n
    LEFT JOIN users u ON n.user_id = u.id
    ORDER BY n.id DESC LIMIT 50
");
$notifications = $notifStmt->fetchAll();
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight">Notifications Dispatch</h1>
            <p class="text-xs text-zinc-500">Send direct notifications to individuals or broadcast announcements to all users.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Send Form -->
        <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-400">Dispatch Notification</h2>
            
            <form action="" method="POST" class="space-y-4 text-xs" x-data="{ target: 'all' }">
                <?= csrf_field() ?>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Target Audience</label>
                    <select name="target" x-model="target" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                        <option value="all">All Users (Global Broadcast)</option>
                        <option value="single">Specific User</option>
                    </select>
                </div>

                <div x-show="target === 'single'" x-cloak>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">User ID</label>
                    <input type="number" name="user_id" placeholder="e.g. 1" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600">
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Type</label>
                    <select name="type" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                        <option value="info">Info (Blue)</option>
                        <option value="success">Success (Green)</option>
                        <option value="warning">Warning (Amber)</option>
                        <option value="danger">Danger (Red)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Title</label>
                    <input type="text" name="title" required placeholder="e.g. Scheduled Maintenance" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Message Content</label>
                    <textarea name="message" rows="4" required placeholder="Type notification message..." class="w-full p-3.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600"></textarea>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold shadow-md shadow-purple-600/20 transition-all">
                    Send Notification
                </button>
            </form>
        </div>

        <!-- Recent Dispatched Notifications List -->
        <div class="lg:col-span-2 bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-400">Recently Dispatched</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-50 border-b border-zinc-200 text-zinc-500 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="p-3">Audience</th>
                            <th class="p-3">Title & Message</th>
                            <th class="p-3">Type</th>
                            <th class="p-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        <?php if (empty($notifications)): ?>
                            <tr>
                                <td colspan="4" class="p-6 text-center text-zinc-400">No notifications dispatched yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): ?>
                                <tr class="hover:bg-zinc-50/50">
                                    <td class="p-3 font-bold text-zinc-900 whitespace-nowrap">
                                        <?= $n['user_id'] ? ('User #' . $n['user_id'] . ' (' . e($n['username'] ?? 'User') . ')') : '<span class="text-purple-600">Global (All Users)</span>' ?>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-bold text-zinc-900"><?= e($n['title']) ?></div>
                                        <div class="text-zinc-500 text-[11px] truncate max-w-md"><?= e($n['message']) ?></div>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full <?= $n['type'] === 'success' ? 'bg-emerald-100 text-emerald-800' : ($n['type'] === 'danger' ? 'bg-rose-100 text-rose-800' : ($n['type'] === 'warning' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800')) ?>">
                                            <?= ucfirst($n['type']) ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-zinc-400 whitespace-nowrap"><?= date('M d, H:i', strtotime($n['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
