<?php
declare(strict_types=1);

$pageTitle = 'Home - Premier Automated SMM Panel';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/app/Banners/BannerManager.php';
require_once BASE_PATH . '/app/Services/ServiceManager.php';
require_once BASE_PATH . '/app/FlashSales/FlashSaleManager.php';

use App\Banners\BannerManager;
use App\Services\ServiceManager;
use App\FlashSales\FlashSaleManager;

$banners = BannerManager::getActiveBanners();
$categories = ServiceManager::getCategories(true);
$flashSales = FlashSaleManager::getActiveSales();
?>

<!-- HERO SECTION (Requirement 6 & 7) -->
<section class="relative overflow-hidden bg-gradient-to-b from-purple-50/60 via-white to-white py-16 sm:py-24 lg:py-32">
    <!-- Subtle Ambient Glows -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-purple-400/10 blur-[120px] rounded-full pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <?php if (!empty($banners)): ?>
            <!-- Dynamic MySQL-Backed Banner Slider (Requirement 7) -->
            <div x-data="{ current: 0, total: <?= count($banners) ?> }" class="relative overflow-hidden rounded-3xl border border-purple-200 shadow-xl shadow-purple-600/10 mb-12 bg-white">
                <?php foreach ($banners as $idx => $b): ?>
                    <div x-show="current === <?= $idx ?>" x-transition.opacity.duration.300ms class="p-8 sm:p-12 flex flex-col md:flex-row items-center justify-between gap-8 bg-gradient-to-br from-purple-900 via-purple-800 to-purple-700 text-white">
                        <div class="max-w-xl space-y-4">
                            <span class="inline-block px-3 py-1 bg-white/10 backdrop-blur-md rounded-full text-xs font-bold text-purple-200 uppercase tracking-wider">
                                Featured Announcement
                            </span>
                            <h2 class="text-2xl sm:text-4xl font-black tracking-tight leading-tight"><?= e($b['title']) ?></h2>
                            <?php if (!empty($b['subtitle'])): ?>
                                <p class="text-sm text-purple-100 leading-relaxed"><?= e($b['subtitle']) ?></p>
                            <?php endif; ?>
                            <div class="pt-2">
                                <a href="<?= e($b['cta_url'] ?? '/register.php') ?>" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-white text-purple-900 font-bold text-sm shadow-lg hover:bg-purple-50 transition-all">
                                    <span><?= e($b['cta_text'] ?? 'Get Started') ?></span>
                                    <?= heroicon('arrow-right-on-rectangle', 'w-4 h-4 text-purple-700') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (count($banners) > 1): ?>
                    <!-- Slider controls -->
                    <div class="absolute bottom-4 right-4 flex items-center gap-1.5 z-20">
                        <?php foreach ($banners as $idx => $b): ?>
                            <button @click="current = <?= $idx ?>" 
                                    :class="current === <?= $idx ?> ? 'w-6 bg-white' : 'w-2 bg-white/40'" 
                                    class="h-2 rounded-full transition-all"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Default SaaS Hero Banner (Requirement 6) -->
            <div class="hero-content text-center max-w-3xl mx-auto space-y-6">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200/80 text-xs font-bold shadow-sm">
                    <?= heroicon('sparkles', 'w-4 h-4 text-purple-600') ?>
                    <span>Automated Social Marketing Engine</span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-zinc-900 tracking-tight leading-[1.1]">
                    Scale Social Reach With <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-700 to-purple-500">
                        Instant Precision Delivery
                    </span>
                </h1>

                <p class="text-base sm:text-lg text-zinc-600 leading-relaxed font-normal max-w-2xl mx-auto">
                    The enterprise growth gateway for agencies, resellers, and digital brands. Automated order dispatch, multi-currency payments, and direct API failover.
                </p>

                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="/register.php" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-sm shadow-xl shadow-purple-600/25 transition-all hover:scale-[1.02]">
                        <span>Create Free Account</span>
                        <?= heroicon('arrow-right-on-rectangle', 'w-4 h-4') ?>
                    </a>
                    <a href="/services.php" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-2xl bg-white border border-zinc-200 hover:bg-zinc-50 text-zinc-800 font-bold text-sm transition-all">
                        <span>View Services & Rates</span>
                        <?= heroicon('cube', 'w-4 h-4 text-purple-600') ?>
                    </a>
                </div>

                <div class="pt-8 flex items-center justify-center gap-6 text-xs text-zinc-500 font-medium">
                    <div class="flex items-center gap-2">
                        <?= heroicon('check-circle', 'w-4 h-4 text-emerald-600') ?>
                        <span>Instant Delivery</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <?= heroicon('check-circle', 'w-4 h-4 text-emerald-600') ?>
                        <span>Real-Time Tracking</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <?= heroicon('check-circle', 'w-4 h-4 text-emerald-600') ?>
                        <span>Reseller Ready API</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- FLASH SALE SECTION (If active in MySQL) -->
