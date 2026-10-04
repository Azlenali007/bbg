<?php
declare(strict_types=1);

$pageTitle = 'Orders Management';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Wallet/WalletManager.php';

use App\Wallet\WalletManager;

$db = getDB();

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    require_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $validStatuses = ['pending', 'processing', 'completed', 'partial', 'cancelled', 'refunded', 'failed'];

    if ($orderId > 0 && in_array($newStatus, $validStatuses, true)) {
        // Fetch order
        $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $orderStmt->execute([$orderId]);
        $order = $orderStmt->fetch();

        if ($order) {
            $oldStatus = $order['status'];
            
            // If changing to refunded or cancelled from an active status, refund wallet
            if (in_array($newStatus, ['refunded', 'cancelled'], true) && !in_array($oldStatus, ['refunded', 'cancelled'], true)) {
                WalletManager::credit(
                    (int)$order['user_id'],
                    (float)$order['charge'],
                    "Refund for Order #{$orderId} ({$newStatus})",
                    'order',
                    (string)$orderId,
                    'refund'
                );
            }

            $upStmt = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $upStmt->execute([$newStatus, $orderId]);

            $logStmt = $db->prepare("INSERT INTO order_logs (order_id, action, details, created_at) VALUES (?, 'status_change', ?, NOW())");
            $logStmt->execute([$orderId, "Admin changed status from {$oldStatus} to {$newStatus}"]);

            flash('success', "Order #{$orderId} status updated to " . ucfirst($newStatus));
            header('Location: /admin/orders.php');
            exit;
        }
    }
}

$status = trim($_GET['status'] ?? 'all');
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$where = ["1=1"];
$params = [];

if ($status && $status !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $status;
}

if ($search) {
    $where[] = "(o.id = ? OR u.username LIKE ? OR o.link LIKE ? OR o.provider_order_id = ?)";
    $params[] = is_numeric($search) ? (int)$search : 0;
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $search;
}

$whereClause = implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON o.user_id = u.id WHERE {$whereClause}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

$sql = "
    SELECT o.*, u.username, s.name as service_name, p.name as provider_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN services s ON o.service_id = s.id
    LEFT JOIN providers p ON o.provider_id = p.id
    WHERE {$whereClause}
    ORDER BY o.id DESC
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statusTabs = ['all', 'pending', 'processing', 'completed', 'partial', 'cancelled', 'refunded', 'failed'];

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="space-y-6">

    <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">Orders Registry</h2>
            <p class="text-xs text-zinc-500 mt-1">Live customer orders across all active service pipelines.</p>
        </div>

        <form action="/admin/orders.php" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <div class="relative">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="ID, username, link..." 
                       class="pl-9 pr-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 w-52 sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                    <?= heroicon('magnifying-glass', 'w-4 h-4') ?>
                </div>
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-2xl bg-purple-600 text-white text-xs font-bold">Filter</button>
        </form>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
        <?php foreach ($statusTabs as $st): ?>
            <a href="/admin/orders.php?status=<?= $st ?>&search=<?= urlencode($search) ?>" 
               class="px-4 py-2 rounded-2xl text-xs font-bold whitespace-nowrap transition-all <?= $status === $st ? 'bg-purple-600 text-white shadow-md shadow-purple-600/20' : 'bg-white border border-zinc-200 text-zinc-600 hover:bg-zinc-50' ?>">
                <?= ucfirst($st) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-zinc-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50 text-zinc-500 font-semibold border-b border-zinc-100">
                    <tr>
                        <th class="py-3.5 px-6">ID</th>
                        <th class="py-3.5 px-6">User</th>
                        <th class="py-3.5 px-6">Service</th>
                        <th class="py-3.5 px-6">Link</th>
                        <th class="py-3.5 px-6">Qty</th>
                        <th class="py-3.5 px-6">Charge</th>
                        <th class="py-3.5 px-6">Provider Info</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Update</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-700">
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" class="py-12 text-center text-zinc-400">
                                No orders matching current filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <tr class="hover:bg-zinc-50">
                                <td class="py-3.5 px-6 font-mono font-bold text-purple-700">#<?= $o['id'] ?></td>
                                <td class="py-3.5 px-6 font-semibold text-zinc-900"><?= e($o['username']) ?></td>
                                <td class="py-3.5 px-6 max-w-xs truncate"><?= e($o['service_name']) ?></td>
                                <td class="py-3.5 px-6 max-w-[150px] truncate text-purple-600">
                                    <a href="<?= e($o['link']) ?>" target="_blank" class="hover:underline"><?= e($o['link']) ?></a>
                                </td>
                                <td class="py-3.5 px-6 font-medium"><?= number_format($o['quantity']) ?></td>
                                <td class="py-3.5 px-6 font-bold text-zinc-900"><?= formatCurrency($o['charge']) ?></td>
                                <td class="py-3.5 px-6 font-mono text-[11px] text-zinc-500">
                                    <?= !empty($o['provider_name']) ? e($o['provider_name']) . " (#{$o['provider_order_id']})" : '<span class="text-zinc-400">Manual</span>' ?>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200">
                                        <?= ucfirst($o['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 text-right">
                                    <form action="/admin/orders.php" method="POST" class="inline-flex items-center gap-1.5">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" class="px-2 py-1 rounded-xl border border-zinc-200 text-[11px] font-semibold bg-white text-zinc-800">
                                            <?php foreach (['pending', 'processing', 'completed', 'partial', 'cancelled', 'refunded', 'failed'] as $stOpt): ?>
                                                <option value="<?= $stOpt ?>" <?= $o['status'] === $stOpt ? 'selected' : '' ?>><?= ucfirst($stOpt) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="p-4 border-t border-zinc-100 flex items-center justify-between text-xs text-zinc-500">
                <span>Page <?= $page ?> of <?= $totalPages ?> (<?= $total ?> total)</span>
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                        <a href="/admin/orders.php?status=<?= $status ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" class="px-3 py-1.5 rounded-xl border border-zinc-200 font-semibold">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="/admin/orders.php?status=<?= $status ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" class="px-3 py-1.5 rounded-xl border border-zinc-200 font-semibold">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
