<?php
declare(strict_types=1);

$pageTitle = 'Transactions Ledger';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

$filterType = $_GET['type'] ?? '';
$searchUser = trim($_GET['user'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($filterType !== '') {
    $where[] = "t.type = ?";
    $params[] = $filterType;
}

if ($searchUser !== '') {
    $where[] = "(u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$searchUser}%";
    $params[] = "%{$searchUser}%";
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count total
$countStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    {$whereSql}
");
$countStmt->execute($params);
$totalTx = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalTx / $perPage));

// Fetch transactions
$stmt = $db->prepare("
    SELECT t.*, u.username, u.email
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    {$whereSql}
    ORDER BY t.id DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight">Transactions Ledger</h1>
            <p class="text-xs text-zinc-500">Comprehensive real-time ledger of all user balance changes.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-zinc-500">Total Entries:</span>
            <span class="px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 font-extrabold text-xs"><?= number_format($totalTx) ?></span>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white border border-zinc-200/80 rounded-2xl p-4 shadow-sm">
        <form action="" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div>
                <input type="text" name="user" value="<?= e($searchUser) ?>" placeholder="Search username or email..." 
                       class="w-full px-3.5 py-2 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600">
            </div>
            <div>
                <select name="type" class="w-full px-3.5 py-2 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                    <option value="">All Transaction Types</option>
                    <option value="deposit" <?= $filterType === 'deposit' ? 'selected' : '' ?>>Deposits</option>
                    <option value="order_charge" <?= $filterType === 'order_charge' ? 'selected' : '' ?>>Order Charges</option>
                    <option value="order_refund" <?= $filterType === 'order_refund' ? 'selected' : '' ?>>Refunds</option>
                    <option value="manual_credit" <?= $filterType === 'manual_credit' ? 'selected' : '' ?>>Manual Credits</option>
                    <option value="manual_debit" <?= $filterType === 'manual_debit' ? 'selected' : '' ?>>Manual Debits</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl shadow-sm transition-all">
                    Filter
                </button>
                <a href="/admin/transactions.php" class="px-3 py-2 text-zinc-500 hover:text-zinc-800 font-semibold">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50 border-b border-zinc-200 text-zinc-500 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="p-3">ID</th>
                        <th class="p-3">User</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Balance After</th>
                        <th class="p-3">Description</th>
                        <th class="p-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7" class="p-6 text-center text-zinc-400">No transactions recorded.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): 
                            $isCredit = (float)$tx['amount'] >= 0;
                        ?>
                            <tr class="hover:bg-zinc-50/50">
                                <td class="p-3 font-mono text-zinc-400">#<?= $tx['id'] ?></td>
                                <td class="p-3 font-bold text-zinc-900">
                                    <a href="/admin/user-view.php?id=<?= $tx['user_id'] ?>" class="text-purple-600 hover:underline">
                                        <?= e($tx['username']) ?>
                                    </a>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full <?= $isCredit ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $tx['type'])) ?>
                                    </span>
                                </td>
                                <td class="p-3 font-bold <?= $isCredit ? 'text-emerald-600' : 'text-rose-600' ?>">
                                    <?= ($isCredit ? '+' : '') . formatCurrency($tx['amount']) ?>
                                </td>
                                <td class="p-3 font-semibold text-zinc-700"><?= formatCurrency($tx['balance_after']) ?></td>
                                <td class="p-3 text-zinc-500 max-w-xs truncate"><?= e($tx['description'] ?? 'N/A') ?></td>
                                <td class="p-3 text-zinc-400"><?= date('M d, Y H:i:s', strtotime($tx['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-4 text-xs">
                <span class="text-zinc-500">Page <?= $page ?> of <?= $totalPages ?></span>
                <div class="flex items-center gap-1">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&type=<?= urlencode($filterType) ?>&user=<?= urlencode($searchUser) ?>" 
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
