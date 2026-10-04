<?php
declare(strict_types=1);

$pageTitle = 'Reports & Analytics';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Overall Metrics
$totalRevenue = (float)$db->query("SELECT COALESCE(SUM(charge), 0) FROM orders WHERE status != 'canceled' AND status != 'refunded'")->fetchColumn();
$totalOrders = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$completedOrders = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn();
$totalDeposits = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Last 7 days revenue for ApexCharts
$dailyStmt = $db->query("
    SELECT DATE(created_at) as date, COUNT(*) as count, COALESCE(SUM(charge), 0) as revenue
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
      AND status != 'canceled' AND status != 'refunded'
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$dailyStats = $dailyStmt->fetchAll();

$dates = [];
$revenues = [];
$orderCounts = [];
foreach ($dailyStats as $d) {
    $dates[] = date('M d', strtotime($d['date']));
    $revenues[] = round((float)$d['revenue'], 2);
    $orderCounts[] = (int)$d['count'];
}

// Top 5 Services
$topServicesStmt = $db->query("
    SELECT s.name, COUNT(o.id) as order_count, SUM(o.charge) as total_charge
    FROM orders o
    JOIN services s ON o.service_id = s.id
    GROUP BY s.id
    ORDER BY total_charge DESC
    LIMIT 5
");
$topServices = $topServicesStmt->fetchAll();
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight">Reports & Business Intelligence</h1>
            <p class="text-xs text-zinc-500">Live operational financial telemetry and order trends.</p>
        </div>
    </div>

    <!-- Stat KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-5 shadow-sm">
            <span class="text-xs font-semibold text-zinc-500">Total Order Revenue</span>
            <div class="text-2xl font-black text-zinc-900 mt-1"><?= formatCurrency($totalRevenue) ?></div>
            <span class="text-[10px] text-emerald-600 font-bold mt-1 block">Live aggregated charges</span>
        </div>
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-5 shadow-sm">
            <span class="text-xs font-semibold text-zinc-500">Total Deposits Inflow</span>
            <div class="text-2xl font-black text-purple-600 mt-1"><?= formatCurrency($totalDeposits) ?></div>
            <span class="text-[10px] text-zinc-400 mt-1 block">Completed gateway payments</span>
        </div>
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-5 shadow-sm">
            <span class="text-xs font-semibold text-zinc-500">Total Orders Processed</span>
            <div class="text-2xl font-black text-zinc-900 mt-1"><?= number_format($totalOrders) ?></div>
            <span class="text-[10px] text-zinc-500 mt-1 block"><?= number_format($completedOrders) ?> completed</span>
        </div>
        <div class="bg-white border border-zinc-200/80 rounded-2xl p-5 shadow-sm">
            <span class="text-xs font-semibold text-zinc-500">Registered Accounts</span>
            <div class="text-2xl font-black text-zinc-900 mt-1"><?= number_format($totalUsers) ?></div>
            <span class="text-[10px] text-zinc-400 mt-1 block">Active platform users</span>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-400">7-Day Revenue Trend</h2>
            <div id="revenueChart" class="w-full h-72"></div>
        </div>

        <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-400">Top Selling Services</h2>
            <div class="divide-y divide-zinc-100">
                <?php if (empty($topServices)): ?>
                    <p class="text-xs text-zinc-400 text-center py-6">No service orders yet.</p>
                <?php else: ?>
                    <?php foreach ($topServices as $ts): ?>
                        <div class="py-3 flex items-center justify-between">
                            <div class="min-w-0 pr-2">
                                <h4 class="text-xs font-bold text-zinc-900 truncate"><?= e($ts['name']) ?></h4>
                                <span class="text-[10px] text-zinc-400"><?= number_format($ts['order_count']) ?> orders</span>
                            </div>
                            <span class="text-xs font-black text-purple-600 whitespace-nowrap"><?= formatCurrency($ts['total_charge']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const dates = <?= json_encode($dates) ?>;
        const revenues = <?= json_encode($revenues) ?>;

        const options = {
            chart: {
                type: 'area',
                height: 280,
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            colors: ['#7C3AED'],
            series: [{
                name: 'Revenue (<?= CURRENCY ?>)',
                data: revenues.length ? revenues : [0]
            }],
            xaxis: {
                categories: dates.length ? dates : ['No Data'],
                labels: { style: { colors: '#71717A', fontSize: '11px' } }
            },
            yaxis: {
                labels: { 
                    style: { colors: '#71717A', fontSize: '11px' },
                    formatter: val => '$' + val.toFixed(2)
                }
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [20, 100]
                }
            },
            grid: { borderColor: '#F4F4F5' },
            tooltip: { theme: 'light' }
        };

        const chart = new ApexCharts(document.querySelector("#revenueChart"), options);
        chart.render();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
