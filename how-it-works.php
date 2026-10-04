<?php
declare(strict_types=1);

$pageTitle = 'How It Works';

require_once __DIR__ . '/includes/header.php';
?>

<div class="py-16 bg-slate-50 min-h-[80vh]">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        
        <div class="text-center space-y-2 max-w-2xl mx-auto">
            <h2 class="text-xs font-bold text-purple-600 uppercase tracking-widest">Simple & Fast</h2>
            <h1 class="text-3xl sm:text-4xl font-black text-zinc-900 tracking-tight">How ApexSMM Works</h1>
            <p class="text-xs sm:text-sm text-zinc-500">Accelerate your social footprint in four effortless steps with enterprise-grade automation.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Step 1 -->
            <div class="bg-white rounded-3xl p-8 border border-zinc-200/80 shadow-sm space-y-4 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center font-black text-lg">
                        01
                    </div>
                    <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2.5 py-1 rounded-full">30 Seconds</span>
                </div>
                <h3 class="text-lg font-bold text-zinc-900">Create Free Account</h3>
                <p class="text-xs text-zinc-600 leading-relaxed">
                    Register with your email and username. No complex verification or credit card required to explore the platform.
                </p>
            </div>

            <!-- Step 2 -->
            <div class="bg-white rounded-3xl p-8 border border-zinc-200/80 shadow-sm space-y-4 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center font-black text-lg">
                        02
                    </div>
                    <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2.5 py-1 rounded-full">Instant Credit</span>
                </div>
                <h3 class="text-lg font-bold text-zinc-900">Fund Your Wallet</h3>
                <p class="text-xs text-zinc-600 leading-relaxed">
                    Choose from Stripe, PayPal, or major Cryptocurrencies. Your deposit is credited automatically with 256-bit encryption.
                </p>
            </div>

            <!-- Step 3 -->
            <div class="bg-white rounded-3xl p-8 border border-zinc-200/80 shadow-sm space-y-4 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center font-black text-lg">
                        03
                    </div>
                    <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2.5 py-1 rounded-full">Precision Choice</span>
                </div>
                <h3 class="text-lg font-bold text-zinc-900">Select Service & Enter Link</h3>
                <p class="text-xs text-zinc-600 leading-relaxed">
                    Search our live catalog, input your public link, and choose your quantity. Live pricing is calculated transparently.
                </p>
            </div>

            <!-- Step 4 -->
            <div class="bg-white rounded-3xl p-8 border border-zinc-200/80 shadow-sm space-y-4 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center font-black text-lg">
                        04
                    </div>
                    <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full">Auto-Dispatch</span>
                </div>
                <h3 class="text-lg font-bold text-zinc-900">Automated Dispatch & Tracking</h3>
                <p class="text-xs text-zinc-600 leading-relaxed">
                    Our high-throughput engine transmits your order directly to the provider endpoint with real-time status and counter updates.
                </p>
            </div>
        </div>

        <div class="text-center pt-6">
            <a href="/register.php" class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-sm shadow-xl shadow-purple-600/25 transition-all hover:scale-105">
                <span>Start Growing Now</span>
                <?= heroicon('arrow-right-on-rectangle', 'w-4 h-4') ?>
            </a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
