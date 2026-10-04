<?php
declare(strict_types=1);

$pageTitle = 'Payments & Deposits';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/payments/payment-manager.php';

$db = getDB();
$payments = $db->query("
    SELECT p.*, u.username, u.email, g.name as gateway_name 
    FROM payments p
    JOIN users u ON p.user_id = u.id
    JOIN payment_gateways g ON p.gateway_id = g.id
    ORDER BY p.id DESC
")->fetchAll();

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="space-y-6">

    <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">Payments & Deposits Ledger</h2>
            <p class="text-xs text-zinc-500 mt-1">Incoming client deposits, transaction reference IDs, and webhook verification states.</p>
        </div>
        <a href="/admin/payment-settings.php" class="px-5 py-2.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/25 flex items-center gap-1.5">
            <?= heroicon('credit-card', 'w-4 h-4') ?>
            <span>Gateway Settings</span>
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-zinc-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50 text-zinc-500 font-semibold border-b border-zinc-100">
                    <tr>
                        <th class="py-3.5 px-6">ID</th>
                        <th class="py-3.5 px-6">Reference</th>
                        <th class="py-3.5 px-6">User</th>
                        <th class="py-3.5 px-6">Gateway</th>
                        <th class="py-3.5 px-6">Gross Amount</th>
                        <th class="py-3.5 px-6">Fee</th>
                        <th class="py-3.5 px-6">Net Credited</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-700">
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="9" class="py-12 text-center text-zinc-400">
                                No deposit transactions recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                            <tr class="hover:bg-zinc-50">
                                <td class="py-4 px-6 font-mono font-bold text-purple-700">#<?= $p['id'] ?></td>
                                <td class="py-4 px-6 font-mono text-[11px] font-semibold text-zinc-900"><?= e($p['transaction_ref']) ?></td>
                                <td class="py-4 px-6 font-bold text-zinc-900"><?= e($p['username']) ?></td>
                                <td class="py-4 px-6"><?= e($p['gateway_name']) ?></td>
                                <td class="py-4 px-6 font-semibold"><?= formatCurrency($p['amount']) ?></td>
                                <td class="py-4 px-6 text-zinc-400"><?= formatCurrency($p['fee']) ?></td>
                                <td class="py-4 px-6 font-bold text-emerald-600"><?= formatCurrency($p['net_amount']) ?></td>
                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $p['status'] === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($p['status'] === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') ?>">
                                        <?= ucfirst($p['status']) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-zinc-400 whitespace-nowrap">
                                    <?= date('M j, Y H:i', strtotime($p['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
