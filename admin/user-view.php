<?php
declare(strict_types=1);

$pageTitle = 'User Details';
require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Wallet/WalletManager.php';

use App\Wallet\WalletManager;

$db = getDB();
$userId = (int)($_GET['id'] ?? 0);

if (!$userId) {
    flash('danger', 'Invalid user ID.');
    header('Location: /admin/users.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$viewUser = $stmt->fetch();

if (!$viewUser) {
    flash('danger', 'User not found.');
    header('Location: /admin/users.php');
    exit;
}

// Handle Form Submissions
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $role = $_POST['role'] ?? 'user';
        $newPass = $_POST['new_password'] ?? '';

        if (!in_array($status, ['active', 'banned', 'suspended'], true)) {
            $error = 'Invalid user status.';
        } elseif (!in_array($role, ['user', 'admin'], true)) {
            $error = 'Invalid role.';
        } else {
            try {
                if (!empty($newPass)) {
                    $hash = password_hash($newPass, PASSWORD_DEFAULT);
                    $upStmt = $db->prepare("UPDATE users SET username = ?, email = ?, status = ?, role = ?, password = ? WHERE id = ?");
                    $upStmt->execute([$username, $email, $status, $role, $hash, $userId]);
                } else {
                    $upStmt = $db->prepare("UPDATE users SET username = ?, email = ?, status = ?, role = ? WHERE id = ?");
                    $upStmt->execute([$username, $email, $status, $role, $userId]);
                }
                logAudit($adminId, true, 'update_user', "Updated user #{$userId} ($username)");
                flash('success', 'User profile updated successfully.');
                header("Location: /admin/user-view.php?id={$userId}");
                exit;
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'adjust_balance') {
        $type = $_POST['type'] ?? 'add';
        $amount = (float)($_POST['amount'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Admin manual balance adjustment');

        if ($amount <= 0) {
            $error = 'Adjustment amount must be greater than zero.';
        } else {
            try {
                if ($type === 'add') {
                    WalletManager::addFunds($userId, $amount, 'manual', 'ADMIN-' . time(), $reason);
                    logAudit($adminId, true, 'balance_credit', "Added " . formatCurrency($amount) . " to user #{$userId}");
                    flash('success', 'Funds successfully credited to user wallet.');
                } else {
                    WalletManager::deductFunds($userId, $amount, 'manual_deduct', $reason);
                    logAudit($adminId, true, 'balance_debit', "Deducted " . formatCurrency($amount) . " from user #{$userId}");
                    flash('success', 'Funds successfully deducted from user wallet.');
                }
                header("Location: /admin/user-view.php?id={$userId}");
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
    }
}

// Fetch user orders
$orderStmt = $db->prepare("
    SELECT o.*, s.name as service_name 
    FROM orders o 
    LEFT JOIN services s ON o.service_id = s.id 
    WHERE o.user_id = ? 
    ORDER BY o.id DESC LIMIT 10
");
$orderStmt->execute([$userId]);
$userOrders = $orderStmt->fetchAll();

// Fetch user transactions
$txStmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC LIMIT 10");
$txStmt->execute([$userId]);
$userTx = $txStmt->fetchAll();
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="/admin/users.php" class="text-xs font-semibold text-purple-600 hover:text-purple-700 flex items-center gap-1">
                    <?= heroicon('arrow-left', 'w-3.5 h-3.5') ?> Back to Users
                </a>
            </div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight mt-1">User #<?= $userId ?>: <?= e($viewUser['username']) ?></h1>
            <p class="text-xs text-zinc-500">Registered on <?= date('M d, Y H:i', strtotime($viewUser['created_at'])) ?></p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 text-xs font-bold rounded-full <?= $viewUser['status'] === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                Status: <?= ucfirst($viewUser['status']) ?>
            </span>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- User KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-5 shadow-sm">
            <span class="text-xs font-semibold text-zinc-500">Current Balance</span>
            <div class="text-2xl font-black text-zinc-900 mt-1"><?= formatCurrency($viewUser['balance']) ?></div>
        </div>
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-5 shadow-sm">
            <span class="text-xs font-semibold text-zinc-500">Total Spent</span>
            <div class="text-2xl font-black text-zinc-900 mt-1"><?= formatCurrency($viewUser['spent']) ?></div>
        </div>
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-5 shadow-sm">
            <span class="text-xs font-semibold text-zinc-500">API Key</span>
            <div class="text-xs font-mono text-zinc-600 truncate mt-2 bg-slate-50 p-2 rounded-lg border border-zinc-200">
                <?= $viewUser['api_key'] ? e($viewUser['api_key']) : 'None' ?>
            </div>
        </div>
    </div>

    <!-- Management Forms Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Edit Profile -->
        <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm">
            <h2 class="text-base font-bold text-zinc-900 mb-4 flex items-center gap-2">
                <?= heroicon('user', 'w-5 h-5 text-purple-600') ?> Edit User Profile
            </h2>
            <form action="" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">

                <div>
                    <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">Username</label>
                    <input type="text" name="username" value="<?= e($viewUser['username']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 text-xs focus:outline-none focus:border-purple-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">Email</label>
                    <input type="email" name="email" value="<?= e($viewUser['email']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 text-xs focus:outline-none focus:border-purple-600">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">Status</label>
                        <select name="status" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 text-xs focus:outline-none focus:border-purple-600">
                            <option value="active" <?= $viewUser['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="suspended" <?= $viewUser['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            <option value="banned" <?= $viewUser['status'] === 'banned' ? 'selected' : '' ?>>Banned</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">Role</label>
                        <select name="role" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 text-xs focus:outline-none focus:border-purple-600">
                            <option value="user" <?= $viewUser['role'] === 'user' ? 'selected' : '' ?>>User</option>
                            <option value="admin" <?= $viewUser['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">Reset Password (leave empty to keep current)</label>
                    <input type="password" name="new_password" placeholder="New password..." class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 text-xs focus:outline-none focus:border-purple-600">
                </div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-md shadow-purple-600/20 transition-all">
                    Save Changes
                </button>
            </form>
        </div>

        <!-- Balance Adjustment -->
        <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm">
            <h2 class="text-base font-bold text-zinc-900 mb-4 flex items-center gap-2">
                <?= heroicon('wallet', 'w-5 h-5 text-purple-600') ?> Adjust Balance
            </h2>
            <form action="" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="adjust_balance">

                <div>
                    <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">Operation</label>
                    <select name="type" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 text-xs focus:outline-none focus:border-purple-600">
                        <option value="add">Add Funds (Credit)</option>
                        <option value="deduct">Deduct Funds (Debit)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">Amount (<?= CURRENCY ?>)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" required placeholder="10.00" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 text-xs focus:outline-none focus:border-purple-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">Reason / Note</label>
                    <input type="text" name="reason" placeholder="e.g. Compensation for order refund" required class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 text-xs focus:outline-none focus:border-purple-600">
                </div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-white font-bold text-xs shadow-md transition-all">
                    Execute Balance Adjustment
                </button>
            </form>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm">
        <h2 class="text-base font-bold text-zinc-900 mb-4 flex items-center gap-2">
            <?= heroicon('shopping-cart', 'w-5 h-5 text-purple-600') ?> Recent Orders by User
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50 border-b border-zinc-200 text-zinc-500 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="p-3">Order ID</th>
                        <th class="p-3">Service</th>
                        <th class="p-3">Quantity</th>
                        <th class="p-3">Charge</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Date</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php if (empty($userOrders)): ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-zinc-400">No orders found for this user.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($userOrders as $o): ?>
                            <tr class="hover:bg-zinc-50/50">
                                <td class="p-3 font-mono font-bold text-purple-600">#<?= $o['id'] ?></td>
                                <td class="p-3 font-semibold text-zinc-800 truncate max-w-xs"><?= e($o['service_name'] ?? 'Custom Service') ?></td>
                                <td class="p-3"><?= number_format($o['quantity']) ?></td>
                                <td class="p-3 font-semibold text-zinc-900"><?= formatCurrency($o['charge']) ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full <?= $o['status'] === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                                        <?= ucfirst($o['status']) ?>
                                    </span>
                                </td>
                                <td class="p-3 text-zinc-400"><?= date('M d, H:i', strtotime($o['created_at'])) ?></td>
                                <td class="p-3 text-right">
                                    <a href="/admin/order-view.php?id=<?= $o['id'] ?>" class="text-purple-600 hover:text-purple-800 font-bold">View</a>
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
