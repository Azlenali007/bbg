<?php
declare(strict_types=1);

$pageTitle = 'Order History';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Orders/OrderManager.php';

use App\Orders\OrderManager;

$status = trim($_GET['status'] ?? 'all');
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

$orderData = OrderManager::getUserOrders($userId, $status, $search, $page, 20);
$orders = $orderData['orders'];
$totalPages = $orderData['total_pages'];

$statusTabs = ['all', 'pending', 'processing', 'completed', 'partial', 'cancelled', 'refunded', 'failed'];

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="space-y-6">

    <!-- Header & Search -->
    <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">Order History</h2>
            <p class="text-xs text-zinc-500 mt-1">Review live execution states, counters, charges, and real-time delivery progress.</p>
        </div>

        <form action="/user/orders.php" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <div class="relative">
                <input type="text" 
                       name="search" 
                       value="<?= e($search) ?>" 
                       placeholder="Order ID or link..." 
                       class="pl-9 pr-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 focus:ring-4 focus:ring-purple-600/10 transition-all w-52 sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                    <?= heroicon('magnifying-glass', 'w-4 h-4') ?>
                </div>
            </div>
            <button type="submit" class="p-2.5 rounded-2xl bg-purple-600 text-white hover:bg-purple-700 transition-colors">
                <?= heroicon('magnifying-glass', 'w-4 h-4') ?>
            </button>
        </form>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
        <?php foreach ($statusTabs as $st): ?>
            <a href="/user/orders.php?status=<?= $st ?>&search=<?= urlencode($search) ?>" 
               class="px-4 py-2 rounded-2xl text-xs font-bold whitespace-nowrap transition-all <?= $status === $st ? 'bg-purple-600 text-white shadow-md shadow-purple-600/20' : 'bg-white border border-zinc-200 text-zinc-600 hover:bg-zinc-50' ?>">
                <?= ucfirst($st) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-3xl border border-zinc-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50/80 text-zinc-500 font-semibold border-b border-zinc-100">
                    <tr>
                        <th class="py-3.5 px-6">ID</th>
                        <th class="py-3.5 px-6">Service</th>
                        <th class="py-3.5 px-6">Link</th>
                        <th class="py-3.5 px-6">Quantity</th>
                        <th class="py-3.5 px-6">Charge</th>
                        <th class="py-3.5 px-6">Remains</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Date</th>
                        <th class="py-3.5 px-6 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-700">
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" class="py-12 text-center text-zinc-400">
                                <div class="w-10 h-10 rounded-2xl bg-zinc-100 text-zinc-400 flex items-center justify-center mx-auto mb-2">
                                    <?= heroicon('shopping-cart', 'w-5 h-5') ?>
                                </div>
                                <p class="text-xs font-medium text-zinc-600">No orders found</p>
                                <p class="text-[11px] text-zinc-400 mt-0.5">There are no orders matching the selected filter criteria.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <tr class="hover:bg-zinc-50/80 transition-colors">
                                <td class="py-4 px-6 font-mono font-bold text-purple-700">#<?= $o['id'] ?></td>
                                <td class="py-4 px-6 max-w-xs">
                                    <span class="font-semibold text-zinc-900 block truncate"><?= e($o['service_name']) ?></span>
                                    <span class="text-[10px] text-zinc-400"><?= e($o['category_name']) ?></span>
                                </td>
                                <td class="py-4 px-6 max-w-[180px] truncate text-purple-600">
                                    <a href="<?= e($o['link']) ?>" target="_blank" rel="noopener" class="hover:underline flex items-center gap-1">
                                        <?= heroicon('link', 'w-3 h-3 flex-shrink-0') ?>
                                        <span class="truncate"><?= e($o['link']) ?></span>
                                    </a>
                                </td>
                                <td class="py-4 px-6 font-medium"><?= number_format($o['quantity']) ?></td>
                                <td class="py-4 px-6 font-bold text-zinc-900"><?= formatCurrency($o['charge']) ?></td>
                                <td class="py-4 px-6 font-mono text-zinc-500"><?= number_format((int)$o['remains']) ?></td>
                                <td class="py-4 px-6">
                                    <?php
                                    $st = $o['status'];
                                    $badgeClass = match($st) {
                                        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'processing' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'partial' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'cancelled', 'refunded', 'failed' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        default => 'bg-zinc-100 text-zinc-700 border-zinc-200'
                                    };
                                    ?>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border <?= $badgeClass ?>">
                                        <?= ucfirst($st) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-zinc-400 whitespace-nowrap">
                                    <?= date('M j, Y H:i', strtotime($o['created_at'])) ?>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="/user/order-details.php?id=<?= $o['id'] ?>" 
                                       class="inline-flex items-center gap-1 p-2 rounded-xl text-zinc-400 hover:text-purple-600 hover:bg-purple-50 transition-colors">
                                        <?= heroicon('eye', 'w-4 h-4') ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="p-4 border-t border-zinc-100 flex items-center justify-between">
                <span class="text-xs text-zinc-500">
                    Page <?= $page ?> of <?= $totalPages ?> (<?= $orderData['total'] ?> total)
                </span>
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                        <a href="/user/orders.php?status=<?= $status ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" 
                           class="px-3 py-1.5 rounded-xl border border-zinc-200 text-xs font-semibold text-zinc-700 hover:bg-zinc-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="/user/orders.php?status=<?= $status ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" 
                           class="px-3 py-1.5 rounded-xl border border-zinc-200 text-xs font-semibold text-zinc-700 hover:bg-zinc-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
