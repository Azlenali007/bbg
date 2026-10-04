<?php
declare(strict_types=1);

$pageTitle = 'New Order';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Services/ServiceManager.php';
require_once BASE_PATH . '/app/Orders/OrderManager.php';
require_once BASE_PATH . '/app/FlashSales/FlashSaleManager.php';

use App\Services\ServiceManager;
use App\Orders\OrderManager;
use App\FlashSales\FlashSaleManager;

$categories = ServiceManager::getCategories(true);
$services = ServiceManager::getActiveServices();

// Handle form submission
$orderError = null;
$orderSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $serviceId = (int)($_POST['service_id'] ?? 0);
    $link = trim($_POST['link'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);

    if ($serviceId <= 0) {
        $orderError = 'Please select a valid service.';
    } elseif (empty($link)) {
        $orderError = 'Please enter a target link.';
    } elseif ($quantity <= 0) {
        $orderError = 'Please specify a positive quantity.';
    } else {
        $result = OrderManager::createOrder($userId, $serviceId, $link, $quantity);
        if ($result['success']) {
            flash('success', "Order #{$result['order_id']} placed successfully!");
            header('Location: /user/orders.php');
            exit;
        } else {
            $orderError = $result['message'];
        }
    }
}

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-5xl mx-auto space-y-6" 
     x-data="serviceSelector(<?= htmlspecialchars(json_encode($services, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($categories, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">

    <!-- Page Header & Balance Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">Create New Order</h2>
            <p class="text-xs text-zinc-500 mt-1">Select your service, paste the target link, and deploy instant automated growth.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right">
                <span class="text-[11px] font-medium text-zinc-400 block">Available Balance</span>
                <span class="text-lg font-black text-purple-700"><?= formatCurrency($user['wallet_balance'] ?? 0) ?></span>
            </div>
            <a href="/user/add-funds.php" class="p-2 rounded-xl bg-purple-50 text-purple-700 hover:bg-purple-100 transition-colors">
                <?= heroicon('plus', 'w-5 h-5') ?>
            </a>
        </div>
    </div>

    <?php if ($orderError): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-3">
            <?= heroicon('x-circle', 'w-5 h-5 text-rose-600 flex-shrink-0') ?>
            <span><?= e($orderError) ?></span>
        </div>
    <?php endif; ?>

    <!-- Main Order Form Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Order Placement Form (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-3xl p-6 sm:p-8 border border-zinc-200/80 shadow-sm">
            <form action="/user/new-order.php" method="POST" class="space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="service_id" id="selected_service_id" :value="selectedService ? selectedService.id : ''">

                <!-- 1. Category Selection -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-2">
                        1. Category
                    </label>
                    <div class="relative">
                        <select x-model.number="selectedCategory" 
                                @change="selectedService = null; calculate()"
                                class="w-full px-4 py-3 rounded-2xl border border-zinc-200 bg-zinc-50/50 hover:bg-white focus:bg-white focus:border-purple-600 focus:ring-4 focus:ring-purple-600/10 text-xs font-semibold text-zinc-900 transition-all appearance-none pr-10">
                            <option value="0">All Categories (<?= count($services) ?> Total Services)</option>
                            <template x-for="cat in categories" :key="cat.id">
                                <option :value="cat.id" x-text="cat.name + ' (' + cat.service_count + ')'"></option>
                            </template>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-3.5 pointer-events-none text-zinc-400">
                            <?= heroicon('chevron-down', 'w-4 h-4') ?>
                        </div>
                    </div>
                </div>

                <!-- 2. CUSTOM SERVICE DROPDOWN (Requirement 16, 17, 18, 19) -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700">
                            2. Service Selection
                        </label>
                        <span x-show="selectedService" class="text-[11px] font-semibold text-purple-600 font-mono" x-text="'ID: #' + selectedService.id"></span>
                    </div>

                    <!-- Custom Dropdown Trigger Button -->
                    <div class="relative" @click.away="close()">
                        <button type="button" 
                                @click="toggle()"
                                class="w-full text-left px-4 py-3.5 rounded-2xl border transition-all flex items-center justify-between bg-zinc-50/50 hover:bg-white focus:outline-none"
                                :class="open ? 'border-purple-600 ring-4 ring-purple-600/10 bg-white' : (selectedService ? 'border-purple-300 bg-purple-50/20' : 'border-zinc-200')">
                            
                            <div class="flex items-center gap-3 min-w-0 pr-2">
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                                     :class="selectedService ? 'bg-purple-600 text-white' : 'bg-zinc-200 text-zinc-500'">
                                    <?= heroicon('cube', 'w-4 h-4') ?>
                                </div>
                                <div class="truncate">
                                    <template x-if="selectedService">
                                        <div>
                                            <p class="text-xs font-bold text-zinc-900 truncate" x-text="selectedService.name"></p>
                                            <p class="text-[11px] text-purple-700 font-semibold" x-text="'$' + parseFloat(selectedService.rate).toFixed(4) + ' per 1k'"></p>
                                        </div>
                                    </template>
                                    <template x-if="!selectedService">
                                        <p class="text-xs font-medium text-zinc-400">Click to choose a high-retention service...</p>
                                    </template>
                                </div>
                            </div>

                            <div class="flex-shrink-0 text-zinc-400">
                                <?= heroicon('chevron-down', 'w-4 h-4 transition-transform duration-200', 'outline') ?>
                            </div>
                        </button>

                        <!-- Custom Service Dropdown Panel -->
                        <div x-show="open" x-cloak 
                             x-ref="dropdownPanel"
                             class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl border border-zinc-200 shadow-2xl shadow-purple-600/15 z-50 overflow-hidden">
                            
                            <!-- Search & Filter bar inside dropdown -->
                            <div class="p-3 border-b border-zinc-100 bg-zinc-50/80 flex items-center gap-2">
                                <div class="relative flex-1">
                                    <input type="text" 
                                           x-ref="searchInput"
                                           x-model="search" 
                                           placeholder="Search service by name or ID..." 
                                           class="w-full pl-9 pr-8 py-2 rounded-xl border border-zinc-200 bg-white text-xs text-zinc-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-600/10">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                                        <?= heroicon('magnifying-glass', 'w-4 h-4') ?>
                                    </div>
                                    <button type="button" 
                                            x-show="search" 
                                            @click="clearSearch()"
                                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-zinc-400 hover:text-zinc-600">
                                        <?= heroicon('x-mark', 'w-4 h-4') ?>
                                    </button>
                                </div>
                            </div>

                            <!-- Services List Items -->
                            <div class="max-h-72 overflow-y-auto divide-y divide-zinc-100 p-1">
                                <template x-if="filteredServices.length === 0">
                                    <div class="py-8 text-center text-zinc-400">
                                        <div class="w-8 h-8 rounded-full bg-zinc-100 text-zinc-400 flex items-center justify-center mx-auto mb-2">
                                            <?= heroicon('magnifying-glass', 'w-4 h-4') ?>
                                        </div>
                                        <p class="text-xs font-medium">No matching services found</p>
                                    </div>
                                </template>

                                <template x-for="item in filteredServices" :key="item.id">
                                    <div @click="selectService(item)"
                                         class="p-3 rounded-xl cursor-pointer transition-colors flex items-start justify-between gap-3"
                                         :class="selectedService && selectedService.id === item.id ? 'bg-purple-50 text-purple-900 border border-purple-200' : 'hover:bg-zinc-50'">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-mono font-bold text-purple-600 bg-purple-100/60 px-1.5 py-0.5 rounded" x-text="'#' + item.id"></span>
                                                <span class="text-xs font-semibold text-zinc-900 truncate" x-text="item.name"></span>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2 mt-1.5 text-[10px] text-zinc-400">
                                                <span x-text="'Min: ' + item.min_quantity"></span>
                                                <span>&bull;</span>
                                                <span x-text="'Max: ' + item.max_quantity"></span>
                                                <template x-if="item.refill_supported == 1">
                                                    <span class="text-emerald-600 font-bold bg-emerald-50 px-1 rounded">&bull; Refill</span>
                                                </template>
                                                <template x-if="item.cancel_supported == 1">
                                                    <span class="text-purple-600 font-bold bg-purple-50 px-1 rounded">&bull; Cancel</span>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <span class="text-xs font-bold text-purple-700 block" x-text="'$' + parseFloat(item.rate).toFixed(4)"></span>
                                            <span class="text-[9px] text-zinc-400">per 1k</span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Target Link -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-2">
                        3. Target Link / URL
                    </label>
                    <div class="relative">
                        <input type="url" 
                               name="link" 
                               required
                               placeholder="https://instagram.com/username or post link"
                               class="w-full pl-10 pr-4 py-3 rounded-2xl border border-zinc-200 bg-zinc-50/50 hover:bg-white focus:bg-white focus:border-purple-600 focus:ring-4 focus:ring-purple-600/10 text-xs font-medium text-zinc-900 transition-all">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                            <?= heroicon('link', 'w-4 h-4') ?>
                        </div>
                    </div>
                    <span class="text-[11px] text-zinc-400 mt-1 block">Ensure account is public and link format is valid.</span>
                </div>

                <!-- 4. Quantity -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700">
                            4. Quantity
                        </label>
                        <template x-if="selectedService">
                            <span class="text-[11px] text-zinc-500" x-text="'Allowed: ' + selectedService.min_quantity + ' - ' + selectedService.max_quantity"></span>
                        </template>
                    </div>
                    <input type="number" 
                           name="quantity" 
                           x-model.number="quantity" 
                           @input="calculate()"
                           :min="selectedService ? selectedService.min_quantity : 1" 
                           :max="selectedService ? selectedService.max_quantity : 1000000"
                           required
                           class="w-full px-4 py-3 rounded-2xl border border-zinc-200 bg-zinc-50/50 hover:bg-white focus:bg-white focus:border-purple-600 focus:ring-4 focus:ring-purple-600/10 text-xs font-semibold text-zinc-900 transition-all">
                </div>

                <!-- Dynamic Price Display & Submit (Requirement 21) -->
                <div class="pt-4 border-t border-zinc-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider block">Estimated Total Price</span>
                        <div class="text-2xl font-black text-purple-700 tracking-tight">
                            $<span x-text="calculatedPrice">0.0000</span>
                        </div>
                        <span class="text-[10px] text-zinc-400 leading-tight">Server strictly verifies rate and limits upon submission.</span>
                    </div>

                    <button type="submit" 
                            :disabled="!selectedService"
                            class="px-8 py-3.5 rounded-2xl bg-purple-600 hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-bold shadow-lg shadow-purple-600/25 transition-all flex items-center justify-center gap-2">
                        <?= heroicon('bolt', 'w-4 h-4') ?>
                        <span>Submit Order</span>
                    </button>
                </div>

            </form>
        </div>

        <!-- SERVICE INFORMATION CARD (Requirement 20) -->
        <div class="space-y-6">
            
            <div class="bg-white rounded-3xl p-6 border border-zinc-200/80 shadow-sm">
                <div class="flex items-center gap-2.5 pb-4 border-b border-zinc-100">
                    <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <?= heroicon('information-circle', 'w-4 h-4') ?>
                    </div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900">Service Specifications</h3>
                </div>

                <template x-if="selectedService">
                    <div class="mt-4 space-y-4">
                        <div>
                            <span class="text-[11px] text-zinc-400 block">Service Name</span>
                            <p class="text-xs font-bold text-zinc-800 mt-0.5" x-text="selectedService.name"></p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="bg-zinc-50 p-2.5 rounded-xl border border-zinc-100">
                                <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Rate / 1k</span>
                                <span class="font-bold text-purple-700" x-text="'$' + parseFloat(selectedService.rate).toFixed(4)"></span>
                            </div>
                            <div class="bg-zinc-50 p-2.5 rounded-xl border border-zinc-100">
                                <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Min Quantity</span>
                                <span class="font-bold text-zinc-800" x-text="selectedService.min_quantity"></span>
                            </div>
                            <div class="bg-zinc-50 p-2.5 rounded-xl border border-zinc-100">
                                <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Max Quantity</span>
                                <span class="font-bold text-zinc-800" x-text="selectedService.max_quantity"></span>
                            </div>
                            <div class="bg-zinc-50 p-2.5 rounded-xl border border-zinc-100">
                                <span class="text-[10px] text-zinc-400 uppercase font-semibold block">Auto Refill</span>
                                <span class="font-bold" :class="selectedService.refill_supported == 1 ? 'text-emerald-600' : 'text-zinc-400'" x-text="selectedService.refill_supported == 1 ? 'Supported' : 'No'"></span>
                            </div>
                        </div>

                        <template x-if="selectedService.description">
                            <div class="pt-3 border-t border-zinc-100">
                                <span class="text-[11px] text-zinc-400 block mb-1">Description</span>
                                <p class="text-xs text-zinc-600 bg-zinc-50/70 p-3 rounded-xl border border-zinc-100 leading-relaxed whitespace-pre-line" x-text="selectedService.description"></p>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!selectedService">
                    <div class="py-12 text-center text-zinc-400">
                        <div class="w-10 h-10 rounded-2xl bg-zinc-100 text-zinc-400 flex items-center justify-center mx-auto mb-2">
                            <?= heroicon('cube', 'w-5 h-5') ?>
                        </div>
                        <p class="text-xs font-semibold text-zinc-600">No service selected</p>
                        <p class="text-[11px] text-zinc-400 mt-1">Select a service from the dropdown to review specifications, limits, and refill coverage.</p>
                    </div>
                </template>
            </div>

            <!-- Guarantee Notice -->
            <div class="p-5 rounded-3xl bg-purple-50/60 border border-purple-100 text-purple-900 space-y-2">
                <div class="flex items-center gap-2">
                    <?= heroicon('shield-check', 'w-4 h-4 text-purple-600') ?>
                    <span class="text-xs font-bold">100% Safe & Automated</span>
                </div>
                <p class="text-[11px] text-purple-800/80 leading-relaxed">
                    All orders are transmitted automatically to direct API endpoints. In the rare event of an API rejection, full funds remain safely credited in your balance.
                </p>
            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
