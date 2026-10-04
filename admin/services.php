<?php
declare(strict_types=1);

$pageTitle = 'Services Management';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Services/ServiceManager.php';

use App\Services\ServiceManager;

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        ServiceManager::deleteService($id);
        flash('success', "Service #{$id} deleted successfully.");
        header('Location: /admin/services.php');
        exit;
    }
}

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = trim($_GET['search'] ?? '');

$categories = ServiceManager::getCategories(false);
$services = ServiceManager::getActiveServices($categoryId > 0 ? $categoryId : null, $search ?: null);

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="space-y-6">

    <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">Services Management</h2>
            <p class="text-xs text-zinc-500 mt-1">Configure pricing rates, limits, provider mappings, and active statuses.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="/admin/service-import.php" class="px-4 py-2.5 rounded-2xl bg-zinc-100 hover:bg-zinc-200 text-zinc-800 text-xs font-bold transition-colors flex items-center gap-1.5">
                <?= heroicon('arrow-down-tray', 'w-4 h-4 text-zinc-600') ?>
                <span>Import via API</span>
            </a>
            <a href="/admin/service-add.php" class="px-4 py-2.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/25 transition-all flex items-center gap-1.5">
                <?= heroicon('plus', 'w-4 h-4') ?>
                <span>Add Service</span>
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-2xl border border-zinc-200/80 flex flex-wrap items-center gap-3">
        <form action="/admin/services.php" method="GET" class="flex flex-wrap items-center gap-3 w-full">
            <div class="relative flex-1 min-w-[200px]">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name or ID..." 
                       class="w-full pl-9 pr-4 py-2 rounded-xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                    <?= heroicon('magnifying-glass', 'w-4 h-4') ?>
                </div>
            </div>

            <select name="category" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-zinc-200 bg-zinc-50 text-xs font-semibold text-zinc-900">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-bold">Filter</button>
            <?php if ($search || $categoryId > 0): ?>
                <a href="/admin/services.php" class="px-3 py-2 rounded-xl border border-zinc-200 text-xs text-zinc-500 hover:bg-zinc-50">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Services Table -->
    <div class="bg-white rounded-3xl border border-zinc-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50 text-zinc-500 font-semibold border-b border-zinc-100">
                    <tr>
                        <th class="py-3.5 px-6">ID</th>
                        <th class="py-3.5 px-6">Category</th>
                        <th class="py-3.5 px-6">Name</th>
                        <th class="py-3.5 px-6">Provider</th>
                        <th class="py-3.5 px-6">Selling Rate</th>
                        <th class="py-3.5 px-6">Min / Max</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-700">
                    <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center text-zinc-400">
                                No services found. Click "Add Service" or "Import via API" to populate.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($services as $s): ?>
                            <tr class="hover:bg-zinc-50/80">
                                <td class="py-3.5 px-6 font-mono font-bold text-purple-700">#<?= $s['id'] ?></td>
                                <td class="py-3.5 px-6 text-zinc-500"><?= e($s['category_name']) ?></td>
                                <td class="py-3.5 px-6 font-bold text-zinc-900 max-w-xs truncate"><?= e($s['name']) ?></td>
                                <td class="py-3.5 px-6 text-zinc-500 font-mono text-[11px]">
                                    <?= !empty($s['provider_name']) ? e($s['provider_name']) . " (#{$s['provider_service_id']})" : '<span class="text-zinc-400">Manual</span>' ?>
                                </td>
                                <td class="py-3.5 px-6 font-bold text-purple-700"><?= formatCurrency($s['rate']) ?></td>
                                <td class="py-3.5 px-6"><?= number_format($s['min_quantity']) ?> - <?= number_format($s['max_quantity']) ?></td>
                                <td class="py-3.5 px-6">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $s['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' ?>">
                                        <?= ucfirst($s['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="/admin/service-edit.php?id=<?= $s['id'] ?>" class="p-1.5 rounded-lg text-zinc-400 hover:text-purple-600 hover:bg-purple-50">
                                            <?= heroicon('pencil-square', 'w-4 h-4') ?>
                                        </a>
                                        <form action="/admin/services.php" method="POST" onsubmit="return confirm('Delete service #<?= $s['id'] ?>?');" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="p-1.5 rounded-lg text-zinc-400 hover:text-rose-600 hover:bg-rose-50">
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

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
