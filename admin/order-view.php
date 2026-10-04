<?php
declare(strict_types=1);

$pageTitle = 'Order Details';
require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Orders/OrderManager.php';
require_once BASE_PATH . '/app/Wallet/WalletManager.php';

use App\Orders\OrderManager;
use App\Wallet\WalletManager;

$db = getDB();
$orderId = (int)($_GET['id'] ?? 0);

if (!$orderId) {
    flash('danger', 'Invalid order ID.');
    header('Location: /admin/orders.php');
    exit;
}

$stmt = $db->prepare("
    SELECT o.*, u.username as user_name, u.email as user_email, s.name as service_name, p.name as provider_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN services s ON o.service_id = s.id
    LEFT JOIN providers p ON o.provider_id = p.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    flash('danger', 'Order not found.');
    header('Location: /admin/orders.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_order') {
        $status = $_POST['status'] ?? $order['status'];
        $startCount = (int)($_POST['start_counter'] ?? $order['start_counter']);
        $remains = (int)($_POST['remains'] ?? $order['remains']);

        $allowedStatuses = ['pending', 'processing', 'inprogress', 'completed', 'partial', 'canceled', 'refunded', 'failed'];
        if (!in_array($status, $allowedStatuses, true)) {
            $error = 'Invalid status selected.';
        } else {
            try {
                // If transitioning to canceled or refunded, auto refund user if not refunded yet
                if (in_array($status, ['canceled', 'refunded'], true) && !in_array($order['status'], ['canceled', 'refunded'], true)) {
                    WalletManager::refundOrder((int)$order['user_id'], (int)$order['id'], (float)$order['charge'], "Admin marked order #{$orderId} as {$status}");
                }

                $upStmt = $db->prepare("
                    UPDATE orders 
                    SET status = ?, start_counter = ?, remains = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $upStmt->execute([$status, $startCount, $remains, $orderId]);

                logAudit($adminId, true, 'update_order', "Updated order #{$orderId} status to {$status}");
                flash('success', 'Order updated successfully.');
                header("Location: /admin/order-view.php?id={$orderId}");
                exit;
            } catch (Throwable $e) {
                $error = 'Error updating order: ' . $e->getMessage();
            }
        }
    }
}
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="/admin/orders.php" class="text-xs font-semibold text-purple-600 hover:text-purple-700 flex items-center gap-1">
                    <?= heroicon('arrow-left', 'w-3.5 h-3.5') ?> Back to Orders
                </a>
            </div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight mt-1">Order #<?= $order['id'] ?></h1>
            <p class="text-xs text-zinc-500">Placed on <?= date('M d, Y H:i:s', strtotime($order['created_at'])) ?></p>
        </div>
        <div>
            <span class="px-3.5 py-1.5 text-xs font-bold rounded-full <?= $order['status'] === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-purple-100 text-purple-800' ?>">
                Status: <?= ucfirst($order['status']) ?>
            </span>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Order Details -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-400">Order Information</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-zinc-100">
                        <span class="text-zinc-400 font-semibold block mb-1">Service</span>
                        <span class="font-bold text-zinc-900"><?= e($order['service_name'] ?? 'Unknown Service') ?></span>
                    </div>
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-zinc-100">
                        <span class="text-zinc-400 font-semibold block mb-1">Customer</span>
                        <a href="/admin/user-view.php?id=<?= $order['user_id'] ?>" class="font-bold text-purple-600 hover:underline">
                            <?= e($order['user_name']) ?> (#<?= $order['user_id'] ?>)
                        </a>
                    </div>
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-zinc-100">
                        <span class="text-zinc-400 font-semibold block mb-1">Quantity</span>
                        <span class="font-bold text-zinc-900"><?= number_format($order['quantity']) ?></span>
                    </div>
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-zinc-100">
                        <span class="text-zinc-400 font-semibold block mb-1">Total Charge</span>
                        <span class="font-bold text-zinc-900 text-sm"><?= formatCurrency($order['charge']) ?></span>
                    </div>
                </div>

                <div class="bg-slate-50 p-3.5 rounded-2xl border border-zinc-100 text-xs">
                    <span class="text-zinc-400 font-semibold block mb-1">Target Link</span>
                    <a href="<?= e($order['link']) ?>" target="_blank" rel="noopener noreferrer" class="font-mono text-purple-600 hover:underline break-all">
                        <?= e($order['link']) ?> &nearr;
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-zinc-100">
                        <span class="text-zinc-400 font-semibold block mb-1">Provider</span>
                        <span class="font-bold text-zinc-900"><?= e($order['provider_name'] ?? 'Direct / None') ?></span>
                    </div>
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-zinc-100">
                        <span class="text-zinc-400 font-semibold block mb-1">Provider Order ID</span>
                        <span class="font-mono text-zinc-800"><?= e($order['provider_order_id'] ?? 'N/A') ?></span>
                    </div>
                </div>

                <?php if (!empty($order['provider_response'])): ?>
                    <div class="bg-slate-900 text-emerald-400 p-4 rounded-2xl text-xs font-mono overflow-x-auto">
                        <span class="text-zinc-400 block mb-1 text-[10px] uppercase font-bold tracking-wider">Raw Provider API Response</span>
                        <?= e($order['provider_response']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Action Control Panel -->
        <div class="space-y-6">
            <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-400">Order Management</h2>
                
                <form action="" method="POST" class="space-y-4 text-xs">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_order">

                    <div>
                        <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Status</label>
                        <select name="status" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                            <?php foreach (['pending', 'processing', 'inprogress', 'completed', 'partial', 'canceled', 'refunded', 'failed'] as $st): ?>
                                <option value="<?= $st ?>" <?= $order['status'] === $st ? 'selected' : '' ?>>
                                    <?= ucfirst($st) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-[10px] text-zinc-400 mt-1">Selecting "Canceled" or "Refunded" will automatically credit the user's wallet.</p>
                    </div>

                    <div>
                        <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Start Counter</label>
                        <input type="number" name="start_counter" value="<?= (int)$order['start_counter'] ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600">
                    </div>

                    <div>
                        <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Remains</label>
                        <input type="number" name="remains" value="<?= (int)$order['remains'] ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600">
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold shadow-md shadow-purple-600/20 transition-all">
                        Update Order
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
