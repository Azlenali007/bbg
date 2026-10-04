<?php
declare(strict_types=1);

$pageTitle = 'Frequently Asked Questions';

require_once __DIR__ . '/includes/header.php';

$faqs = [
    [
        'q' => 'What is an SMM Panel and how does it work?',
        'a' => 'An SMM (Social Media Marketing) panel is an automated web platform where agencies, influencers, and brands purchase social engagement (followers, likes, views, comments) via low-latency API connections for instant fulfillment.'
    ],
    [
        'q' => 'How long does it take for orders to start delivering?',
        'a' => 'Most services start delivering within seconds to a few minutes after submission. Some high-retention gradual drip-feed services deliver naturally over several hours or days to mimic organic growth.'
    ],
    [
        'q' => 'What does "Refill" mean?',
        'a' => 'If a service includes Refill support and you experience any natural engagement drops within the warranty period (e.g. 30 days), the system will automatically top up the missing engagement at zero additional cost.'
    ],
    [
        'q' => 'What payment methods do you support?',
        'a' => 'We support Credit/Debit Cards via Stripe, PayPal Checkout, major Cryptocurrencies (Bitcoin, Ethereum, USDT, Litecoin), and Direct Bank Wire transfers.'
    ],
    [
        'q' => 'Can I connect my own website or bot via API?',
        'a' => 'Yes. Every registered user receives a secret API key. We provide a full REST-compatible Standard SMM v2 API specification for automated order placement and real-time status polling.'
    ],
    [
        'q' => 'What happens if an order fails or cannot be completed?',
        'a' => 'If an order is cancelled or cannot be fulfilled due to private account settings or network blocks, the remaining unfulfilled portion is automatically refunded directly to your account balance.'
    ]
];
?>

<div class="py-16 bg-slate-50 min-h-[80vh]">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="text-center space-y-2">
            <h2 class="text-xs font-bold text-purple-600 uppercase tracking-widest">Help Center</h2>
            <h1 class="text-3xl sm:text-4xl font-black text-zinc-900 tracking-tight">Frequently Asked Questions</h1>
            <p class="text-xs sm:text-sm text-zinc-500 max-w-lg mx-auto">Everything you need to know about our services, ordering process, and automated API.</p>
        </div>

        <div class="space-y-4">
            <?php foreach ($faqs as $faq): ?>
                <div class="bg-white rounded-3xl p-6 border border-zinc-200/80 shadow-sm space-y-2" x-data="{ open: false }">
                    <button @click="open = !open" class="w-full text-left flex items-center justify-between gap-4 focus:outline-none">
                        <span class="text-sm font-bold text-zinc-900"><?= e($faq['q']) ?></span>
                        <div class="text-purple-600 p-1 rounded-xl bg-purple-50 flex-shrink-0" :class="open ? 'rotate-180' : ''">
                            <?= heroicon('chevron-down', 'w-4 h-4 transition-transform duration-200') ?>
                        </div>
                    </button>
                    <div x-show="open" x-cloak class="pt-2 text-xs text-zinc-600 leading-relaxed border-t border-zinc-100">
                        <?= e($faq['a']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center p-8 bg-white rounded-3xl border border-zinc-200/80 shadow-sm space-y-3">
            <h3 class="text-base font-bold text-zinc-900">Still have questions?</h3>
            <p class="text-xs text-zinc-500">Our customer support specialists are ready to help 24/7.</p>
            <a href="/contact.php" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/20 transition-all">
                <span>Contact Support</span>
                <?= heroicon('arrow-right-on-rectangle', 'w-3.5 h-3.5') ?>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
