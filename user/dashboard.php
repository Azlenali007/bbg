<?php
declare(strict_types=1);

$pageTitle = 'Dashboard';
$loadApexCharts = true;

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Orders/OrderManager.php';
require_once BASE_PATH . '/app/FlashSales/FlashSaleManager.php';

use App\Orders\OrderManager;
use App\FlashSales\FlashSaleManager;

$stats = OrderManager::getUserStats($userId);
$recentOrders = OrderManager::getUserOrders($userId, null, null, 1, 5)['orders'];
$activeFlashSales = FlashSaleManager::getActiveSales();

// Fetch 7-day real order stats for ApexCharts
$db = getDB();
$chartStmt = $db->prepare("
    SELECT DATE(created_at) as order_date, COUNT(*) as count, COALESCE(SUM(charge), 0) as daily_spent
    FROM orders
    WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY order_date ASC
");
$chartStmt->execute([$userId]);
$chartData = $chartStmt->fetchAll();

$hasChartData = !empty($chartData);
$dates = [];
$counts = [];
$spents = [];
foreach ($chartData as $row) {
    $dates[] = date('M j', strtotime($row['order_date']));
    $counts[] = (int)$row['count'];
    $spents[] = (float)$row['daily_spent'];
}

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="space-y-6">

    <!-- USER DASHBOARD HERO BANNER (Requirement 8) -->
    <div class="hero-content relative overflow-hidden bg-gradient-to-r from-purple-900 via-purple-800 to-purple-700 rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-purple-900/15 border border-purple-600/30">
        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="max-w-xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-semibold text-purple-200 border border-white/10 mb-3">
                    <?= heroicon('sparkles', 'w-3.5 h-3.5 text-purple-300') ?>
                    <span>Premium Automated SMM Network</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-white leading-tight">
                    Welcome back, <?= e($user['username']) ?>!
                </h2>
                <p class="text-purple-100 text-sm mt-1.5 leading-relaxed">
                    Scale your social presence with millisecond-speed automated dispatch and transparent real-time tracking.
                </p>
            </div>

            <!-- Hero CTAs -->
            <div class="flex flex-wrap items-center gap-3">
                <a href="/user/new-order.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white hover:bg-purple-50 text-purple-900 text-sm font-bold shadow-lg shadow-black/10 transition-all hover:scale-[1.02]">
                    <?= heroicon('plus-circle', 'w-4 h-4 text-purple-700') ?>
                    <span>New Order</span>
                </a>
                <a href="/user/add-funds.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-semibold backdrop-blur-sm transition-all">
                    <?= heroicon('wallet', 'w-4 h-4') ?>
                    <span>Add Funds</span>
                </a>
            </div>
        </div>

        <!-- Decorative ambient circles -->
        <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-1/3 -top-12 w-48 h-48 bg-purple-400/20 rounded-full blur-2xl pointer-events-none"></div>
    </div>

    <!-- ACTIVE FLASH SALE BANNER (If configured in MySQL) (Requirement 9 & 10) -->
    <?php if (!empty($activeFlashSales)): ?>
        <?php foreach ($activeFlashSales as $sale): ?>
            <div class="flash-sale-card bg-gradient-to-r from-purple-50 via-white to-purple-50/50 border border-purple-200 rounded-3xl p-5 sm:p-6 shadow-sm relative overflow-hidden" 
                 x-data="flashSaleCountdown(<?= (int)$sale['remaining_seconds'] ?>)">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-purple-600 text-white flex items-center justify-center flex-shrink-0 shadow-md shadow-purple-600/30">
                            <?= heroicon('bolt', 'w-6 h-6 text-white') ?>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full bg-purple-600 text-white text-[10px] font-black uppercase tracking-wider">
                                    Flash Deal
                                </span>
                                <?php if (!empty($sale['discount_percentage'])): ?>
                                    <span class="text-xs font-bold text-purple-700">
                                        <?= (float)$sale['discount_percentage'] ?>% OFF
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="text-base font-bold text-zinc-900 mt-1"><?= e($sale['title']) ?></h3>
                            <p class="text-xs text-zinc-500 mt-0.5 leading-relaxed"><?= e($sale['description'] ?? 'Limited time promo on premium high-retention services.') ?></p>
                        </div>
                    </div>

                    <!-- Countdown & Action -->
                    <div class="flex items-center gap-4 self-end sm:self-center">
                        <div class="flex items-center gap-1.5 text-center">
                            <div class="bg-white border border-purple-200 rounded-xl px-2.5 py-1 shadow-sm">
                                <span class="text-sm font-black text-purple-900" x-text="format(hours)">00</span>
                                <span class="text-[9px] block text-zinc-400 uppercase font-medium">Hrs</span>
                            </div>
                            <span class="font-bold text-purple-400">:</span>
                            <div class="bg-white border border-purple-200 rounded-xl px-2.5 py-1 shadow-sm">
                                <span class="text-sm font-black text-purple-900" x-text="format(minutes)">00</span>
                                <span class="text-[9px] block text-zinc-400 uppercase font-medium">Min</span>
                            </div>
                            <span class="font-bold text-purple-400">:</span>
                            <div class="bg-white border border-purple-200 rounded-xl px-2.5 py-1 shadow-sm">
                                <span class="text-sm font-black text-purple-900" x-text="format(seconds)">00</span>
                                <span class="text-[9px] block text-zinc-400 uppercase font-medium">Sec</span>
                            </div>
                        </div>

                        <a href="/user/new-order.php?service_id=<?= (int)$sale['service_id'] ?>" 
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/20 transition-all">
                            <span>Claim Deal</span>
                            <?= heroicon('arrow-right-on-rectangle', 'w-3.5 h-3.5') ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- STATISTIC CARDS (Requirement 23) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Balance -->
        <div class="stat-card bg-white p-5 rounded-3xl border border-zinc-200/80 shadow-sm shadow-purple-500/5 hover:border-purple-200 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500">Wallet Balance</span>
                <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <?= heroicon('wallet', 'w-4 h-4') ?>
                </div>
            </div>
            <div class="mt-2 text-2xl font-black text-zinc-900 tracking-tight">
                <?= formatCurrency($user['wallet_balance'] ?? 0) ?>
            </div>
            <a href="/user/add-funds.php" class="mt-2 text-[11px] font-semibold text-purple-600 hover:text-purple-700 inline-flex items-center gap-1">
                <span>Add funds</span>
                <?= heroicon('chevron-right', 'w-3 h-3') ?>
            </a>
        </div>

        <!-- Total Orders -->
        <div class="stat-card bg-white p-5 rounded-3xl border border-zinc-200/80 shadow-sm shadow-purple-500/5 hover:border-purple-200 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500">Total Orders</span>
                <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <?= heroicon('shopping-cart', 'w-4 h-4') ?>
                </div>
            </div>
            <div class="mt-2 text-2xl font-black text-zinc-900 tracking-tight">
                <?= number_format((int)$stats['total_orders']) ?>
            </div>
            <span class="mt-2 text-[11px] text-zinc-400 block">Lifetime orders count</span>
        </div>

        <!-- Completed Orders -->
        <div class="stat-card bg-white p-5 rounded-3xl border border-zinc-200/80 shadow-sm shadow-purple-500/5 hover:border-purple-200 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500">Completed</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <?= heroicon('check-circle', 'w-4 h-4') ?>
                </div>
            </div>
            <div class="mt-2 text-2xl font-black text-zinc-900 tracking-tight text-emerald-600">
                <?= number_format((int)$stats['completed']) ?>
            </div>
            <span class="mt-2 text-[11px] text-zinc-400 block">Delivered successfully</span>
        </div>

        <!-- Processing / Pending -->
        <div class="stat-card bg-white p-5 rounded-3xl border border-zinc-200/80 shadow-sm shadow-purple-500/5 hover:border-purple-200 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500">In Progress</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <?= heroicon('clock', 'w-4 h-4') ?>
                </div>
            </div>
            <div class="mt-2 text-2xl font-black text-zinc-900 tracking-tight text-amber-600">
                <?= number_format((int)$stats['processing'] + (int)$stats['pending']) ?>
            </div>
            <div class="mt-2 flex items-center gap-2 text-[11px] text-zinc-400">
                <span><?= (int)$stats['processing'] ?> processing</span>
                <span>&bull;</span>
                <span><?= (int)$stats['pending'] ?> pending</span>
            </div>
        </div>
    </div>

    <!-- CHARTS & RECENT ORDERS GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Activity Chart (Requirement 24: Real ApexCharts data) -->
        <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-zinc-200/80 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900">7-Day Order Activity</h3>
                    <p class="text-xs text-zinc-400">Real-time daily order volume</p>
                </div>
                <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <?= heroicon('chart-bar', 'w-4 h-4') ?>
                </div>
            </div>

            <?php if ($hasChartData): ?>
                <div id="activity-chart" class="w-full h-64"></div>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const options = {
                            series: [{
                                name: 'Orders Placed',
                                data: <?= json_encode($counts) ?>
                            }],
                            chart: {
                                type: 'area',
                                height: 260,
                                toolbar: { show: false },
                                zoom: { enabled: false },
                                fontFamily: 'inherit'
                            },
                            colors: ['#7C3AED'],
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shadeIntensity: 1,
                                    opacityFrom: 0.35,
                                    opacityTo: 0.05,
                                    stops: [0, 90, 100]
                                }
                            },
                            dataLabels: { enabled: false },
                            stroke: { curve: 'smooth', width: 2.5 },
                            xaxis: {
                                categories: <?= json_encode($dates) ?>,
                                axisBorder: { show: false },
                                axisTicks: { show: false },
                                labels: { style: { colors: '#71717A', fontSize: '11px' } }
                            },
                            yaxis: {
                                labels: { style: { colors: '#71717A', fontSize: '11px' } }
                            },
                            grid: {
                                borderColor: '#F4F4F5',
                                strokeDashArray: 4
                            },
                            tooltip: {
                                theme: 'light',
                                y: { formatter: val => val + ' orders' }
                            }
                        };
                        const chart = new ApexCharts(document.querySelector("#activity-chart"), options);
                        chart.render();
                    });
                </script>
            <?php else: ?>
                <!-- Clean Empty State (Requirement 2: No fake chart values!) -->
                <div class="h-64 flex flex-col items-center justify-center text-center p-6 bg-slate-50/60 rounded-2xl border border-dashed border-zinc-200">
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mb-3">
                        <?= heroicon('chart-bar', 'w-6 h-6') ?>
                    </div>
                    <h4 class="text-sm font-bold text-zinc-800">No Activity Yet</h4>
                    <p class="text-xs text-zinc-500 max-w-xs mt-1">Place your first order to see real-time volume metrics and analytics displayed here.</p>
                    <a href="/user/new-order.php" class="mt-4 px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-semibold shadow-md shadow-purple-600/20 hover:bg-purple-700 transition-colors">
                        Place First Order
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Order Shortcut -->
        <div class="bg-white rounded-3xl p-6 border border-zinc-200/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="w-10 h-10 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center mb-4">
                    <?= heroicon('shopping-cart', 'w-5 h-5') ?>
                </div>
                <h3 class="text-base font-bold text-zinc-900">Need instant growth?</h3>
                <p class="text-xs text-zinc-500 mt-1 leading-relaxed">
                    Select from thousands of high-retention services across Instagram, YouTube, TikTok, Telegram and Twitter.
                </p>

                <div class="mt-5 space-y-2.5 text-xs text-zinc-600">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Auto-dispatch within seconds</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Full refill & cancel protection</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>24/7 dedicated support</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-zinc-100 flex flex-col gap-2">
                <a href="/user/new-order.php" class="w-full text-center px-4 py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/20 transition-all">
                    Create New Order
                </a>
                <a href="/user/services.php" class="w-full text-center px-4 py-2.5 rounded-xl border border-zinc-200 hover:bg-zinc-50 text-zinc-700 text-xs font-semibold transition-all">
                    Browse Service Rates
                </a>
            </div>
        </div>

    </div>

    <!-- RECENT ORDERS TABLE (Requirement 40) -->
    <div class="bg-white rounded-3xl border border-zinc-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-zinc-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-zinc-900">Recent Orders</h3>
                <p class="text-xs text-zinc-400">Latest orders placed on your account</p>
            </div>
            <a href="/user/orders.php" class="text-xs font-semibold text-purple-600 hover:text-purple-800 flex items-center gap-1">
                <span>View all</span>
                <?= heroicon('chevron-right', 'w-3 h-3') ?>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50/80 text-zinc-500 font-semibold border-b border-zinc-100">
                    <tr>
                        <th class="py-3.5 px-6">ID</th>
                        <th class="py-3.5 px-6">Service</th>
                        <th class="py-3.5 px-6">Link</th>
                        <th class="py-3.5 px-6">Quantity</th>
                        <th class="py-3.5 px-6">Charge</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-700">
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="7" class="py-12 text-center text-zinc-400">
                                <div class="w-10 h-10 rounded-2xl bg-zinc-100 text-zinc-400 flex items-center justify-center mx-auto mb-2">
                                    <?= heroicon('shopping-cart', 'w-5 h-5') ?>
                                </div>
                                <p class="text-xs font-medium text-zinc-600">No orders found</p>
                                <p class="text-[11px] text-zinc-400 mt-0.5">You have not placed any orders yet.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr class="hover:bg-zinc-50/80 transition-colors">
                                <td class="py-3.5 px-6 font-mono font-bold text-purple-700">#<?= $order['id'] ?></td>
                                <td class="py-3.5 px-6 font-semibold text-zinc-900 max-w-xs truncate">
                                    <?= e($order['service_name']) ?>
                                </td>
                                <td class="py-3.5 px-6 text-zinc-500 max-w-[200px] truncate">
                                    <a href="<?= e($order['link']) ?>" target="_blank" rel="noopener" class="hover:underline flex items-center gap-1 text-purple-600">
                                        <?= heroicon('link', 'w-3 h-3') ?>
                                        <span class="truncate"><?= e($order['link']) ?></span>
                                    </a>
                                </td>
                                <td class="py-3.5 px-6 font-medium"><?= number_format($order['quantity']) ?></td>
                                <td class="py-3.5 px-6 font-bold text-zinc-900"><?= formatCurrency($order['charge']) ?></td>
                                <td class="py-3.5 px-6">
                                    <?php
                                    $st = $order['status'];
                                    $badgeClass = match($st) {
                                        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'processing' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'partial' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'cancelled', 'refunded', 'failed' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        default => 'bg-zinc-100 text-zinc-700 border-zinc-200'
                                    };
                                    ?>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border <?= $badgeClass ?>">
                                        <?= ucfirst($st) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 text-zinc-400 whitespace-nowrap">
                                    <?= date('M j, Y H:i', strtotime($order['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
