<?php
declare(strict_types=1);

$pageTitle = 'Announcements';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $type = $_POST['type'] ?? 'update';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title) || empty($content)) {
            $error = 'Title and content are required.';
        } else {
            $stmt = $db->prepare("INSERT INTO announcements (title, content, type, is_active, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $content, $type, $isActive]);
            logAudit($adminId, true, 'create_announcement', "Created announcement: {$title}");
            flash('success', 'Announcement published.');
            header('Location: /admin/announcements.php');
            exit;
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("UPDATE announcements SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?")->execute([$id]);
        flash('success', 'Announcement status updated.');
        header('Location: /admin/announcements.php');
        exit;
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM announcements WHERE id = ?")->execute([$id]);
        flash('success', 'Announcement deleted.');
        header('Location: /admin/announcements.php');
        exit;
    }
}

$announcements = $db->query("SELECT * FROM announcements ORDER BY id DESC")->fetchAll();
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight">Platform Announcements</h1>
            <p class="text-xs text-zinc-500">Post system alerts, maintenance notes, and feature updates.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- New Announcement -->
        <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-400">New Announcement</h2>
            <form action="" method="POST" class="space-y-4 text-xs">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Title</label>
                    <input type="text" name="title" required placeholder="e.g. Instagram Services Faster Update" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Type</label>
                    <select name="type" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                        <option value="update">Service Update</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="offer">Special Offer</option>
                        <option value="alert">System Alert</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Content</label>
                    <textarea name="content" rows="4" required placeholder="Announcement details..." class="w-full p-3.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600"></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked class="rounded border-zinc-300 text-purple-600 focus:ring-purple-500">
                    <label for="is_active" class="text-zinc-700 font-semibold cursor-pointer">
                        Active Immediately
                    </label>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold shadow-md shadow-purple-600/20 transition-all">
                    Post Announcement
                </button>
            </form>
        </div>

        <!-- List -->
        <div class="lg:col-span-2 bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-400">All Announcements</h2>
            <div class="divide-y divide-zinc-100">
                <?php if (empty($announcements)): ?>
                    <p class="text-center py-8 text-zinc-400 text-xs">No announcements created yet.</p>
                <?php else: ?>
                    <?php foreach ($announcements as $a): ?>
                        <div class="py-4 flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full <?= $a['is_active'] ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-600' ?>">
                                        <?= $a['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                    <span class="text-[10px] font-extrabold text-purple-600 uppercase tracking-wider"><?= e($a['type']) ?></span>
                                    <span class="text-[10px] text-zinc-400">&bull; <?= date('M d, Y', strtotime($a['created_at'])) ?></span>
                                </div>
                                <h3 class="font-bold text-sm text-zinc-900"><?= e($a['title']) ?></h3>
                                <p class="text-xs text-zinc-600 leading-relaxed"><?= nl2br(e($a['content'])) ?></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <form action="" method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                    <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg <?= $a['is_active'] ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' ?>">
                                        <?= $a['is_active'] ? 'Disable' : 'Enable' ?>
                                    </button>
                                </form>
                                <form action="" method="POST" onsubmit="return confirm('Delete announcement?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                    <button type="submit" class="p-1 text-zinc-400 hover:text-rose-600">
                                        <?= heroicon('trash', 'w-4 h-4') ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
