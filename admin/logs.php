<?php
declare(strict_types=1);

$pageTitle = 'Audit & System Logs';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$actionFilter = trim($_GET['action'] ?? '');
$where = [];
$params = [];

if ($actionFilter !== '') {
    $where[] = "action LIKE ?";
    $params[] = "%{$actionFilter}%";
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$countStmt = $db->prepare("SELECT COUNT(*) FROM audit_logs {$whereSql}");
$countStmt->execute($params);
$totalLogs = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalLogs / $perPage));

$stmt = $db->prepare("
    SELECT * FROM audit_logs 
    {$whereSql} 
    ORDER BY id DESC 
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight">Audit & Security Logs</h1>
            <p class="text-xs text-zinc-500">Immutable chronological record of administrative and security events.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-zinc-500">Total Entries:</span>
            <span class="px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 font-extrabold text-xs"><?= number_format($totalLogs) ?></span>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-4 shadow-sm">
        <form action="" method="GET" class="flex items-center gap-3 text-xs">
            <input type="text" name="action" value="<?= e($actionFilter) ?>" placeholder="Filter by action name (e.g. login, update_order)..." 
                   class="max-w-md w-full px-3.5 py-2 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600">
            <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl shadow-sm transition-all">
                Search Logs
            </button>
            <?php if ($actionFilter): ?>
                <a href="/admin/logs.php" class="text-zinc-500 hover:text-zinc-800 font-semibold">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50 border-b border-zinc-200 text-zinc-500 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="p-3">ID</th>
                        <th class="p-3">Actor</th>
                        <th class="p-3">Action</th>
                        <th class="p-3">Details / Context</th>
                        <th class="p-3">IP Address</th>
                        <th class="p-3">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 font-mono">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="p-6 text-center text-zinc-400 font-sans">No audit events logged.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                            <tr class="hover:bg-zinc-50/50">
                                <td class="p-3 text-zinc-400">#<?= $l['id'] ?></td>
                                <td class="p-3 font-sans">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full <?= $l['is_admin'] ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-700' ?>">
                                        <?= $l['is_admin'] ? ('Admin #' . $l['user_id']) : ('User #' . $l['user_id']) ?>
                                    </span>
                                </td>
                                <td class="p-3 font-bold text-zinc-900"><?= e($l['action']) ?></td>
                                <td class="p-3 font-sans text-zinc-600 max-w-sm truncate"><?= e($l['details'] ?? '-') ?></td>
                                <td class="p-3 text-zinc-400"><?= e($l['ip_address'] ?? 'CLI') ?></td>
                                <td class="p-3 text-zinc-400"><?= date('M d, H:i:s', strtotime($l['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-4 text-xs font-sans">
                <span class="text-zinc-500">Page <?= $page ?> of <?= $totalPages ?></span>
                <div class="flex items-center gap-1">
                    <?php for ($i = 1; $i <= min(10, $totalPages); $i++): ?>
                        <a href="?page=<?= $i ?>&action=<?= urlencode($actionFilter) ?>" 
                           class="w-7 h-7 flex items-center justify-center rounded-lg font-bold <?= $i === $page ? 'bg-purple-600 text-white' : 'text-zinc-600 hover:bg-zinc-100' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
