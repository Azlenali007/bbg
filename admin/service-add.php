<?php
declare(strict_types=1);

$pageTitle = 'Add Service';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Services/ServiceManager.php';

use App\Services\ServiceManager;

$db = getDB();
$categories = ServiceManager::getCategories(false);
$providers = $db->query("SELECT * FROM providers WHERE status = 'active' ORDER BY name ASC")->fetchAll();

$addError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $data = [
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'provider_id' => !empty($_POST['provider_id']) ? (int)$_POST['provider_id'] : null,
        'provider_service_id' => trim($_POST['provider_service_id'] ?? ''),
        'name' => trim($_POST['name'] ?? ''),
        'type' => trim($_POST['type'] ?? 'default'),
        'rate' => (float)($_POST['rate'] ?? 0),
        'min_quantity' => (int)($_POST['min_quantity'] ?? 10),
        'max_quantity' => (int)($_POST['max_quantity'] ?? 10000),
        'refill_supported' => !empty($_POST['refill_supported']) ? 1 : 0,
        'cancel_supported' => !empty($_POST['cancel_supported']) ? 1 : 0,
        'dripfeed_supported' => !empty($_POST['dripfeed_supported']) ? 1 : 0,
        'description' => trim($_POST['description'] ?? ''),
        'status' => $_POST['status'] ?? 'active',
        'sort_order' => (int)($_POST['sort_order'] ?? 0)
    ];

    if ($data['category_id'] <= 0 || empty($data['name']) || $data['rate'] <= 0) {
        $addError = 'Category, Name, and Selling Rate are required.';
    } else {
        $res = ServiceManager::createService($data);
        if ($res['success']) {
            flash('success', "Service #{$res['id']} created successfully.");
            header('Location: /admin/services.php');
            exit;
        } else {
            $addError = $res['message'];
        }
    }
}

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="/admin/services.php" class="text-xs font-semibold text-zinc-500 hover:text-purple-600 flex items-center gap-1.5">
            <?= heroicon('arrow-left-on-rectangle', 'w-4 h-4') ?>
            <span>Back to Services</span>
        </a>
        <h2 class="text-lg font-bold text-zinc-900">Add New Service</h2>
    </div>

    <?php if ($addError): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">
            <?= e($addError) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-zinc-200/80 shadow-sm">
        <form action="/admin/service-add.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Category *</label>
                <select name="category_id" required class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Service Name *</label>
                <input type="text" name="name" required placeholder="e.g. Instagram Followers [Real & Active]" 
                       class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">API Provider (Optional)</label>
                    <select name="provider_id" class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                        <option value="">Manual / No Provider</option>
                        <?php foreach ($providers as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Provider Service ID</label>
                    <input type="text" name="provider_service_id" placeholder="e.g. 1024" 
                           class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Selling Rate (per 1k) *</label>
                    <input type="number" name="rate" step="0.0001" min="0.0001" required placeholder="1.5000" 
                           class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Min Quantity *</label>
                    <input type="number" name="min_quantity" min="1" value="10" required 
                           class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Max Quantity *</label>
                    <input type="number" name="max_quantity" min="1" value="10000" required 
                           class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                </div>
            </div>

            <div class="flex items-center gap-6 pt-2">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-zinc-700">
                    <input type="checkbox" name="refill_supported" value="1" class="rounded text-purple-600 focus:ring-purple-500">
                    <span>Supports Refill</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-zinc-700">
                    <input type="checkbox" name="cancel_supported" value="1" class="rounded text-purple-600 focus:ring-purple-500">
                    <span>Supports Cancel</span>
                </label>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Description & Guidelines</label>
                <textarea name="description" rows="3" placeholder="Provide delivery instructions, speed estimates, and requirements..." 
                          class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600"></textarea>
            </div>

            <div class="pt-4 border-t border-zinc-100 flex items-center justify-end gap-3">
                <a href="/admin/services.php" class="px-5 py-2.5 rounded-2xl border border-zinc-200 text-xs font-semibold text-zinc-700 hover:bg-zinc-50">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/25">
                    Save Service
                </button>
            </div>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
