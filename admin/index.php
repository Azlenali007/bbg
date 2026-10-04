<?php
declare(strict_types=1);

$pageTitle = 'Overview';
$loadApexCharts = true;

require_once __DIR__ . '/includes/header.php';

$db = getDB();

// 1. Total users
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// 2. Orders stats
$orderStats = $db->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
        COALESCE(SUM(charge), 0) as total_revenue
    FROM orders
")->fetch();

// 3. Payments & Deposits
$totalDeposits = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();

// 4. Open tickets
$openTickets = (int)$db->query("SELECT COUNT(*) FROM tickets WHERE status IN ('open', 'user_reply')")->fetchColumn();

// 5. Recent 5 orders
$recentOrders = $db->query("
    SELECT o.*, u.username, s.name as service_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN services s ON o.service_id = s.id
    ORDER BY o.id DESC 
    LIMIT 5
")->fetchAll();

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="space-y-6">

    <!-- Stat Cards Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Revenue -->
        <div class="bg-white p-5 rounded-3xl border border-zinc-200/80 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500">Gross Orders Value</span>
                <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <?= heroicon('currency-dollar', 'w-4 h-4') ?>
                </div>
            </div>
            <div class="mt-2 text-2xl font-black text-zinc-900 tracking-tight">
                <?= formatCurrency((float)($orderStats['total_revenue'] ?? 0)) ?>
            </div>
            <span class="mt-1 text-[11px] text-zinc-400 block">From <?= number_format((int)$orderStats['total_orders']) ?> orders</span>
        </div>

        <!-- Total Deposits -->
        <div class="bg-white p-5 rounded-3xl border border-zinc-200/80 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500">Completed Deposits</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <?= heroicon('wallet', 'w-4 h-4') ?>
                </div>
            </div>
            <div class="mt-2 text-2xl font-black text-emerald-600 tracking-tight">
                <?= formatCurrency($totalDeposits) ?>
            </div>
            <span class="mt-1 text-[11px] text-zinc-400 block">Verified wallet funding</span>
        </div>

        <!-- Registered Users -->
        <div class="bg-white p-5 rounded-3xl border border-zinc-200/80 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500">Total Users</span>
                <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <?= heroicon('users', 'w-4 h-4') ?>
                </div>
            </div>
            <div class="mt-2 text-2xl font-black text-zinc-900 tracking-tight">
                <?= number_format($totalUsers) ?>
            </div>
            <a href="/admin/users.php" class="mt-1 text-[11px] font-semibold text-purple-600 hover:text-purple-800 inline-block">Manage users &rarr;</a>
        </div>

        <!-- Open Tickets -->
        <div class="bg-white p-5 rounded-3xl border border-zinc-200/80 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500">Pending Support</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <?= heroicon('ticket', 'w-4 h-4') ?>
                </div>
            </div>
            <div class="mt-2 text-2xl font-black <?= $openTickets > 0 ? 'text-amber-600' : 'text-zinc-900' ?> tracking-tight">
                <?= number_format($openTickets) ?>
            </div>
            <a href="/admin/tickets.php" class="mt-1 text-[11px] font-semibold text-purple-600 hover:text-purple-800 inline-block">View tickets &rarr;</a>
        </div>

    </div>

    <!-- Quick Operations & Recent Orders -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Operations Panel -->
        <div class="bg-white rounded-3xl p-6 border border-zinc-200/80 shadow-sm space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-700 pb-3 border-b border-zinc-100">
                Administration Shortcuts
            </h3>
            <div class="grid grid-cols-1 gap-2.5">
                <a href="/admin/service-add.php" class="p-3 rounded-2xl bg-zinc-50 hover:bg-purple-50 hover:text-purple-700 transition-colors flex items-center gap-3 text-xs font-bold text-zinc-800 border border-zinc-100">
                    <?= heroicon('plus-circle', 'w-4 h-4 text-purple-600') ?>
                    <span>Create Custom Service</span>
                </a>
                <a href="/admin/service-import.php" class="p-3 rounded-2xl bg-zinc-50 hover:bg-purple-50 hover:text-purple-700 transition-colors flex items-center gap-3 text-xs font-bold text-zinc-800 border border-zinc-100">
                    <?= heroicon('arrow-down-tray', 'w-4 h-4 text-purple-600') ?>
                    <span>Import Services via API</span>
                </a>
                <a href="/admin/providers.php" class="p-3 rounded-2xl bg-zinc-50 hover:bg-purple-50 hover:text-purple-700 transition-colors flex items-center gap-3 text-xs font-bold text-zinc-800 border border-zinc-100">
                    <?= heroicon('server', 'w-4 h-4 text-purple-600') ?>
                    <span>Manage Providers & Balances</span>
                </a>
                <a href="/admin/banners.php" class="p-3 rounded-2xl bg-zinc-50 hover:bg-purple-50 hover:text-purple-700 transition-colors flex items-center gap-3 text-xs font-bold text-zinc-800 border border-zinc-100">
                    <?= heroicon('photo', 'w-4 h-4 text-purple-600') ?>
                    <span>Configure Hero Banners</span>
                </a>
                <a href="/admin/flash-sales.php" class="p-3 rounded-2xl bg-zinc-50 hover:bg-purple-50 hover:text-purple-700 transition-colors flex items-center gap-3 text-xs font-bold text-zinc-800 border border-zinc-100">
                    <?= heroicon('bolt', 'w-4 h-4 text-purple-600') ?>
                    <span>Create Flash Sale Promo</span>
                </a>
            </div>
        </div>

        <!-- Recent System Orders Table -->
        <div class="lg:col-span-2 bg-white rounded-3xl border border-zinc-200/80 shadow-sm overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-6 border-b border-zinc-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-zinc-900">Live Orders Stream</h3>
                        <p class="text-xs text-zinc-400">Real-time incoming orders across all users</p>
                    </div>
                    <a href="/admin/orders.php" class="text-xs font-semibold text-purple-600 hover:text-purple-800">
                        View All &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-zinc-50/80 text-zinc-500 font-semibold border-b border-zinc-100">
                            <tr>
                                <th class="py-3 px-6">ID</th>
                                <th class="py-3 px-6">User</th>
                                <th class="py-3 px-6">Service</th>
                                <th class="py-3 px-6">Charge</th>
                                <th class="py-3 px-6">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 text-zinc-700">
                            <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-zinc-400">
                                        No orders in the database.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $ro): ?>
                                    <tr class="hover:bg-zinc-50">
                                        <td class="py-3 px-6 font-mono font-bold text-purple-700">#<?= $ro['id'] ?></td>
                                        <td class="py-3 px-6 font-semibold text-zinc-900"><?= e($ro['username']) ?></td>
                                        <td class="py-3 px-6 max-w-xs truncate"><?= e($ro['service_name']) ?></td>
                                        <td class="py-3 px-6 font-bold"><?= formatCurrency($ro['charge']) ?></td>
                                        <td class="py-3 px-6">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700">
                                                <?= ucfirst($ro['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
