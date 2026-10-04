<?php
declare(strict_types=1);

$pageTitle = 'Users Management';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Wallet/WalletManager.php';

use App\Wallet\WalletManager;

$db = getDB();

// Handle balance adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'adjust_balance') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $type = $_POST['type'] ?? 'credit';
        $reason = trim($_POST['reason'] ?? 'Admin balance adjustment');

        if ($targetUserId > 0 && $amount > 0) {
            if ($type === 'credit') {
                WalletManager::credit($targetUserId, $amount, $reason, 'admin', (string)$adminId, 'bonus');
                flash('success', "Credited " . formatCurrency($amount) . " to user #{$targetUserId}.");
            } else {
                WalletManager::debit($targetUserId, $amount, $reason, 'admin', (string)$adminId);
                flash('success', "Debited " . formatCurrency($amount) . " from user #{$targetUserId}.");
            }
            header('Location: /admin/users.php');
            exit;
        }
    } elseif ($action === 'toggle_ban') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        if ($targetUserId > 0) {
            $stmt = $db->prepare("UPDATE users SET status = IF(status = 'active', 'banned', 'active') WHERE id = ?");
            $stmt->execute([$targetUserId]);
            flash('success', "User #{$targetUserId} status toggled.");
            header('Location: /admin/users.php');
            exit;
        }
    }
}

$search = trim($_GET['search'] ?? '');
$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(username LIKE ? OR email LIKE ? OR id = ?)";
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = is_numeric($search) ? (int)$search : 0;
}

$whereClause = implode(' AND ', $where);
$stmt = $db->prepare("
    SELECT u.*, w.balance as wallet_balance, w.total_spent, w.total_deposited 
    FROM users u
    LEFT JOIN wallets w ON u.id = w.user_id
    WHERE {$whereClause}
    ORDER BY u.id DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="space-y-6" x-data="{ adjustModal: false, selectedUser: null }">

    <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">Users Directory</h2>
            <p class="text-xs text-zinc-500 mt-1">Registered clients, wallet balances, account statuses, and balance overrides.</p>
        </div>

        <form action="/admin/users.php" method="GET" class="flex items-center gap-2">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search username, email, ID..." 
                   class="px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 w-52 sm:w-64">
            <button type="submit" class="px-4 py-2.5 rounded-2xl bg-purple-600 text-white text-xs font-bold">Search</button>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-3xl border border-zinc-200/80 shadow-sm overflow-hidden">
        <table class="w-full text-left text-xs">
            <thead class="bg-zinc-50 text-zinc-500 font-semibold border-b border-zinc-100">
                <tr>
                    <th class="py-3.5 px-6">ID</th>
                    <th class="py-3.5 px-6">Username</th>
                    <th class="py-3.5 px-6">Email</th>
                    <th class="py-3.5 px-6">Wallet Balance</th>
                    <th class="py-3.5 px-6">Total Spent</th>
                    <th class="py-3.5 px-6">Status</th>
                    <th class="py-3.5 px-6">Registered</th>
                    <th class="py-3.5 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 text-zinc-700">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="py-12 text-center text-zinc-400">
                            No users found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-zinc-50">
                            <td class="py-4 px-6 font-mono font-bold text-purple-700">#<?= $u['id'] ?></td>
                            <td class="py-4 px-6 font-bold text-zinc-900"><?= e($u['username']) ?></td>
                            <td class="py-4 px-6 text-zinc-500"><?= e($u['email']) ?></td>
                            <td class="py-4 px-6 font-black text-purple-700 text-sm">
                                <?= formatCurrency((float)($u['wallet_balance'] ?? 0)) ?>
                            </td>
                            <td class="py-4 px-6 text-zinc-600 font-medium">
                                <?= formatCurrency((float)($u['total_spent'] ?? 0)) ?>
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $u['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
                                    <?= ucfirst($u['status']) ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-zinc-400 whitespace-nowrap">
                                <?= date('M j, Y', strtotime($u['created_at'])) ?>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" 
                                            @click="selectedUser = <?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>; adjustModal = true"
                                            class="px-2.5 py-1 rounded-xl bg-purple-50 text-purple-700 hover:bg-purple-100 font-semibold text-[11px]">
                                        Funds
                                    </button>
                                    <form action="/admin/users.php" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_ban">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="px-2.5 py-1 rounded-xl border border-zinc-200 hover:bg-zinc-50 text-[11px] font-semibold <?= $u['status'] === 'active' ? 'text-rose-600' : 'text-emerald-600' ?>">
                                            <?= $u['status'] === 'active' ? 'Ban' : 'Unban' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Adjust Funds Modal -->
    <div x-show="adjustModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-zinc-200 space-y-4" @click.away="adjustModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-zinc-100">
                <div>
                    <h3 class="text-base font-bold text-zinc-900">Adjust User Balance</h3>
                    <p class="text-xs text-zinc-500" x-text="selectedUser ? selectedUser.username + ' (#' + selectedUser.id + ')' : ''"></p>
                </div>
                <button @click="adjustModal = false" class="text-zinc-400 hover:text-zinc-600">
                    <?= heroicon('x-mark', 'w-5 h-5') ?>
                </button>
            </div>

            <form action="/admin/users.php" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="adjust_balance">
                <input type="hidden" name="user_id" :value="selectedUser ? selectedUser.id : ''">

                <div>
                    <label class="block text-xs font-bold uppercase text-zinc-700 mb-1">Adjustment Type</label>
                    <select name="type" class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 font-semibold">
                        <option value="credit">Credit (+) Add to balance</option>
                        <option value="debit">Debit (-) Deduct from balance</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-zinc-700 mb-1">Amount (USD)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="50.00" 
                           class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-zinc-700 mb-1">Reason / Description</label>
                    <input type="text" name="reason" required placeholder="e.g. Manual payment received / Bonus" 
                           class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900">
                </div>

                <div class="pt-3 border-t border-zinc-100 flex items-center justify-end gap-2">
                    <button type="button" @click="adjustModal = false" class="px-4 py-2 rounded-xl border border-zinc-200 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 text-white text-xs font-bold shadow-md shadow-purple-600/25">Apply Adjustment</button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
