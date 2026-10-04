</main>

<!-- Public Footer -->
<footer class="bg-white border-t border-zinc-200 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Brand Info -->
            <div class="md:col-span-1 space-y-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-purple-800 to-purple-600 flex items-center justify-center text-white shadow-sm">
                        <?= heroicon('bolt', 'w-4 h-4 text-white') ?>
                    </div>
                    <span class="font-bold text-lg text-zinc-900"><?= e(getSetting('site_name', APP_NAME)) ?></span>
                </div>
                <p class="text-sm text-zinc-500 leading-relaxed">
                    Production-grade SMM automation engine. Supercharge your social media presence with instant delivery and automated API synchronization.
                </p>
                <div class="flex items-center gap-2 text-xs font-medium text-emerald-600">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>All Systems Operational</span>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-4">Platform</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="/services.php" class="text-zinc-600 hover:text-purple-600 transition-colors">Service Catalog</a></li>
                    <li><a href="/how-it-works.php" class="text-zinc-600 hover:text-purple-600 transition-colors">How It Works</a></li>
                    <li><a href="/faq.php" class="text-zinc-600 hover:text-purple-600 transition-colors">Frequently Asked Questions</a></li>
                    <li><a href="/contact.php" class="text-zinc-600 hover:text-purple-600 transition-colors">Contact Support</a></li>
                </ul>
            </div>

            <!-- Account -->
            <div>
                <h4 class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-4">Account</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="/login.php" class="text-zinc-600 hover:text-purple-600 transition-colors">Sign In</a></li>
                    <li><a href="/register.php" class="text-zinc-600 hover:text-purple-600 transition-colors">Create Account</a></li>
                    <li><a href="/user/dashboard.php" class="text-zinc-600 hover:text-purple-600 transition-colors">Client Portal</a></li>
                    <li><a href="/admin/login.php" class="text-zinc-400 hover:text-zinc-600 transition-colors">Admin Console</a></li>
                </ul>
            </div>

            <!-- Security & Guarantee -->
            <div>
                <h4 class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-4">Security & Trust</h4>
                <div class="space-y-3 text-sm text-zinc-500">
                    <div class="flex items-start gap-2.5">
                        <?= heroicon('shield-check', 'w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5') ?>
                        <span>Protected by 256-bit encryption & real-time fraud mitigation.</span>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <?= heroicon('bolt', 'w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5') ?>
                        <span>Instant automated dispatch with 24/7 API failover.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-12 pt-8 border-t border-zinc-100 flex flex-col sm:flex-row items-center justify-between text-xs text-zinc-500 gap-4">
            <p>&copy; <?= date('Y') ?> <?= e(getSetting('site_name', APP_NAME)) ?>. All rights reserved.</p>
            <div class="flex items-center gap-6">
                <span>Fast &bull; Secure &bull; Reliable</span>
            </div>
        </div>
    </div>
</footer>

<?php if ($flashSuccess = flash('success')): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        ApexToast.success(<?= json_encode($flashSuccess) ?>);
    });
</script>
<?php endif; ?>

<?php if ($flashError = flash('error')): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        ApexToast.error(<?= json_encode($flashError) ?>);
    });
</script>
<?php endif; ?>

</body>
</html>
