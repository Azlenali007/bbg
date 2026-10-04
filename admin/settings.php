<?php
declare(strict_types=1);

$pageTitle = 'System Settings';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $settingsToUpdate = [
        'site_name' => trim($_POST['site_name'] ?? APP_NAME),
        'site_description' => trim($_POST['site_description'] ?? ''),
        'currency' => strtoupper(trim($_POST['currency'] ?? 'USD')),
        'currency_symbol' => trim($_POST['currency_symbol'] ?? '$'),
        'support_email' => trim($_POST['support_email'] ?? 'support@smm.local'),
        'min_deposit' => (string)max(1.0, (float)($_POST['min_deposit'] ?? 5.0)),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'enable_registration' => isset($_POST['enable_registration']) ? '1' : '0',
        'auto_order_api' => isset($_POST['auto_order_api']) ? '1' : '0',
    ];

    try {
        foreach ($settingsToUpdate as $k => $v) {
            setSetting($k, $v);
        }
        logAudit($adminId, true, 'update_settings', 'Updated system settings');
        flash('success', 'System configuration saved successfully.');
        header('Location: /admin/settings.php');
        exit;
    } catch (Throwable $e) {
        $error = 'Error saving settings: ' . $e->getMessage();
    }
}
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-4xl w-full mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight">System Settings</h1>
            <p class="text-xs text-zinc-500">Configure global platform options, currency, security, and defaults.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 sm:p-8 shadow-sm">
        <form action="" method="POST" class="space-y-6 text-xs">
            <?= csrf_field() ?>

            <!-- Branding & General -->
            <div class="space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-400 border-b border-zinc-100 pb-2">Platform Branding</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Site / Panel Name</label>
                        <input type="text" name="site_name" value="<?= e(getSetting('site_name', APP_NAME)) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                    </div>
                    <div>
                        <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Support Contact Email</label>
                        <input type="email" name="support_email" value="<?= e(getSetting('support_email', 'support@smm.local')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Site Meta Description</label>
                    <textarea name="site_description" rows="2" class="w-full p-3 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600"><?= e(getSetting('site_description', 'High performance SMM services.')) ?></textarea>
                </div>
            </div>

            <!-- Currency & Finance -->
            <div class="space-y-4 pt-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-400 border-b border-zinc-100 pb-2">Financial Settings</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Currency Code</label>
                        <input type="text" name="currency" value="<?= e(getSetting('currency', 'USD')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                    </div>
                    <div>
                        <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Currency Symbol</label>
                        <input type="text" name="currency_symbol" value="<?= e(getSetting('currency_symbol', '$')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                    </div>
                    <div>
                        <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Minimum Deposit</label>
                        <input type="number" step="0.5" name="min_deposit" value="<?= e(getSetting('min_deposit', '5.00')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                    </div>
                </div>
            </div>

            <!-- Operations Toggles -->
            <div class="space-y-4 pt-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-400 border-b border-zinc-100 pb-2">Operation Mode & Automation</h2>
                
                <div class="space-y-3">
                    <label class="flex items-center gap-3 p-3 rounded-2xl border border-zinc-100 hover:bg-zinc-50 cursor-pointer">
                        <input type="checkbox" name="auto_order_api" value="1" <?= getSetting('auto_order_api', '1') === '1' ? 'checked' : '' ?> class="rounded border-zinc-300 text-purple-600 focus:ring-purple-500">
                        <div>
                            <span class="font-bold text-zinc-800 block">Auto-Dispatch Orders to Provider APIs</span>
                            <span class="text-[11px] text-zinc-400">When enabled, orders placed with provider-linked services are automatically forwarded to external API immediately.</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-2xl border border-zinc-100 hover:bg-zinc-50 cursor-pointer">
                        <input type="checkbox" name="enable_registration" value="1" <?= getSetting('enable_registration', '1') === '1' ? 'checked' : '' ?> class="rounded border-zinc-300 text-purple-600 focus:ring-purple-500">
                        <div>
                            <span class="font-bold text-zinc-800 block">Enable Public User Registration</span>
                            <span class="text-[11px] text-zinc-400">Allow new visitors to register accounts freely on /register.php</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-2xl border border-rose-100 bg-rose-50/30 hover:bg-rose-50/50 cursor-pointer">
                        <input type="checkbox" name="maintenance_mode" value="1" <?= getSetting('maintenance_mode', '0') === '1' ? 'checked' : '' ?> class="rounded border-rose-300 text-rose-600 focus:ring-rose-500">
                        <div>
                            <span class="font-bold text-rose-900 block">Maintenance Mode</span>
                            <span class="text-[11px] text-rose-600">Temporarily prevent customer access while administration remains fully accessible.</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="pt-4 border-t border-zinc-100 flex justify-end">
                <button type="submit" class="px-6 py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-md shadow-purple-600/20 transition-all">
                    Save System Settings
                </button>
            </div>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
