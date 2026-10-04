<?php
declare(strict_types=1);

$pageTitle = 'Services & Rates';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Services/ServiceManager.php';

use App\Services\ServiceManager;

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = trim($_GET['search'] ?? '');

$categories = ServiceManager::getCategories(true);
$services = ServiceManager::getActiveServices($categoryId > 0 ? $categoryId : null, $search ?: null);
?>

<div class="py-12 bg-slate-50 min-h-[80vh]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-zinc-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-zinc-900 tracking-tight">Public Service Catalog</h1>
                <p class="text-xs text-zinc-500 mt-1">Live rates per 1,000 units with complete delivery specifications and minimum thresholds.</p>
            </div>

            <form action="/services.php" method="GET" class="flex flex-wrap items-center gap-3">
                <div class="relative">
                    <input type="text" 
                           name="search" 
                           value="<?= e($search) ?>" 
                           placeholder="Search services..." 
                           class="pl-9 pr-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 focus:ring-4 focus:ring-purple-600/10 transition-all w-52 sm:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                        <?= heroicon('magnifying-glass', 'w-4 h-4') ?>
                    </div>
                </div>

                <select name="category" 
                        onchange="this.form.submit()"
                        class="px-3.5 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs font-semibold text-zinc-900 focus:bg-white focus:border-purple-600 transition-all">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?> (<?= (int)$cat['service_count'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if ($search || $categoryId > 0): ?>
                    <a href="/services.php" class="p-2.5 rounded-xl border border-zinc-200 hover:bg-zinc-50 text-zinc-500 text-xs">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-3xl border border-zinc-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-50/80 text-zinc-500 font-semibold border-b border-zinc-100">
                        <tr>
                            <th class="py-3.5 px-6">ID</th>
                            <th class="py-3.5 px-6">Category</th>
                            <th class="py-3.5 px-6">Service Name</th>
                            <th class="py-3.5 px-6">Rate / 1k</th>
                            <th class="py-3.5 px-6">Min / Max</th>
                            <th class="py-3.5 px-6">Coverage</th>
                            <th class="py-3.5 px-6 text-right">Order</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-zinc-700">
                        <?php if (empty($services)): ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-zinc-400">
                                    <div class="w-10 h-10 rounded-2xl bg-zinc-100 text-zinc-400 flex items-center justify-center mx-auto mb-2">
                                        <?= heroicon('cube', 'w-5 h-5') ?>
                                    </div>
                                    <p class="text-xs font-medium text-zinc-600">No services found</p>
                                    <p class="text-[11px] text-zinc-400 mt-0.5">Please check back shortly or adjust search filters.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($services as $s): ?>
                                <tr class="hover:bg-purple-50/20 transition-colors">
                                    <td class="py-4 px-6 font-mono font-bold text-purple-700">#<?= $s['id'] ?></td>
                                    <td class="py-4 px-6">
                                        <span class="inline-block px-2 py-0.5 rounded-lg bg-zinc-100 text-zinc-600 text-[10px] font-semibold">
                                            <?= e($s['category_name']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 max-w-sm">
                                        <span class="font-bold text-zinc-900 block"><?= e($s['name']) ?></span>
                                        <?php if (!empty($s['description'])): ?>
                                            <p class="text-[11px] text-zinc-400 line-clamp-1 mt-0.5"><?= e($s['description']) ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="font-bold text-sm text-purple-700"><?= formatCurrency($s['rate']) ?></span>
                                    </td>
                                    <td class="py-4 px-6 font-medium text-zinc-600 whitespace-nowrap">
                                        <?= number_format($s['min_quantity']) ?> &ndash; <?= number_format($s['max_quantity']) ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <?php if ($s['refill_supported']): ?>
                                                <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[10px] font-bold">Refill</span>
                                            <?php endif; ?>
                                            <?php if ($s['cancel_supported']): ?>
                                                <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-700 text-[10px] font-bold">Cancel</span>
                                            <?php endif; ?>
                                            <?php if ($s['dripfeed_supported']): ?>
                                                <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 text-[10px] font-bold">Drip-feed</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <a href="/login.php" 
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-sm shadow-purple-600/20 transition-all">
                                            <span>Order</span>
                                            <?= heroicon('chevron-right', 'w-3 h-3') ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
