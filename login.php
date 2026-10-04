<?php
declare(strict_types=1);

$pageTitle = 'Sign In';

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

if (Auth::check()) {
    header('Location: /user/dashboard.php');
    exit;
}

$loginError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (!checkRateLimit('login_attempts', 10, 60)) {
        renderErrorPage(429, 'Too Many Attempts', 'Too many failed login attempts. Please wait 60 seconds.');
    }

    $identifier = trim($_POST['identifier'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (empty($identifier) || empty($password)) {
        $loginError = 'Please enter your username/email and password.';
    } else {
        $res = Auth::login($identifier, $password);
        if ($res['success']) {
            header('Location: /user/dashboard.php');
            exit;
        } else {
            $loginError = $res['message'];
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
            <h2 class="text-2xl font-black text-zinc-900 tracking-tight">Sign in to your account</h2>
            <p class="text-xs text-zinc-500 mt-1">Enter your credentials to access your dashboard and services.</p>
        </div>

        <?php if ($loginError): ?>
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-3">
                <?= heroicon('x-circle', 'w-5 h-5 text-rose-600 flex-shrink-0') ?>
                <span><?= e($loginError) ?></span>
            </div>
        <?php endif; ?>

        <form action="/login.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Username or Email</label>
                <div class="relative">
                    <input type="text" name="identifier" required placeholder="username or email"
                           class="w-full pl-10 pr-4 py-3 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 focus:ring-4 focus:ring-purple-600/10 transition-all">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                        <?= heroicon('user', 'w-4 h-4') ?>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Password</label>
                <div class="relative">
                    <input type="password" name="password" required placeholder="••••••••"
                           class="w-full pl-10 pr-4 py-3 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 focus:ring-4 focus:ring-purple-600/10 transition-all">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                        <?= heroicon('key', 'w-4 h-4') ?>
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" 
                        class="w-full py-3.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-lg shadow-purple-600/25 transition-all flex items-center justify-center gap-2">
                    <span>Sign In</span>
                    <?= heroicon('arrow-right-on-rectangle', 'w-4 h-4') ?>
                </button>
            </div>
        </form>

        <div class="text-center pt-2 border-t border-zinc-100">
            <p class="text-xs text-zinc-500">
                Don't have an account yet? 
                <a href="/register.php" class="font-bold text-purple-600 hover:text-purple-800">Sign Up</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
