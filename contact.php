<?php
declare(strict_types=1);

$pageTitle = 'Contact Us';

require_once __DIR__ . '/includes/header.php';
require_once BASE_PATH . '/includes/csrf.php';

$sentSuccess = null;
$sentError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $msg = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($msg)) {
        $sentError = 'Please fill out all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $sentError = 'Please provide a valid email address.';
    } else {
        // Log contact message and create notification for admin
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
            VALUES (NULL, CONCAT('New Inquiry from ', ?), ?, 'info', 0, NOW())
        ");
        $stmt->execute([$name, "From: {$email}\nMessage: {$msg}"]);
        $sentSuccess = 'Thank you! Your message has been received. Our team will contact you shortly.';
    }
}
?>

<div class="py-16 bg-slate-50 min-h-[80vh]">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        
        <div class="text-center space-y-2 max-w-xl mx-auto">
            <h2 class="text-xs font-bold text-purple-600 uppercase tracking-widest">24/7 Support</h2>
            <h1 class="text-3xl sm:text-4xl font-black text-zinc-900 tracking-tight">Get In Touch</h1>
            <p class="text-xs sm:text-sm text-zinc-500">Need enterprise API onboarding, custom rate tiers, or order assistance? We are here to assist.</p>
        </div>

        <?php if ($sentSuccess): ?>
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-3">
                <?= heroicon('check-circle', 'w-5 h-5 text-emerald-600 flex-shrink-0') ?>
                <span><?= e($sentSuccess) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($sentError): ?>
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-3">
                <?= heroicon('x-circle', 'w-5 h-5 text-rose-600 flex-shrink-0') ?>
                <span><?= e($sentError) ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Info side -->
            <div class="space-y-4">
                <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <?= heroicon('envelope', 'w-5 h-5') ?>
                    </div>
                    <h3 class="text-sm font-bold text-zinc-900">Email Support</h3>
                    <p class="text-xs text-zinc-500 leading-relaxed">Direct support via our technical desk.</p>
                    <span class="text-xs font-bold text-purple-700 block"><?= e(getSetting('contact_email', 'support@apexsmm.com')) ?></span>
                </div>

                <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <?= heroicon('clock', 'w-5 h-5') ?>
                    </div>
                    <h3 class="text-sm font-bold text-zinc-900">Response Speed</h3>
                    <p class="text-xs text-zinc-500 leading-relaxed">Average ticket response under 15 minutes during business hours.</p>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="md:col-span-2 bg-white p-8 rounded-3xl border border-zinc-200/80 shadow-sm">
                <form action="/contact.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Your Name</label>
                        <input type="text" name="name" required placeholder="John Doe" 
                               class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Email Address</label>
                        <input type="email" name="email" required placeholder="john@example.com" 
                               class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Message</label>
                        <textarea name="message" rows="4" required placeholder="How can our technical team assist you?" 
                                  class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 transition-all"></textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-6 py-3 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-md shadow-purple-600/25 transition-all">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
