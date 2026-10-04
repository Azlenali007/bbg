<?php
declare(strict_types=1);

$pageTitle = 'Categories';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Services/ServiceManager.php';

use App\Services\ServiceManager;

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? 'cube');
        $sort = (int)($_POST['sort_order'] ?? 0);
        if (!empty($name)) {
            ServiceManager::createCategory($name, $icon, $sort);
            flash('success', "Category '{$name}' created.");
            header('Location: /admin/categories.php');
            exit;
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            flash('success', "Category deleted.");
            header('Location: /admin/categories.php');
            exit;
        }
    }
}

$categories = ServiceManager::getCategories(false);

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="space-y-6" x-data="{ createModal: false }">

    <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">Service Categories</h2>
            <p class="text-xs text-zinc-500 mt-1">Organize services into parent groups and define display ordering.</p>
        </div>
        <button type="button" @click="createModal = true" class="px-5 py-2.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/25 flex items-center gap-1.5">
            <?= heroicon('plus', 'w-4 h-4') ?>
            <span>New Category</span>
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-zinc-200/80 shadow-sm overflow-hidden">
        <table class="w-full text-left text-xs">
            <thead class="bg-zinc-50 text-zinc-500 font-semibold border-b border-zinc-100">
                <tr>
                    <th class="py-3.5 px-6">ID</th>
                    <th class="py-3.5 px-6">Name</th>
                    <th class="py-3.5 px-6">Icon</th>
                    <th class="py-3.5 px-6">Services Count</th>
                    <th class="py-3.5 px-6">Sort Order</th>
                    <th class="py-3.5 px-6">Status</th>
                    <th class="py-3.5 px-6 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 text-zinc-700">
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-zinc-400">
                            No categories yet. Click "New Category" to add one.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr class="hover:bg-zinc-50">
                            <td class="py-4 px-6 font-mono font-bold text-purple-700">#<?= $cat['id'] ?></td>
                            <td class="py-4 px-6 font-bold text-zinc-900"><?= e($cat['name']) ?></td>
                            <td class="py-4 px-6 text-zinc-500 font-mono"><?= e($cat['icon']) ?></td>
                            <td class="py-4 px-6 font-semibold"><?= (int)$cat['service_count'] ?> services</td>
                            <td class="py-4 px-6 font-mono"><?= (int)$cat['sort_order'] ?></td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $cat['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' ?>">
                                    <?= ucfirst($cat['status']) ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <form action="/admin/categories.php" method="POST" onsubmit="return confirm('Delete this category?');" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                    <button type="submit" class="p-1.5 rounded-lg text-zinc-400 hover:text-rose-600 hover:bg-rose-50">
                                        <?= heroicon('trash', 'w-4 h-4') ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Create Modal -->
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-zinc-200 space-y-4" @click.away="createModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-zinc-100">
                <h3 class="text-base font-bold text-zinc-900">Add Service Category</h3>
                <button @click="createModal = false" class="text-zinc-400 hover:text-zinc-600">
                    <?= heroicon('x-mark', 'w-5 h-5') ?>
                </button>
            </div>
            <form action="/admin/categories.php" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="block text-xs font-bold uppercase text-zinc-700 mb-1">Category Name</label>
                    <input type="text" name="name" required placeholder="e.g. Instagram Followers" 
                           class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-zinc-700 mb-1">Heroicon Key</label>
                        <input type="text" name="icon" value="cube" placeholder="cube" 
                               class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-zinc-700 mb-1">Sort Order</label>
                        <input type="number" name="sort_order" value="0" 
                               class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                    </div>
                </div>
                <div class="pt-3 border-t border-zinc-100 flex items-center justify-end gap-2">
                    <button type="button" @click="createModal = false" class="px-4 py-2 rounded-xl border border-zinc-200 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 text-white text-xs font-bold shadow-md shadow-purple-600/25">Create</button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
