<?php
declare(strict_types=1);

$pageTitle = 'Hero Banners';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Banners/BannerManager.php';

use App\Banners\BannerManager;

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        BannerManager::createBanner([
            'title' => trim($_POST['title'] ?? ''),
            'subtitle' => trim($_POST['subtitle'] ?? ''),
            'image_url' => trim($_POST['image_url'] ?? ''),
            'cta_text' => trim($_POST['cta_text'] ?? 'Get Started'),
            'cta_url' => trim($_POST['cta_url'] ?? '/register.php'),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active' => !empty($_POST['is_active']) ? 1 : 0
        ]);
        flash('success', 'Banner created successfully.');
        header('Location: /admin/banners.php');
        exit;
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        BannerManager::deleteBanner($id);
        flash('success', 'Banner deleted.');
        header('Location: /admin/banners.php');
        exit;
    }
}

$banners = BannerManager::getAllBanners();

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="space-y-6" x-data="{ createModal: false }">

    <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">Hero Banners System</h2>
            <p class="text-xs text-zinc-500 mt-1">Manage landing page and dashboard hero promotional banners stored in MySQL.</p>
        </div>
        <button type="button" @click="createModal = true" class="px-5 py-2.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/25 flex items-center gap-1.5">
            <?= heroicon('plus', 'w-4 h-4') ?>
            <span>Create Banner</span>
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-zinc-200/80 shadow-sm overflow-hidden">
        <table class="w-full text-left text-xs">
            <thead class="bg-zinc-50 text-zinc-500 font-semibold border-b border-zinc-100">
                <tr>
                    <th class="py-3.5 px-6">ID</th>
                    <th class="py-3.5 px-6">Title</th>
                    <th class="py-3.5 px-6">Subtitle</th>
                    <th class="py-3.5 px-6">CTA</th>
                    <th class="py-3.5 px-6">Sort</th>
                    <th class="py-3.5 px-6">Status</th>
                    <th class="py-3.5 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 text-zinc-700">
                <?php if (empty($banners)): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-zinc-400">
                            No banners configured yet. When empty, the system displays the default branded hero section.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($banners as $b): ?>
                        <tr class="hover:bg-zinc-50">
                            <td class="py-4 px-6 font-mono font-bold text-purple-700">#<?= $b['id'] ?></td>
                            <td class="py-4 px-6 font-bold text-zinc-900"><?= e($b['title']) ?></td>
                            <td class="py-4 px-6 text-zinc-500 max-w-xs truncate"><?= e($b['subtitle']) ?></td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-700 font-medium text-[11px]"><?= e($b['cta_text']) ?></span>
                            </td>
                            <td class="py-4 px-6 font-mono"><?= (int)$b['sort_order'] ?></td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $b['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' ?>">
                                    <?= $b['is_active'] ? 'Active' : 'Disabled' ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <form action="/admin/banners.php" method="POST" onsubmit="return confirm('Delete this banner?');" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
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
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-zinc-200 space-y-4" @click.away="createModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-zinc-100">
                <h3 class="text-base font-bold text-zinc-900">Add Hero Banner</h3>
                <button @click="createModal = false" class="text-zinc-400 hover:text-zinc-600">
                    <?= heroicon('x-mark', 'w-5 h-5') ?>
                </button>
            </div>
            <form action="/admin/banners.php" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">

                <div>
                    <label class="block text-xs font-bold uppercase text-zinc-700 mb-1">Banner Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Exclusive High-Retention Instagram Campaign" 
                           class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-zinc-700 mb-1">Subtitle / Message</label>
                    <textarea name="subtitle" rows="2" placeholder="Instant automated execution with 30-day refill guarantee." 
                              class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-zinc-700 mb-1">CTA Button Text</label>
                        <input type="text" name="cta_text" value="Get Started" 
                               class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-zinc-700 mb-1">CTA Destination URL</label>
                        <input type="text" name="cta_url" value="/register.php" 
                               class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-zinc-700 mb-1">Sort Order</label>
                        <input type="number" name="sort_order" value="0" 
                               class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                    </div>
                    <div class="flex items-center pt-6">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-zinc-800">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded text-purple-600 focus:ring-purple-500">
                            <span>Active & Displaying</span>
                        </label>
                    </div>
                </div>

                <div class="pt-3 border-t border-zinc-100 flex items-center justify-end gap-2">
                    <button type="button" @click="createModal = false" class="px-4 py-2 rounded-xl border border-zinc-200 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 text-white text-xs font-bold shadow-md shadow-purple-600/25">Save Banner</button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
