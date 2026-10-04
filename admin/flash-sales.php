<?php
declare(strict_types=1);

$pageTitle = 'Flash Sales';
require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/FlashSales/FlashSaleManager.php';
require_once BASE_PATH . '/app/Services/ServiceManager.php';

use App\FlashSales\FlashSaleManager;
use App\Services\ServiceManager;

$db = getDB();
$services = ServiceManager::getAllServices();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $serviceId = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $discount = (float)($_POST['discount_percentage'] ?? 0);
        $startTime = $_POST['start_time'] ?? date('Y-m-d H:i:s');
        $endTime = $_POST['end_time'] ?? date('Y-m-d H:i:s', strtotime('+24 hours'));

        if (empty($title)) {
            $error = 'Sale title is required.';
        } elseif ($discount <= 0 || $discount > 99) {
            $error = 'Discount must be between 1% and 99%.';
        } else {
            $stmt = $db->prepare("
                INSERT INTO flash_sales (service_id, title, description, discount_percentage, start_time, end_time, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$serviceId, $title, $description, $discount, $startTime, $endTime]);
            logAudit($adminId, true, 'create_flash_sale', "Created flash sale: {$title}");
            flash('success', 'Flash sale created successfully.');
            header('Location: /admin/flash-sales.php');
            exit;
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("UPDATE flash_sales SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?")->execute([$id]);
        flash('success', 'Flash sale status toggled.');
        header('Location: /admin/flash-sales.php');
        exit;
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM flash_sales WHERE id = ?")->execute([$id]);
        flash('success', 'Flash sale deleted.');
        header('Location: /admin/flash-sales.php');
        exit;
    }
}

$sales = $db->query("
    SELECT fs.*, s.name as service_name, s.rate as original_rate
    FROM flash_sales fs
    LEFT JOIN services s ON fs.service_id = s.id
    ORDER BY fs.id DESC
")->fetchAll();
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight">Flash Sales & Limited Deals</h1>
            <p class="text-xs text-zinc-500">Configure promotional discount events and countdown campaigns.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- New Flash Sale Form -->
        <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-400">Create Flash Deal</h2>
            <form action="" method="POST" class="space-y-4 text-xs">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Deal Title</label>
                    <input type="text" name="title" required placeholder="e.g. 50% Off YouTube Views Mega Drop" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Target Service</label>
                    <select name="service_id" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                        <option value="">-- All Services in Category or General --</option>
                        <?php foreach ($services as $svc): ?>
                            <option value="<?= $svc['id'] ?>">#<?= $svc['id'] ?>: <?= e($svc['name']) ?> (<?= formatCurrency($svc['rate']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Discount Percentage (%)</label>
                    <input type="number" step="1" min="1" max="99" name="discount_percentage" required value="25" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Start Time</label>
                        <input type="datetime-local" name="start_time" value="<?= date('Y-m-d\TH:i') ?>" required class="w-full px-3 py-2 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">End Time</label>
                        <input type="datetime-local" name="end_time" value="<?= date('Y-m-d\TH:i', strtotime('+48 hours')) ?>" required class="w-full px-3 py-2 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 text-xs">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Description / Tagline</label>
                    <textarea name="description" rows="2" placeholder="Limited units available at this price!" class="w-full p-3 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600"></textarea>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold shadow-md shadow-purple-600/20 transition-all">
                    Launch Flash Sale
                </button>
            </form>
        </div>

        <!-- Sales List -->
        <div class="lg:col-span-2 bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-400">Active & Past Sales</h2>
            <div class="divide-y divide-zinc-100">
                <?php if (empty($sales)): ?>
                    <p class="text-center py-8 text-zinc-400 text-xs">No flash sales configured.</p>
                <?php else: ?>
                    <?php foreach ($sales as $s): 
                        $now = time();
                        $start = strtotime($s['start_time']);
                        $end = strtotime($s['end_time']);
                        $isLive = ($s['is_active'] && $now >= $start && $now <= $end);
                    ?>
                        <div class="py-4 flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full <?= $isLive ? 'bg-rose-100 text-rose-800' : 'bg-zinc-100 text-zinc-600' ?>">
                                        <?= $isLive ? 'LIVE NOW' : ($now < $start ? 'Upcoming' : 'Ended') ?>
                                    </span>
                                    <span class="text-xs font-black text-purple-600"><?= (float)$s['discount_percentage'] ?>% OFF</span>
                                </div>
                                <h3 class="font-bold text-sm text-zinc-900"><?= e($s['title']) ?></h3>
                                <?php if ($s['service_name']): ?>
                                    <p class="text-xs text-zinc-500">Service: <span class="font-semibold text-zinc-700"><?= e($s['service_name']) ?></span></p>
                                <?php endif; ?>
                                <p class="text-[11px] text-zinc-400">
                                    Period: <?= date('M d, H:i', $start) ?> &rarr; <?= date('M d, H:i', $end) ?>
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <form action="" method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg <?= $s['is_active'] ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' ?>">
                                        <?= $s['is_active'] ? 'Pause' : 'Activate' ?>
                                    </button>
                                </form>
                                <form action="" method="POST" onsubmit="return confirm('Delete deal?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="p-1 text-zinc-400 hover:text-rose-600">
                                        <?= heroicon('trash', 'w-4 h-4') ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
