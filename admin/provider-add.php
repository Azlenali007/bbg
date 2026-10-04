<?php
declare(strict_types=1);

$pageTitle = 'Add API Provider';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/providers/provider-manager.php';

use Providers\ProviderManager;

$addError = null;
$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = trim($_POST['name'] ?? '');
    $apiUrl = trim($_POST['api_url'] ?? '');
    $apiKey = trim($_POST['api_key'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (isset($_POST['test_connection'])) {
        $testResult = ProviderManager::testConnection($apiUrl, $apiKey);
    } else {
        if (empty($name) || empty($apiUrl) || empty($apiKey)) {
            $addError = 'Name, API URL, and API Key are required.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO providers (name, api_url, api_key, status, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $apiUrl, $apiKey, $status]);
            $newId = (int)$db->lastInsertId();

            // Try to sync balance immediately
            ProviderManager::syncBalance($newId);

            flash('success', "Provider '{$name}' created successfully.");
            header('Location: /admin/providers.php');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="/admin/providers.php" class="text-xs font-semibold text-zinc-500 hover:text-purple-600 flex items-center gap-1.5">
            <?= heroicon('arrow-left-on-rectangle', 'w-4 h-4') ?>
            <span>Back to Providers</span>
        </a>
        <h2 class="text-lg font-bold text-zinc-900">Add API Provider</h2>
    </div>

    <?php if ($addError): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">
            <?= e($addError) ?>
        </div>
    <?php endif; ?>

    <?php if ($testResult): ?>
        <div class="p-4 rounded-2xl <?= $testResult['success'] ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?> text-xs font-semibold flex items-center gap-2">
            <?= heroicon($testResult['success'] ? 'check-circle' : 'x-circle', 'w-5 h-5 flex-shrink-0') ?>
            <span>
                <?= $testResult['success'] ? "Connection successful! Provider Live Balance: " . formatCurrency($testResult['balance']) : "Connection failed: " . e($testResult['message']) ?>
            </span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-zinc-200/80 shadow-sm">
        <form action="/admin/provider-add.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Provider Label *</label>
                <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required placeholder="e.g. Peakerr / JustSMM" 
                       class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">API Endpoint URL *</label>
                <input type="url" name="api_url" value="<?= e($_POST['api_url'] ?? '') ?>" required placeholder="https://provider-domain.com/api/v2" 
                       class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                <span class="text-[11px] text-zinc-400 mt-1 block">Standard SMM v2 endpoint accepting POST requests.</span>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Provider API Key *</label>
                <input type="password" name="api_key" value="<?= e($_POST['api_key'] ?? '') ?>" required placeholder="Secret API Key" 
                       class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="pt-4 border-t border-zinc-100 flex items-center justify-between">
                <button type="submit" name="test_connection" value="1" class="px-4 py-2.5 rounded-2xl border border-purple-200 bg-purple-50 text-purple-700 hover:bg-purple-100 text-xs font-bold transition-colors">
                    Test Connection
                </button>
                <div class="flex items-center gap-2">
                    <a href="/admin/providers.php" class="px-4 py-2.5 rounded-2xl border border-zinc-200 text-xs font-semibold text-zinc-700 hover:bg-zinc-50">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/25">
                        Save Provider
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
