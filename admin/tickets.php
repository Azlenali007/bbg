<?php
declare(strict_types=1);

$pageTitle = 'Support Tickets';
require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Tickets/TicketManager.php';

use App\Tickets\TicketManager;

$db = getDB();
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];

if ($statusFilter !== '') {
    $where[] = "t.status = ?";
    $params[] = $statusFilter;
}

if ($search !== '') {
    $where[] = "(t.subject LIKE ? OR u.username LIKE ? OR t.id = ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = (int)$search;
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $db->prepare("
    SELECT t.*, u.username as user_name, u.email as user_email
    FROM tickets t
    LEFT JOIN users u ON t.user_id = u.id
    {$whereSql}
    ORDER BY FIELD(t.status, 'open', 'user_reply', 'answered', 'closed'), t.updated_at DESC
");
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Counts for tabs
$counts = [
    'all' => (int)$db->query("SELECT COUNT(*) FROM tickets")->fetchColumn(),
    'open' => (int)$db->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn(),
    'user_reply' => (int)$db->query("SELECT COUNT(*) FROM tickets WHERE status = 'user_reply'")->fetchColumn(),
    'answered' => (int)$db->query("SELECT COUNT(*) FROM tickets WHERE status = 'answered'")->fetchColumn(),
    'closed' => (int)$db->query("SELECT COUNT(*) FROM tickets WHERE status = 'closed'")->fetchColumn(),
];
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight">Support Tickets</h1>
            <p class="text-xs text-zinc-500">Provide direct help desk responses to registered customers.</p>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-bold">
        <a href="/admin/tickets.php" class="px-3.5 py-2 rounded-xl transition-all <?= $statusFilter === '' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-zinc-600 border border-zinc-200 hover:bg-zinc-50' ?>">
            All (<?= $counts['all'] ?>)
        </a>
        <a href="?status=open" class="px-3.5 py-2 rounded-xl transition-all <?= $statusFilter === 'open' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-zinc-600 border border-zinc-200 hover:bg-zinc-50' ?>">
            Open (<?= $counts['open'] ?>)
        </a>
        <a href="?status=user_reply" class="px-3.5 py-2 rounded-xl transition-all <?= $statusFilter === 'user_reply' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-zinc-600 border border-zinc-200 hover:bg-zinc-50' ?>">
            User Reply (<?= $counts['user_reply'] ?>)
        </a>
        <a href="?status=answered" class="px-3.5 py-2 rounded-xl transition-all <?= $statusFilter === 'answered' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-zinc-600 border border-zinc-200 hover:bg-zinc-50' ?>">
            Answered (<?= $counts['answered'] ?>)
        </a>
        <a href="?status=closed" class="px-3.5 py-2 rounded-xl transition-all <?= $statusFilter === 'closed' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-zinc-600 border border-zinc-200 hover:bg-zinc-50' ?>">
            Closed (<?= $counts['closed'] ?>)
        </a>
    </div>

    <!-- Tickets List -->
    <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50 border-b border-zinc-200 text-zinc-500 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="p-3">ID</th>
                        <th class="p-3">User</th>
                        <th class="p-3">Subject</th>
                        <th class="p-3">Category</th>
                        <th class="p-3">Priority</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Last Updated</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="8" class="p-6 text-center text-zinc-400">No support tickets found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                            <tr class="hover:bg-zinc-50/50">
                                <td class="p-3 font-mono font-bold text-zinc-400">#<?= $t['id'] ?></td>
                                <td class="p-3 font-bold text-zinc-900">
                                    <a href="/admin/user-view.php?id=<?= $t['user_id'] ?>" class="text-purple-600 hover:underline">
                                        <?= e($t['user_name'] ?? 'User #' . $t['user_id']) ?>
                                    </a>
                                </td>
                                <td class="p-3 font-semibold text-zinc-800 max-w-sm truncate">
                                    <a href="/admin/ticket-view.php?id=<?= $t['id'] ?>" class="hover:text-purple-600">
                                        <?= e($t['subject']) ?>
                                    </a>
                                </td>
                                <td class="p-3 text-zinc-500 uppercase text-[10px] font-bold"><?= e($t['category']) ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full <?= $t['priority'] === 'urgent' ? 'bg-rose-100 text-rose-800' : ($t['priority'] === 'high' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') ?>">
                                        <?= ucfirst($t['priority']) ?>
                                    </span>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full <?= $t['status'] === 'open' ? 'bg-blue-100 text-blue-800' : ($t['status'] === 'user_reply' ? 'bg-amber-100 text-amber-800' : ($t['status'] === 'answered' ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-600')) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $t['status'])) ?>
                                    </span>
                                </td>
                                <td class="p-3 text-zinc-400"><?= date('M d, H:i', strtotime($t['updated_at'])) ?></td>
                                <td class="p-3 text-right">
                                    <a href="/admin/ticket-view.php?id=<?= $t['id'] ?>" class="px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold rounded-lg text-xs">
                                        Reply &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
