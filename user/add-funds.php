<?php
declare(strict_types=1);

$pageTitle = 'Add Funds';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/payments/payment-manager.php';

use Payments\PaymentManager;

$gateways = PaymentManager::getActiveGateways();
$depositError = null;
$depositSuccess = null;
$createdPayment = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $gatewayId = (int)($_POST['gateway_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);

    if ($gatewayId <= 0) {
        $depositError = 'Please select a payment method.';
    } elseif ($amount <= 0) {
        $depositError = 'Please enter a valid deposit amount.';
    } else {
        $result = PaymentManager::createPayment($userId, $gatewayId, $amount);
        if ($result['success']) {
            $createdPayment = $result;
            $depositSuccess = 'Payment transaction initialized successfully. Reference: ' . $result['transaction_ref'];
        } else {
            $depositError = $result['message'];
        }
    }
}

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-4xl mx-auto space-y-6" x-data="{ 
    selectedGatewayId: <?= !empty($gateways) ? (int)$gateways[0]['id'] : 0 ?>, 
    amount: 25,
    gateways: <?= htmlspecialchars(json_encode($gateways), ENT_QUOTES, 'UTF-8') ?>,
    get currentGateway() {
        return this.gateways.find(g => g.id == this.selectedGatewayId) || null;
    },
    get fee() {
        if (!this.currentGateway) return 0;
        const pct = parseFloat(this.currentGateway.fee_percentage) || 0;
        const fixed = parseFloat(this.currentGateway.fee_fixed) || 0;
        return (this.amount * (pct / 100)) + fixed;
    },
    get totalNet() {
        return Math.max(0, this.amount - this.fee);
    }
}">

    <!-- Page Header & Balance Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">Deposit Funds</h2>
            <p class="text-xs text-zinc-500 mt-1">Select an automated payment gateway to credit your wallet balance securely.</p>
        </div>
        <div class="text-right">
            <span class="text-[11px] font-medium text-zinc-400 block">Current Balance</span>
            <span class="text-xl font-black text-purple-700"><?= formatCurrency($user['wallet_balance'] ?? 0) ?></span>
        </div>
    </div>

    <?php if ($depositError): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-3">
            <?= heroicon('x-circle', 'w-5 h-5 text-rose-600 flex-shrink-0') ?>
            <span><?= e($depositError) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($depositSuccess && $createdPayment): ?>
        <div class="p-6 rounded-3xl bg-purple-50 border border-purple-200 space-y-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-purple-600 text-white flex items-center justify-center flex-shrink-0">
                    <?= heroicon('check-circle', 'w-6 h-6 text-white') ?>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-purple-900">Deposit Order Initialized</h3>
                    <p class="text-xs text-purple-700 mt-0.5"><?= e($depositSuccess) ?></p>
                </div>
            </div>

            <div class="p-4 bg-white rounded-2xl border border-purple-100 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                <div>
                    <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Deposit Amount</span>
                    <span class="font-bold text-zinc-900"><?= formatCurrency($createdPayment['amount']) ?></span>
                </div>
                <div>
                    <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Gateway Processing Fee</span>
                    <span class="font-bold text-zinc-600"><?= formatCurrency($createdPayment['fee']) ?></span>
                </div>
                <div>
                    <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Net Credited To Wallet</span>
                    <span class="font-black text-purple-700 text-sm"><?= formatCurrency($createdPayment['net_amount']) ?></span>
                </div>
            </div>

            <p class="text-[11px] text-purple-800 leading-relaxed">
                Your transaction has been securely registered in MySQL. For automated gateways, funds are credited upon real IPN/Webhook confirmation.
            </p>
        </div>
    <?php endif; ?>

    <!-- Gateways Grid & Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-white rounded-3xl p-6 sm:p-8 border border-zinc-200/80 shadow-sm">
            <form action="/user/add-funds.php" method="POST" class="space-y-6">
                <?= csrf_field() ?>

                <!-- Select Gateway Cards -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-3">
                        1. Select Payment Method
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <?php foreach ($gateways as $gw): ?>
                            <label class="relative p-4 rounded-2xl border cursor-pointer transition-all flex items-start gap-3"
                                   :class="selectedGatewayId == <?= $gw['id'] ?> ? 'border-purple-600 ring-2 ring-purple-600/10 bg-purple-50/20' : 'border-zinc-200 hover:bg-zinc-50'">
                                <input type="radio" 
                                       name="gateway_id" 
                                       value="<?= $gw['id'] ?>" 
                                       x-model.number="selectedGatewayId" 
                                       class="mt-1 text-purple-600 focus:ring-purple-500">
                                <div>
                                    <h4 class="text-xs font-bold text-zinc-900"><?= e($gw['name']) ?></h4>
                                    <p class="text-[11px] text-zinc-500 mt-0.5 leading-snug"><?= e($gw['description']) ?></p>
                                    <span class="text-[10px] text-purple-700 font-semibold mt-1.5 inline-block">
                                        Min: <?= formatCurrency($gw['min_amount']) ?> &bull; Max: <?= formatCurrency($gw['max_amount']) ?>
                                    </span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Deposit Amount -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-2">
                        2. Deposit Amount (USD)
                    </label>
                    <div class="relative">
                        <input type="number" 
                               name="amount" 
                               x-model.number="amount"
                               step="0.01" 
                               min="1" 
                               required
                               placeholder="25.00" 
                               class="w-full pl-10 pr-4 py-3 rounded-2xl border border-zinc-200 bg-zinc-50 text-sm font-bold text-zinc-900 focus:bg-white focus:border-purple-600 focus:ring-4 focus:ring-purple-600/10 transition-all">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400 font-bold">
                            $
                        </div>
                    </div>
                    
                    <!-- Quick Amount Pills -->
                    <div class="flex items-center gap-2 mt-2.5">
                        <button type="button" @click="amount = 10" class="px-3 py-1 rounded-xl bg-zinc-100 hover:bg-purple-100 text-[11px] font-semibold text-zinc-700 hover:text-purple-700 transition-colors">$10</button>
                        <button type="button" @click="amount = 25" class="px-3 py-1 rounded-xl bg-zinc-100 hover:bg-purple-100 text-[11px] font-semibold text-zinc-700 hover:text-purple-700 transition-colors">$25</button>
                        <button type="button" @click="amount = 50" class="px-3 py-1 rounded-xl bg-zinc-100 hover:bg-purple-100 text-[11px] font-semibold text-zinc-700 hover:text-purple-700 transition-colors">$50</button>
                        <button type="button" @click="amount = 100" class="px-3 py-1 rounded-xl bg-zinc-100 hover:bg-purple-100 text-[11px] font-semibold text-zinc-700 hover:text-purple-700 transition-colors">$100</button>
                        <button type="button" @click="amount = 250" class="px-3 py-1 rounded-xl bg-zinc-100 hover:bg-purple-100 text-[11px] font-semibold text-zinc-700 hover:text-purple-700 transition-colors">$250</button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-zinc-100">
                    <button type="submit" 
                            class="w-full py-3.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-lg shadow-purple-600/25 transition-all flex items-center justify-center gap-2">
                        <?= heroicon('credit-card', 'w-4 h-4') ?>
                        <span>Proceed with Deposit</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Payment Summary Box -->
        <div class="space-y-6">
            <div class="bg-white rounded-3xl p-6 border border-zinc-200/80 shadow-sm space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 pb-3 border-b border-zinc-100">
                    Deposit Calculation
                </h3>

                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between text-zinc-600">
                        <span>Deposit Amount:</span>
                        <span class="font-bold text-zinc-900">$<span x-text="parseFloat(amount || 0).toFixed(2)">0.00</span></span>
                    </div>
                    <div class="flex items-center justify-between text-zinc-600">
                        <span>Processing Fee:</span>
                        <span class="text-zinc-500">$<span x-text="fee.toFixed(2)">0.00</span></span>
                    </div>
                    <div class="pt-2 border-t border-zinc-100 flex items-center justify-between font-bold">
                        <span class="text-zinc-900">Credited to Balance:</span>
                        <span class="text-base text-purple-700 font-black">$<span x-text="totalNet.toFixed(2)">0.00</span></span>
                    </div>
                </div>

                <div class="pt-3 border-t border-zinc-100 text-[11px] text-zinc-400 space-y-1">
                    <p>&bull; 256-Bit SSL Encrypted checkout.</p>
                    <p>&bull; Instant automated wallet crediting.</p>
                    <p>&bull; Zero hidden conversion charges.</p>
                </div>
            </div>

            <!-- Support help -->
            <div class="p-5 rounded-3xl bg-zinc-50 border border-zinc-200 text-xs text-zinc-600 space-y-2">
                <span class="font-bold text-zinc-900 block">Need custom deposit assistance?</span>
                <p class="text-[11px] text-zinc-500 leading-relaxed">
                    For bulk deposits or corporate wire transfers over $1,000, please open a support ticket to receive direct wire instructions.
                </p>
                <a href="/user/tickets.php" class="text-[11px] font-semibold text-purple-600 hover:text-purple-800 inline-flex items-center gap-1">
                    <span>Open Support Ticket</span>
                    <?= heroicon('chevron-right', 'w-3 h-3') ?>
                </a>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
