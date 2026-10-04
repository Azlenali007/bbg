<?php
declare(strict_types=1);

$pageTitle = 'Sync Provider Services';
require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/providers/provider-manager.php';
require_once BASE_PATH . '/app/Services/ServiceManager.php';

use App\Services\ServiceManager;

$db = getDB();
$providers = ProviderManager::getAllProviders();
$selectedProviderId = (int)($_GET['provider_id'] ?? ($providers[0]['id'] ?? 0));

$syncResult = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $providerId = (int)($_POST['provider_id'] ?? 0);
    $marginPercent = (float)($_POST['profit_margin'] ?? 30);
    $autoDisable = isset($_POST['auto_disable']);

    if (!$providerId) {
        $error = 'Please select a provider to sync.';
    } else {
        try {
            $provider = ProviderManager::getProviderById($providerId);
            if (!$provider) {
                throw new Exception('Provider not found.');
            }

            $adapter = ProviderManager::getAdapter($provider);
            $services = $adapter->services();

            if (empty($services)) {
                throw new Exception('Provider returned no services or API request failed.');
            }

            $syncedCount = 0;
            $updatedRates = 0;

            // Update matching mapped services
            $stmt = $db->prepare("
                SELECT psm.*, s.rate as current_rate, s.id as service_id 
                FROM provider_service_mappings psm
                JOIN services s ON psm.service_id = s.id
                WHERE psm.provider_id = ?
            ");
            $stmt->execute([$providerId]);
            $mappings = $stmt->fetchAll();

            $providerServicesKeyed = [];
            foreach ($services as $ps) {
                $providerServicesKeyed[(string)$ps['service']] = $ps;
            }

            foreach ($mappings as $map) {
                $pid = (string)$map['provider_service_id'];
                if (isset($providerServicesKeyed[$pid])) {
                    $pService = $providerServicesKeyed[$pid];
                    $newProviderRate = (float)($pService['rate'] ?? 0);
                    $newSellingRate = round($newProviderRate * (1 + ($marginPercent / 100)), 4);

                    // Update mapping rate
                    $upMap = $db->prepare("UPDATE provider_service_mappings SET provider_rate = ? WHERE id = ?");
                    $upMap->execute([$newProviderRate, $map['id']]);

                    // Update service selling rate
                    $upSvc = $db->prepare("UPDATE services SET rate = ? WHERE id = ?");
                    $upSvc->execute([$newSellingRate, $map['service_id']]);

                    $updatedRates++;
                } elseif ($autoDisable) {
                    // Service disappeared on provider, mark inactive
                    $db->prepare("UPDATE services SET status = 'inactive' WHERE id = ?")->execute([$map['service_id']]);
                }
                $syncedCount++;
            }

            logAudit($adminId, true, 'sync_services', "Synced {$syncedCount} services for provider {$provider['name']}");
            $syncResult = "Successfully synchronized {$syncedCount} services ({$updatedRates} rates updated with {$marginPercent}% margin).";
        } catch (Throwable $e) {
            $error = 'Sync failed: ' . $e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-5xl w-full mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="/admin/services.php" class="text-xs font-semibold text-purple-600 hover:text-purple-700 flex items-center gap-1">
                    <?= heroicon('arrow-left', 'w-3.5 h-3.5') ?> Back to Services
                </a>
            </div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight mt-1">Sync Provider Services</h1>
            <p class="text-xs text-zinc-500">Auto-update pricing, minimums, maximums and provider status.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($syncResult): ?>
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800">
            <?= e($syncResult) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 sm:p-8 shadow-sm">
        <?php if (empty($providers)): ?>
            <div class="text-center py-8">
                <p class="text-xs text-zinc-500 mb-3">No API Providers configured yet.</p>
                <a href="/admin/provider-add.php" class="px-4 py-2 bg-purple-600 text-white rounded-xl text-xs font-bold">Add Provider</a>
            </div>
        <?php else: ?>
            <form action="" method="POST" class="space-y-6">
                <?= csrf_field() ?>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1.5">Select Provider</label>
                        <select name="provider_id" class="w-full px-4 py-3 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                            <?php foreach ($providers as $prov): ?>
                                <option value="<?= $prov['id'] ?>" <?= $prov['id'] === $selectedProviderId ? 'selected' : '' ?>>
                                    <?= e($prov['name']) ?> (<?= e($prov['api_url']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1.5">Selling Profit Margin Percentage (%)</label>
                        <input type="number" step="1" min="0" max="1000" name="profit_margin" value="30" required
                               class="w-full px-4 py-3 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                        <p class="text-[10px] text-zinc-400 mt-1">e.g. 30% means if provider rate is $1.00/1k, your selling price becomes $1.30/1k.</p>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" name="auto_disable" id="auto_disable" value="1" checked class="rounded border-zinc-300 text-purple-600 focus:ring-purple-500">
                        <label for="auto_disable" class="text-zinc-700 font-semibold cursor-pointer">
                            Auto-disable services in your panel if removed by provider
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t border-zinc-100 flex items-center justify-end gap-3">
                    <a href="/admin/services.php" class="px-5 py-2.5 rounded-xl border border-zinc-200 text-zinc-600 font-bold text-xs hover:bg-zinc-50">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-md shadow-purple-600/20 transition-all">
                        Execute Live Sync
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
