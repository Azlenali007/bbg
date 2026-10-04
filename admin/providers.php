<?php
declare(strict_types=1);

$pageTitle = 'API Providers';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/providers/provider-manager.php';

use Providers\ProviderManager;

$db = getDB();

// Handle balance sync action
if (isset($_GET['sync']) && (int)$_GET['sync'] > 0) {
    $syncId = (int)$_GET['sync'];
    if (ProviderManager::syncBalance($syncId)) {
        flash('success', "Balance synced successfully.");
    } else {
        flash('error', "Failed to sync balance. Check API credentials.");
    }
    header('Location: /admin/providers.php');
    exit;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $db->prepare("DELETE FROM providers WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', "Provider deleted.");
        header('Location: /admin/providers.php');
        exit;
    }
}

$providers = $db->query("SELECT * FROM providers ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="space-y-6">

    <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">API Providers</h2>
            <p class="text-xs text-zinc-500 mt-1">Connect upstream SMM providers (JustSMM, Peakerr, JAP, etc.) via Standard SMM v2 protocol.</p>
        </div>
        <a href="/admin/provider-add.php" class="px-5 py-2.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/25 flex items-center gap-1.5">
            <?= heroicon('plus', 'w-4 h-4') ?>
            <span>Add Provider</span>
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-zinc-200/80 shadow-sm overflow-hidden">
        <table class="w-full text-left text-xs">
            <thead class="bg-zinc-50 text-zinc-500 font-semibold border-b border-zinc-100">
                <tr>
                    <th class="py-3.5 px-6">ID</th>
                    <th class="py-3.5 px-6">Provider Name</th>
                    <th class="py-3.5 px-6">API Endpoint</th>
                    <th class="py-3.5 px-6">Provider Balance</th>
                    <th class="py-3.5 px-6">Last Sync</th>
                    <th class="py-3.5 px-6">Status</th>
                    <th class="py-3.5 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 text-zinc-700">
                <?php if (empty($providers)): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-zinc-400">
                            No API providers configured yet. Click "Add Provider" to connect your first upstream provider.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($providers as $p): ?>
                        <tr class="hover:bg-zinc-50">
                            <td class="py-4 px-6 font-mono font-bold text-purple-700">#<?= $p['id'] ?></td>
                            <td class="py-4 px-6 font-bold text-zinc-900"><?= e($p['name']) ?></td>
                            <td class="py-4 px-6 text-zinc-500 font-mono text-[11px] truncate max-w-xs"><?= e($p['api_url']) ?></td>
                            <td class="py-4 px-6 font-bold text-emerald-600 text-sm">
                                <?= formatCurrency((float)($p['balance'] ?? 0), $p['currency'] ?? 'USD') ?>
                            </td>
                            <td class="py-4 px-6 text-zinc-400 whitespace-nowrap">
                                <?= $p['last_sync'] ? timeAgo($p['last_sync']) : 'Never' ?>
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $p['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' ?>">
                                    <?= ucfirst($p['status']) ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="/admin/providers.php?sync=<?= $p['id'] ?>" class="p-1.5 rounded-lg text-zinc-400 hover:text-purple-600 hover:bg-purple-50" title="Sync live balance">
                                        <?= heroicon('arrow-path', 'w-4 h-4') ?>
                                    </a>
                                    <a href="/admin/provider-edit.php?id=<?= $p['id'] ?>" class="p-1.5 rounded-lg text-zinc-400 hover:text-purple-600 hover:bg-purple-50" title="Edit provider">
                                        <?= heroicon('pencil-square', 'w-4 h-4') ?>
                                    </a>
                                    <form action="/admin/providers.php" method="POST" onsubmit="return confirm('Delete provider #<?= $p['id'] ?>?');" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="p-1.5 rounded-lg text-zinc-400 hover:text-rose-600 hover:bg-rose-50" title="Delete">
                                            <?= heroicon('trash', 'w-4 h-4') ?>
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

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
