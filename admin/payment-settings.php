<?php
declare(strict_types=1);

$pageTitle = 'Payment Gateways';

require_once __DIR__ . '/includes/header.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $gatewayId = (int)($_POST['gateway_id'] ?? 0);
    $isActive = !empty($_POST['is_active']) ? 1 : 0;
    $min = (float)($_POST['min_amount'] ?? 1);
    $max = (float)($_POST['max_amount'] ?? 1000);
    $feePct = (float)($_POST['fee_percentage'] ?? 0);
    $feeFix = (float)($_POST['fee_fixed'] ?? 0);
    $credentials = trim($_POST['credentials'] ?? '');

    if ($gatewayId > 0) {
        $stmt = $db->prepare("
            UPDATE payment_gateways 
            SET is_active = ?, min_amount = ?, max_amount = ?, fee_percentage = ?, fee_fixed = ?, credentials = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$isActive, $min, $max, $feePct, $feeFix, $credentials, $gatewayId]);
        flash('success', 'Gateway settings updated.');
        header('Location: /admin/payment-settings.php');
        exit;
    }
}

$gateways = $db->query("SELECT * FROM payment_gateways ORDER BY sort_order ASC")->fetchAll();

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="space-y-6">

    <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">Payment Gateways Configuration</h2>
            <p class="text-xs text-zinc-500 mt-1">Enable payment channels, configure min/max thresholds, and set merchant API credentials.</p>
        </div>
        <a href="/admin/payments.php" class="text-xs font-semibold text-zinc-500 hover:text-purple-600 flex items-center gap-1">
            <?= heroicon('arrow-left-on-rectangle', 'w-4 h-4') ?>
            <span>Back to Payments</span>
        </a>
    </div>

    <!-- Gateways List Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($gateways as $gw): ?>
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-zinc-200/80 shadow-sm space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-base font-bold text-zinc-900"><?= e($gw['name']) ?></h3>
                        <p class="text-xs text-zinc-400 font-mono mt-0.5"><?= e($gw['code']) ?></p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $gw['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' ?>">
                        <?= $gw['is_active'] ? 'Active' : 'Disabled' ?>
                    </span>
                </div>

                <form action="/admin/payment-settings.php" method="POST" class="space-y-3 pt-2 border-t border-zinc-100">
                    <?= csrf_field() ?>
                    <input type="hidden" name="gateway_id" value="<?= $gw['id'] ?>">

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" id="act_<?= $gw['id'] ?>" <?= $gw['is_active'] ? 'checked' : '' ?> class="rounded text-purple-600 focus:ring-purple-500">
                        <label for="act_<?= $gw['id'] ?>" class="text-xs font-bold text-zinc-800">Enable this gateway for users</label>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-zinc-700 mb-1">Min Deposit</label>
                            <input type="number" name="min_amount" step="0.01" value="<?= (float)$gw['min_amount'] ?>" required 
                                   class="w-full px-3 py-2 rounded-xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-zinc-700 mb-1">Max Deposit</label>
                            <input type="number" name="max_amount" step="0.01" value="<?= (float)$gw['max_amount'] ?>" required 
                                   class="w-full px-3 py-2 rounded-xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-zinc-700 mb-1">Fee %</label>
                            <input type="number" name="fee_percentage" step="0.01" value="<?= (float)$gw['fee_percentage'] ?>" 
                                   class="w-full px-3 py-2 rounded-xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-zinc-700 mb-1">Fixed Fee ($)</label>
                            <input type="number" name="fee_fixed" step="0.01" value="<?= (float)$gw['fee_fixed'] ?>" 
                                   class="w-full px-3 py-2 rounded-xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase text-zinc-700 mb-1">Merchant Credentials (JSON or Keys)</label>
                        <textarea name="credentials" rows="2" placeholder='{"secret_key": "sk_...", "publishable_key": "pk_..."}' 
                                  class="w-full px-3 py-2 rounded-xl border border-zinc-200 bg-zinc-50 font-mono text-[11px] text-zinc-900 focus:bg-white focus:border-purple-600"><?= e($gw['credentials'] ?? '') ?></textarea>
                    </div>

                    <div class="pt-2 text-right">
                        <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/20">
                            Save Gateway
                        </button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
