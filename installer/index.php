<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Check if already installed
if (file_exists(BASE_PATH . '/.env')) {
    $installed = true;
} else {
    $installed = false;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMM Panel Installation Wizard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        purple: {
                            50: '#FAF5FF',
                            100: '#F3E8FF',
                            600: '#7C3AED',
                            700: '#6D28D9',
                            800: '#5B21B6',
                            900: '#4C1D95'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full flex items-center justify-center p-4 antialiased text-zinc-900 font-sans">
    <div class="max-w-xl w-full bg-white border border-zinc-200/90 rounded-3xl p-8 sm:p-10 shadow-xl shadow-purple-500/5 space-y-6">
        <div class="text-center space-y-2">
            <div class="w-14 h-14 rounded-2xl bg-purple-600 text-white flex items-center justify-center mx-auto shadow-lg shadow-purple-600/30">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-zinc-900">SMM Panel Automated Installer</h1>
            <p class="text-xs text-zinc-500">Fast, secure setup for your automated SMM growth platform.</p>
        </div>

        <?php if ($installed): ?>
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 space-y-2">
                <div class="font-bold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Installation Complete!
                </div>
                <p>Your platform is already installed and running. You can proceed directly to administration or customer dashboard.</p>
            </div>
            <div class="flex items-center gap-3 pt-2">
                <a href="/admin/login.php" class="flex-1 py-3 text-center rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-md transition-all">
                    Admin Console &rarr;
                </a>
                <a href="/index.php" class="flex-1 py-3 text-center rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-800 font-bold text-xs transition-all">
                    Visit Main Site &rarr;
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4 text-xs text-zinc-600 leading-relaxed border-t border-b border-zinc-100 py-4">
                <p>This automated installation wizard will verify system requirements, configure MySQL credentials, build database tables, and provision your superadmin account.</p>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2 text-center font-bold">
                    <div class="bg-purple-50 text-purple-700 p-2.5 rounded-xl border border-purple-100">1. Requirements</div>
                    <div class="bg-slate-50 text-zinc-500 p-2.5 rounded-xl border border-zinc-100">2. Database</div>
                    <div class="bg-slate-50 text-zinc-500 p-2.5 rounded-xl border border-zinc-100">3. Admin Setup</div>
                </div>
            </div>

            <div class="pt-2">
                <a href="/installer/requirements.php" class="w-full block py-3.5 text-center rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-lg shadow-purple-600/25 transition-all">
                    Check System Requirements &rarr;
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
