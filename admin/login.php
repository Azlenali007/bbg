<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/session.php';
require_once BASE_PATH . '/includes/admin-auth.php';
require_once BASE_PATH . '/includes/csrf.php';
require_once BASE_PATH . '/includes/security.php';
require_once BASE_PATH . '/includes/functions.php';

sendSecurityHeaders();

if (isset($_GET['logout'])) {
    AdminAuth::logout();
    flash('success', 'Admin session terminated.');
    header('Location: /admin/login.php');
    exit;
}

if (AdminAuth::check()) {
    header('Location: /admin/index.php');
    exit;
}

$loginError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (!checkRateLimit('admin_login_attempts', 5, 60)) {
        renderErrorPage(429, 'Too Many Attempts', 'Too many failed admin login attempts. Please wait 60 seconds.');
    }

    $identifier = trim($_POST['identifier'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (empty($identifier) || empty($password)) {
        $loginError = 'Please enter admin username and password.';
    } else {
        $res = AdminAuth::login($identifier, $password);
        if ($res['success']) {
            header('Location: /admin/index.php');
            exit;
        } else {
            $loginError = $res['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Authentication | <?= e(getSetting('site_name', APP_NAME)) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        purple: {
                            600: '#7C3AED',
                            700: '#6D28D9'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full flex items-center justify-center p-4 antialiased text-zinc-100 font-sans">
    <div class="max-w-md w-full bg-zinc-900 border border-zinc-800 rounded-3xl p-8 sm:p-10 shadow-2xl space-y-6">
        <div class="text-center space-y-2">
            <div class="w-12 h-12 rounded-2xl bg-purple-600 text-white flex items-center justify-center mx-auto shadow-lg shadow-purple-600/30">
                <?= heroicon('shield-check', 'w-6 h-6 text-white') ?>
            </div>
            <h1 class="text-xl font-bold tracking-tight text-white">Administration Console</h1>
            <p class="text-xs text-zinc-400">Restricted administrative access only.</p>
        </div>

        <?php if ($loginError): ?>
            <div class="p-3.5 rounded-2xl bg-rose-950/60 border border-rose-800 text-rose-300 text-xs font-semibold">
                <?= e($loginError) ?>
            </div>
        <?php endif; ?>

        <?php if ($msg = flash('success')): ?>
            <div class="p-3.5 rounded-2xl bg-emerald-950/60 border border-emerald-800 text-emerald-300 text-xs font-semibold">
                <?= e($msg) ?>
            </div>
        <?php endif; ?>

        <form action="/admin/login.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-1.5">Admin Username</label>
                <input type="text" name="identifier" required placeholder="admin" 
                       class="w-full px-4 py-3 rounded-2xl border border-zinc-800 bg-zinc-800/80 text-xs text-white focus:outline-none focus:border-purple-500 transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-1.5">Password</label>
                <input type="password" name="password" required placeholder="••••••••" 
                       class="w-full px-4 py-3 rounded-2xl border border-zinc-800 bg-zinc-800/80 text-xs text-white focus:outline-none focus:border-purple-500 transition-all">
            </div>

            <div class="pt-2">
                <button type="submit" 
                        class="w-full py-3.5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-lg shadow-purple-600/25 transition-all">
                    Authorize Access
                </button>
            </div>
        </form>

        <div class="text-center pt-2 border-t border-zinc-800">
            <a href="/index.php" class="text-xs text-zinc-500 hover:text-zinc-300 transition-colors">
                &larr; Return to main site
            </a>
        </div>
    </div>
</body>
</html>
