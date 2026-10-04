<?php
declare(strict_types=1);

$pageTitle = 'Order Details';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Orders/OrderManager.php';
require_once BASE_PATH . '/includes/permissions.php';

use App\Orders\OrderManager;

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0 || !Permissions::userOwnsOrder($orderId, $userId)) {
    renderErrorPage(404, 'Order Not Found', 'The requested order does not exist or you do not have permission to view it.');
}

$order = OrderManager::getOrderById($orderId);
if (!$order) {
    renderErrorPage(404, 'Order Not Found', 'Order details could not be retrieved.');
}

// Fetch order logs
$db = getDB();
$logsStmt = $db->prepare("SELECT * FROM order_logs WHERE order_id = ? ORDER BY id ASC");
$logsStmt->execute([$orderId]);
$orderLogs = $logsStmt->fetchAll();

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="/user/orders.php" class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-500 hover:text-purple-600 transition-colors">
            <?= heroicon('arrow-left-on-rectangle', 'w-4 h-4') ?>
            <span>Back to Orders</span>
        </a>
        <span class="text-xs font-mono font-bold text-purple-700 bg-purple-50 border border-purple-200 px-3 py-1 rounded-full">
            Order #<?= $order['id'] ?>
        </span>
    </div>

    <!-- Main Order Details Card -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-zinc-200/80 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-zinc-100 gap-4">
            <div>
                <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider block">Service Category: <?= e($order['category_name']) ?></span>
                <h2 class="text-lg font-bold text-zinc-900 mt-0.5"><?= e($order['service_name']) ?></h2>
            </div>
            <div>
                <?php
                $st = $order['status'];
                $badgeClass = match($st) {
                    'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'processing' => 'bg-purple-50 text-purple-700 border-purple-200',
                    'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'partial' => 'bg-sky-50 text-sky-700 border-sky-200',
                    'cancelled', 'refunded', 'failed' => 'bg-rose-50 text-rose-700 border-rose-200',
                    default => 'bg-zinc-100 text-zinc-700 border-zinc-200'
                };
                ?>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border <?= $badgeClass ?>">
                    <?= ucfirst($st) ?>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div class="p-3 bg-zinc-50 rounded-2xl border border-zinc-100">
                <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Total Charge</span>
                <span class="text-sm font-black text-purple-700 mt-0.5 block"><?= formatCurrency($order['charge']) ?></span>
            </div>
            <div class="p-3 bg-zinc-50 rounded-2xl border border-zinc-100">
                <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Ordered Quantity</span>
                <span class="text-sm font-bold text-zinc-900 mt-0.5 block"><?= number_format($order['quantity']) ?></span>
            </div>
            <div class="p-3 bg-zinc-50 rounded-2xl border border-zinc-100">
                <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Start Counter</span>
                <span class="text-sm font-mono text-zinc-700 mt-0.5 block"><?= number_format((int)$order['start_counter']) ?></span>
            </div>
            <div class="p-3 bg-zinc-50 rounded-2xl border border-zinc-100">
                <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Remains</span>
                <span class="text-sm font-mono text-zinc-700 mt-0.5 block"><?= number_format((int)$order['remains']) ?></span>
            </div>
        </div>

        <div class="space-y-2">
            <span class="text-xs font-bold text-zinc-700 block">Target Link / URL</span>
            <div class="p-3 bg-zinc-50 rounded-2xl border border-zinc-200 flex items-center justify-between text-xs text-zinc-800">
                <span class="font-mono truncate pr-4"><?= e($order['link']) ?></span>
                <a href="<?= e($order['link']) ?>" target="_blank" rel="noopener" class="text-purple-600 hover:text-purple-800 flex items-center gap-1 font-semibold flex-shrink-0">
                    <span>Visit</span>
                    <?= heroicon('link', 'w-3.5 h-3.5') ?>
                </a>
            </div>
        </div>

        <!-- Execution History / Logs -->
        <div class="pt-6 border-t border-zinc-100 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-700">Audit & Execution Timeline</h3>
            <div class="divide-y divide-zinc-100 bg-zinc-50/50 rounded-2xl border border-zinc-100 p-2">
                <?php if (empty($orderLogs)): ?>
                    <p class="text-xs text-zinc-400 p-3">No log records recorded yet.</p>
                <?php else: ?>
                    <?php foreach ($orderLogs as $log): ?>
                        <div class="p-2.5 flex items-start justify-between gap-4 text-xs">
                            <div>
                                <span class="font-bold text-zinc-800"><?= ucfirst(str_replace('_', ' ', $log['action'])) ?></span>
                                <p class="text-zinc-500 text-[11px] mt-0.5"><?= e($log['details']) ?></p>
                            </div>
                            <span class="text-[10px] text-zinc-400 whitespace-nowrap"><?= date('M j, Y H:i:s', strtotime($log['created_at'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
