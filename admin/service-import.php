<?php
declare(strict_types=1);

$pageTitle = 'Import Services via API';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Services/ServiceManager.php';
require_once BASE_PATH . '/providers/provider-manager.php';

use App\Services\ServiceManager;
use Providers\ProviderManager;

$db = getDB();
$providers = $db->query("SELECT * FROM providers WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$categories = ServiceManager::getCategories(false);

$selectedProviderId = (int)($_GET['provider_id'] ?? 0);
$providerServices = [];
$fetchError = null;
$importSuccess = null;

if ($selectedProviderId > 0) {
    $res = ProviderManager::fetchServices($selectedProviderId);
    if ($res['success']) {
        $providerServices = $res['services'];
    } else {
        $fetchError = $res['message'];
    }
}

// Handle Import POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import') {
    require_csrf();
    $importedCount = 0;
    $selectedServices = $_POST['services'] ?? [];
    $marginPercent = (float)($_POST['margin_percent'] ?? 50);

    foreach ($selectedServices as $pServiceId => $item) {
        if (empty($item['selected'])) continue;

        $pRate = (float)($item['rate'] ?? 0);
        $sellingRate = round($pRate * (1 + ($marginPercent / 100)), 4);
        if ($sellingRate <= 0) $sellingRate = $pRate;

        $categoryName = trim($item['category'] ?? 'General Services');
        
        // Find or create category
        $catStmt = $db->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
        $catStmt->execute([$categoryName]);
        $catId = $catStmt->fetchColumn();
        if (!$catId) {
            $catId = ServiceManager::createCategory($categoryName);
        }

        // Insert service
        ServiceManager::createService([
            'category_id' => (int)$catId,
            'provider_id' => $selectedProviderId,
            'provider_service_id' => (string)$pServiceId,
            'name' => trim($item['name'] ?? "Service #{$pServiceId}"),
            'type' => $item['type'] ?? 'default',
            'rate' => $sellingRate,
            'original_rate' => $pRate,
            'min_quantity' => (int)($item['min'] ?? 10),
            'max_quantity' => (int)($item['max'] ?? 10000),
            'refill_supported' => !empty($item['refill']) ? 1 : 0,
            'cancel_supported' => !empty($item['cancel']) ? 1 : 0,
            'description' => "Imported from provider API.\nOriginal Provider Rate: \${$pRate}",
            'status' => 'active'
        ]);
        $importedCount++;
    }

    if ($importedCount > 0) {
        flash('success', "Successfully imported {$importedCount} services with {$marginPercent}% markup.");
        header('Location: /admin/services.php');
        exit;
    }
}

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="space-y-6">

    <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">API Service Importer</h2>
            <p class="text-xs text-zinc-500 mt-1">Connect directly to an integrated provider, preview catalog rates, and auto-import with custom profit margins.</p>
        </div>
        <a href="/admin/services.php" class="text-xs font-semibold text-zinc-500 hover:text-purple-600 flex items-center gap-1">
            <?= heroicon('arrow-left-on-rectangle', 'w-4 h-4') ?>
            <span>Back to Services</span>
        </a>
    </div>

    <!-- Provider Selection -->
    <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm space-y-4">
        <form action="/admin/service-import.php" method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[240px]">
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Select SMM Provider</label>
                <select name="provider_id" onchange="this.form.submit()" class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 font-semibold focus:bg-white focus:border-purple-600">
                    <option value="">-- Choose Connected Provider --</option>
                    <?php foreach ($providers as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $selectedProviderId === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= e($p['name']) ?> (<?= e($p['api_url']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if ($fetchError): ?>
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">
                <?= e($fetchError) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Provider Services Table -->
    <?php if (!empty($providerServices)): ?>
        <form action="/admin/service-import.php?provider_id=<?= $selectedProviderId ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="import">

            <div class="bg-white rounded-3xl border border-zinc-200/80 shadow-sm overflow-hidden space-y-4">
                <div class="p-6 border-b border-zinc-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-bold text-zinc-900">Provider Service Feed (<?= count($providerServices) ?> Services)</h3>
                        <p class="text-xs text-zinc-400">Select services to import and configure profit markup percentage.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-zinc-700">Profit Markup:</label>
                            <input type="number" name="margin_percent" value="50" min="0" max="1000" class="w-20 px-2.5 py-1.5 rounded-xl border border-zinc-200 text-xs font-bold text-center">
                            <span class="text-xs font-bold text-zinc-500">%</span>
                        </div>
                        <button type="submit" class="px-5 py-2.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/25">
                            Import Selected
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-zinc-50 text-zinc-500 font-semibold border-b border-zinc-100 sticky top-0 z-10">
                            <tr>
                                <th class="py-3 px-6"><input type="checkbox" onclick="document.querySelectorAll('.svc-cb').forEach(c => c.checked = this.checked)" class="rounded text-purple-600"></th>
                                <th class="py-3 px-6">ID</th>
                                <th class="py-3 px-6">Category</th>
                                <th class="py-3 px-6">Name</th>
                                <th class="py-3 px-6">Provider Rate</th>
                                <th class="py-3 px-6">Min / Max</th>
                                <th class="py-3 px-6">Refill</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 text-zinc-700">
                            <?php foreach ($providerServices as $ps): ?>
                                <?php 
                                $psId = $ps['service'] ?? ($ps['id'] ?? 0);
                                $psRate = (float)($ps['rate'] ?? 0);
                                ?>
                                <tr class="hover:bg-zinc-50">
                                    <td class="py-3 px-6">
                                        <input type="checkbox" name="services[<?= $psId ?>][selected]" value="1" class="svc-cb rounded text-purple-600">
                                        <input type="hidden" name="services[<?= $psId ?>][name]" value="<?= e($ps['name'] ?? '') ?>">
                                        <input type="hidden" name="services[<?= $psId ?>][category]" value="<?= e($ps['category'] ?? 'General') ?>">
                                        <input type="hidden" name="services[<?= $psId ?>][rate]" value="<?= $psRate ?>">
                                        <input type="hidden" name="services[<?= $psId ?>][min]" value="<?= (int)($ps['min'] ?? 10) ?>">
                                        <input type="hidden" name="services[<?= $psId ?>][max]" value="<?= (int)($ps['max'] ?? 10000) ?>">
                                        <input type="hidden" name="services[<?= $psId ?>][refill]" value="<?= !empty($ps['refill']) ? 1 : 0 ?>">
                                        <input type="hidden" name="services[<?= $psId ?>][cancel]" value="<?= !empty($ps['cancel']) ? 1 : 0 ?>">
                                    </td>
                                    <td class="py-3 px-6 font-mono font-bold text-zinc-500">#<?= $psId ?></td>
                                    <td class="py-3 px-6 font-medium text-zinc-600"><?= e($ps['category'] ?? 'General') ?></td>
                                    <td class="py-3 px-6 font-bold text-zinc-900 max-w-sm truncate"><?= e($ps['name'] ?? '') ?></td>
                                    <td class="py-3 px-6 font-bold text-purple-700"><?= formatCurrency($psRate) ?></td>
                                    <td class="py-3 px-6 text-zinc-500"><?= number_format((int)($ps['min'] ?? 10)) ?> - <?= number_format((int)($ps['max'] ?? 10000)) ?></td>
                                    <td class="py-3 px-6">
                                        <?= !empty($ps['refill']) ? '<span class="text-emerald-600 font-bold">Yes</span>' : '<span class="text-zinc-400">No</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