<?php if (!empty($flashSales)): ?>
<section class="py-8 bg-zinc-50/60 border-y border-zinc-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2 mb-4">
            <span class="w-2.5 h-2.5 rounded-full bg-purple-600 animate-ping"></span>
            <h3 class="text-xs font-bold uppercase tracking-wider text-purple-900">Active Flash Promos</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($flashSales as $sale): ?>
                <div class="p-5 rounded-3xl bg-white border border-purple-200 shadow-sm flex items-center justify-between gap-4"
                     x-data="flashSaleCountdown(<?= (int)$sale['remaining_seconds'] ?>)">
                    <div>
                        <span class="text-[10px] font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full">Limited Promo</span>
                        <h4 class="text-sm font-bold text-zinc-900 mt-1"><?= e($sale['title']) ?></h4>
                        <p class="text-xs text-zinc-500 mt-0.5"><?= e($sale['description']) ?></p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-xs font-mono font-bold text-purple-700 bg-purple-50 px-2.5 py-1 rounded-xl">
                            <span x-text="format(hours)">00</span>:<span x-text="format(minutes)">00</span>:<span x-text="format(seconds)">00</span>
                        </div>
                        <a href="/login.php" class="text-[11px] font-bold text-purple-600 hover:text-purple-800 mt-2 block">
                            Order Now &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- KEY PLATFORM ADVANTAGES -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 space-y-2">
            <h2 class="text-xs font-bold text-purple-600 uppercase tracking-widest">Why Choose Us</h2>
            <p class="text-3xl font-black text-zinc-900 tracking-tight">Built For High Reliability & Performance</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="p-8 rounded-3xl bg-slate-50 border border-zinc-200/80 hover:border-purple-200 transition-all space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center">
                    <?= heroicon('bolt', 'w-6 h-6') ?>
                </div>
                <h3 class="text-lg font-bold text-zinc-900">Direct API Integration</h3>
                <p class="text-sm text-zinc-500 leading-relaxed">
                    Our platform executes orders through low-latency direct API connectors. No manual holds or waiting periods.
                </p>
            </div>

            <div class="p-8 rounded-3xl bg-slate-50 border border-zinc-200/80 hover:border-purple-200 transition-all space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center">
                    <?= heroicon('wallet', 'w-6 h-6') ?>
                </div>
                <h3 class="text-lg font-bold text-zinc-900">Instant Automated Deposits</h3>
                <p class="text-sm text-zinc-500 leading-relaxed">
                    Load your account effortlessly using Cards, Crypto, or PayPal with cryptographic webhook verification.
                </p>
            </div>

            <div class="p-8 rounded-3xl bg-slate-50 border border-zinc-200/80 hover:border-purple-200 transition-all space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center">
                    <?= heroicon('shield-check', 'w-6 h-6') ?>
                </div>
                <h3 class="text-lg font-bold text-zinc-900">Auto Refill & Guarantee</h3>
                <p class="text-sm text-zinc-500 leading-relaxed">
                    Supported services come with automatic refill tracking. If an order cannot be fulfilled, balance is safely refunded.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- SERVICES PREVIEW -->
<section class="py-20 bg-slate-50 border-t border-zinc-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4">
            <div>
                <h2 class="text-xs font-bold text-purple-600 uppercase tracking-widest">Global Catalog</h2>
                <p class="text-3xl font-black text-zinc-900 tracking-tight mt-1">Available Service Categories</p>
            </div>
            <a href="/services.php" class="inline-flex items-center gap-1 text-sm font-bold text-purple-600 hover:text-purple-800">
                <span>View Full Catalog</span>
                <?= heroicon('chevron-right', 'w-4 h-4') ?>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($categories)): ?>
                <div class="col-span-3 py-12 text-center text-zinc-400 bg-white rounded-3xl border border-zinc-200">
                    <p class="text-sm font-medium">Services catalog currently updating.</p>
                </div>
            <?php else: ?>
                <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
                    <a href="/services.php?category=<?= $cat['id'] ?>" 
                       class="p-6 rounded-3xl bg-white border border-zinc-200 hover:border-purple-300 shadow-sm hover:shadow-purple-600/5 transition-all flex items-center justify-between group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                                <?= heroicon($cat['icon'] ?? 'cube', 'w-6 h-6') ?>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-zinc-900 group-hover:text-purple-600 transition-colors"><?= e($cat['name']) ?></h3>
                                <span class="text-xs text-zinc-400 mt-0.5 block"><?= (int)$cat['service_count'] ?> Active Services</span>
                            </div>
                        </div>
                        <div class="text-zinc-300 group-hover:text-purple-600 transition-colors">
                            <?= heroicon('chevron-right', 'w-5 h-5') ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CALL TO ACTION -->
<section class="py-20 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-purple-900 via-purple-800 to-purple-700 rounded-3xl p-8 sm:p-14 text-white text-center shadow-xl shadow-purple-900/10 space-y-6 relative overflow-hidden">
            <h2 class="text-3xl sm:text-4xl font-black tracking-tight leading-tight">
                Ready to accelerate your social media growth?
            </h2>
            <p class="text-purple-100 text-sm max-w-xl mx-auto leading-relaxed">
                Sign up in under 30 seconds. Fund your wallet with zero hassle and deploy high-retention services instantly.
            </p>
            <div class="pt-2">
                <a href="/register.php" class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl bg-white hover:bg-purple-50 text-purple-900 font-bold text-sm shadow-xl transition-all hover:scale-105">
                    <span>Create Your Free Account</span>
                    <?= heroicon('arrow-right-on-rectangle', 'w-4 h-4 text-purple-700') ?>
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
