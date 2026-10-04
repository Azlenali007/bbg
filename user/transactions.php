<?php
declare(strict_types=1);

$pageTitle = 'Transactions';

require_once __DIR__ . '/includes/header.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$db = getDB();
$countStmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
$countStmt->execute([$userId]);
$total = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

$stmt = $db->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY id DESC 
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute([$userId]);
$transactions = $stmt->fetchAll();

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">Financial Ledger</h2>
            <p class="text-xs text-zinc-500 mt-1">Audit log of all credits, order charges, automated refunds, and balance reconciliations.</p>
        </div>
        <div class="text-right">
            <span class="text-[11px] font-medium text-zinc-400 block">Available Balance</span>
            <span class="text-lg font-black text-purple-700"><?= formatCurrency($user['wallet_balance'] ?? 0) ?></span>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-3xl border border-zinc-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50/80 text-zinc-500 font-semibold border-b border-zinc-100">
                    <tr>
                        <th class="py-3.5 px-6">ID</th>
                        <th class="py-3.5 px-6">Type</th>
                        <th class="py-3.5 px-6">Description</th>
                        <th class="py-3.5 px-6">Amount</th>
                        <th class="py-3.5 px-6">Balance Before</th>
                        <th class="py-3.5 px-6">Balance After</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-700">
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center text-zinc-400">
                                <div class="w-10 h-10 rounded-2xl bg-zinc-100 text-zinc-400 flex items-center justify-center mx-auto mb-2">
                                    <?= heroicon('credit-card', 'w-5 h-5') ?>
                                </div>
                                <p class="text-xs font-medium text-zinc-600">No transactions recorded yet</p>
                                <p class="text-[11px] text-zinc-400 mt-0.5">Your deposits and order debits will appear here.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): ?>
                            <tr class="hover:bg-zinc-50/80 transition-colors">
                                <td class="py-3.5 px-6 font-mono font-bold text-purple-700">#<?= $tx['id'] ?></td>
                                <td class="py-3.5 px-6">
                                    <?php
                                    $type = $tx['type'];
                                    $isCredit = in_array($type, ['deposit', 'refund', 'bonus'], true);
                                    ?>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $isCredit ? 'bg-emerald-50 text-emerald-700' : 'bg-purple-50 text-purple-700' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $type)) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 max-w-sm">
                                    <span class="font-medium text-zinc-800 block truncate"><?= e($tx['description']) ?></span>
                                </td>
                                <td class="py-3.5 px-6 font-bold <?= $isCredit ? 'text-emerald-600' : 'text-zinc-900' ?>">
                                    <?= ($isCredit ? '+' : '-') . formatCurrency($tx['amount']) ?>
                                </td>
                                <td class="py-3.5 px-6 font-mono text-zinc-400"><?= formatCurrency($tx['balance_before']) ?></td>
                                <td class="py-3.5 px-6 font-mono font-semibold text-zinc-800"><?= formatCurrency($tx['balance_after']) ?></td>
                                <td class="py-3.5 px-6">
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[10px] font-semibold">
                                        Completed
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 text-zinc-400 whitespace-nowrap">
                                    <?= date('M j, Y H:i', strtotime($tx['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="p-4 border-t border-zinc-100 flex items-center justify-between text-xs text-zinc-500">
                <span>Page <?= $page ?> of <?= $totalPages ?> (<?= $total ?> records)</span>
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                        <a href="/user/transactions.php?page=<?= $page - 1 ?>" class="px-3 py-1.5 rounded-xl border border-zinc-200 hover:bg-zinc-50 font-semibold">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="/user/transactions.php?page=<?= $page + 1 ?>" class="px-3 py-1.5 rounded-xl border border-zinc-200 hover:bg-zinc-50 font-semibold">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
