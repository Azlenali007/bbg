<?php
declare(strict_types=1);

$pageTitle = 'Create Account';

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/security.php';

if (Auth::check()) {
    header('Location: /user/dashboard.php');
    exit;
}

$registerError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (!checkRateLimit('register_attempts', 5, 60)) {
        renderErrorPage(429, 'Too Many Attempts', 'Too many registration requests. Please wait a minute.');
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    $validator = new Validator();
    $valid = $validator->validate([
        'username' => $username,
        'email' => $email,
        'password' => $password
    ], [
        'username' => 'required|username|min:3|max:30',
        'email' => 'required|email|max:120',
        'password' => 'required|min:8|max:100'
    ]);

    if (!$valid) {
        $registerError = $validator->getFirstError();
    } elseif ($password !== $confirmPassword) {
        $registerError = 'Passwords do not match.';
    } else {
        $res = Auth::register($username, $email, $password);
        if ($res['success']) {
            flash('success', 'Welcome to ApexSMM! Your account is active.');
            header('Location: /user/dashboard.php');
            exit;
        } else {
            $registerError = $res['message'];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 sm:p-10 rounded-3xl border border-zinc-200/80 shadow-xl shadow-purple-600/5">
        <div class="text-center">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-800 to-purple-600 flex items-center justify-center text-white mx-auto shadow-md shadow-purple-600/25 mb-4">
                <?= heroicon('bolt', 'w-6 h-6 text-white') ?>
            </div>
            <h2 class="text-2xl font-black text-zinc-900 tracking-tight">Create your account</h2>
            <p class="text-xs text-zinc-500 mt-1">Get instant access to automated social media growth services.</p>
        </div>

        <?php if ($registerError): ?>
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-3">
                <?= heroicon('x-circle', 'w-5 h-5 text-rose-600 flex-shrink-0') ?>
                <span><?= e($registerError) ?></span>
            </div>
        <?php endif; ?>

        <form action="/register.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Username</label>
                <div class="relative">
                    <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required placeholder="johndoe"
                           class="w-full pl-10 pr-4 py-3 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 focus:ring-4 focus:ring-purple-600/10 transition-all">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                        <?= heroicon('user', 'w-4 h-4') ?>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Email Address</label>
                <div class="relative">
                    <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required placeholder="name@example.com"
                           class="w-full pl-10 pr-4 py-3 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 focus:ring-4 focus:ring-purple-600/10 transition-all">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                        <?= heroicon('envelope', 'w-4 h-4') ?>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Password (Min 8 chars)</label>
                <div class="relative">
                    <input type="password" name="password" required minlength="8" placeholder="••••••••"
                           class="w-full pl-10 pr-4 py-3 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 focus:ring-4 focus:ring-purple-600/10 transition-all">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                        <?= heroicon('key', 'w-4 h-4') ?>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Confirm Password</label>
                <div class="relative">
                    <input type="password" name="confirm_password" required minlength="8" placeholder="••••••••"
                           class="w-full pl-10 pr-4 py-3 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 focus:ring-4 focus:ring-purple-600/10 transition-all">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                        <?= heroicon('key', 'w-4 h-4') ?>
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" 
                        class="w-full py-3.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-lg shadow-purple-600/25 transition-all flex items-center justify-center gap-2">
                    <span>Create Account</span>
                    <?= heroicon('arrow-right-on-rectangle', 'w-4 h-4') ?>
                </button>
            </div>
        </form>

        <div class="text-center pt-2 border-t border-zinc-100">
            <p class="text-xs text-zinc-500">
                Already registered? 
                <a href="/login.php" class="font-bold text-purple-600 hover:text-purple-800">Sign In</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
