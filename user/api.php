<?php
declare(strict_types=1);

$pageTitle = 'API Integration';

require_once __DIR__ . '/includes/header.php';

$apiSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'regenerate_key') {
    require_csrf();
    $newKey = bin2hex(random_bytes(24));
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET api_key = ? WHERE id = ?");
    $stmt->execute([$newKey, $userId]);
    $user['api_key'] = $newKey;
    $apiSuccess = 'New API Key generated successfully.';
}

$apiUrl = (defined('APP_URL') && APP_URL ? APP_URL : (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] : '')) . '/api/user-api.php';

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header & Key Card -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-zinc-200/80 shadow-sm space-y-4">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">API Integration Key</h2>
            <p class="text-xs text-zinc-500 mt-1">Connect your reseller platform, custom bot, or automated dispatch engine directly to our API.</p>
        </div>

        <?php if ($apiSuccess): ?>
            <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
                <?= heroicon('check-circle', 'w-4 h-4 text-emerald-600') ?>
                <span><?= e($apiSuccess) ?></span>
            </div>
        <?php endif; ?>

        <div class="p-4 bg-zinc-50 rounded-2xl border border-zinc-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="min-w-0">
                <span class="text-[10px] text-zinc-400 uppercase font-bold tracking-wider block">Your Secret API Key</span>
                <span class="font-mono text-xs sm:text-sm font-bold text-purple-700 truncate block mt-0.5 select-all">
                    <?= e($user['api_key'] ?? 'No key assigned') ?>
                </span>
            </div>

            <form action="/user/api.php" method="POST" onsubmit="return confirm('Regenerating your key will invalidate existing integrations. Continue?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="regenerate_key">
                <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-sm shadow-purple-600/20 transition-all flex items-center gap-1.5 whitespace-nowrap">
                    <?= heroicon('arrow-path', 'w-3.5 h-3.5') ?>
                    <span>Regenerate Key</span>
                </button>
            </form>
        </div>
    </div>

    <!-- API Documentation -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-zinc-200/80 shadow-sm space-y-6">
        <div>
            <h3 class="text-base font-bold text-zinc-900">Standard SMM v2 API Specification</h3>
            <p class="text-xs text-zinc-500 mt-0.5">Compatible with all standard SMM panels and automation scripts.</p>
        </div>

        <div class="space-y-2">
            <span class="text-xs font-bold text-zinc-700 block">HTTP POST Endpoint</span>
            <div class="p-3 bg-zinc-900 text-purple-300 font-mono text-xs rounded-xl select-all">
                <?= e($apiUrl) ?>
            </div>
        </div>

        <!-- 1. Services -->
        <div class="pt-4 border-t border-zinc-100 space-y-2">
            <h4 class="text-xs font-bold uppercase text-purple-700">1. Service List</h4>
            <div class="bg-zinc-900 text-zinc-200 p-4 rounded-2xl font-mono text-[11px] overflow-x-auto">
POST <?= e($apiUrl) ?> <br>
key=<?= e($user['api_key']) ?>&action=services
            </div>
        </div>

        <!-- 2. Balance -->
        <div class="pt-4 border-t border-zinc-100 space-y-2">
            <h4 class="text-xs font-bold uppercase text-purple-700">2. Account Balance</h4>
            <div class="bg-zinc-900 text-zinc-200 p-4 rounded-2xl font-mono text-[11px] overflow-x-auto">
POST <?= e($apiUrl) ?> <br>
key=<?= e($user['api_key']) ?>&action=balance
            </div>
        </div>

        <!-- 3. Add Order -->
        <div class="pt-4 border-t border-zinc-100 space-y-2">
            <h4 class="text-xs font-bold uppercase text-purple-700">3. Add Order</h4>
            <div class="bg-zinc-900 text-zinc-200 p-4 rounded-2xl font-mono text-[11px] overflow-x-auto">
POST <?= e($apiUrl) ?> <br>
key=<?= e($user['api_key']) ?>&action=add&service=1&link=https://instagram.com/p/xxx&quantity=1000
            </div>
        </div>

        <!-- 4. Order Status -->
        <div class="pt-4 border-t border-zinc-100 space-y-2">
            <h4 class="text-xs font-bold uppercase text-purple-700">4. Order Status</h4>
            <div class="bg-zinc-900 text-zinc-200 p-4 rounded-2xl font-mono text-[11px] overflow-x-auto">
POST <?= e($apiUrl) ?> <br>
key=<?= e($user['api_key']) ?>&action=status&order=105
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
