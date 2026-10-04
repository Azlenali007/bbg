<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';

sendSecurityHeaders();

$pageTitle = $pageTitle ?? 'Premier Social Media Growth Engine';
$appName = getSetting('site_name', APP_NAME);
$isAuth = Auth::check();
$currentUser = $isAuth ? Auth::user() : null;
?>
<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($pageTitle) ?> | <?= e($appName) ?></title>
    <meta name="description" content="<?= e(getSetting('site_description', 'High performance SMM services.')) ?>">
    
    <!-- Tailwind CSS 4 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        purple: {
                            50: '#FAF5FF',
                            100: '#F3E8FF',
                            200: '#E9D5FF',
                            300: '#D8B4FE',
                            400: '#C084FC',
                            500: '#A855F7',
                            600: '#7C3AED', // Primary Purple
                            700: '#6D28D9', // Purple Hover
                            800: '#5B21B6', // Deep Purple
                            900: '#4C1D95'
                        },
                        zinc: {
                            850: '#1e1e24'
                        }
                    },
                    boxShadow: {
                        'purple-sm': '0 2px 8px -1px rgba(124, 58, 237, 0.08)',
                        'purple-md': '0 8px 24px -4px rgba(124, 58, 237, 0.12)',
                        'purple-lg': '0 16px 32px -6px rgba(124, 58, 237, 0.16)'
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js & GSAP -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>

    <!-- App Assets -->
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/animations.js"></script>
    <script src="/assets/js/notifications.js"></script>
    <script src="/assets/js/components.js"></script>
    <script src="/assets/js/app.js"></script>
</head>
<body class="flex flex-col min-h-full bg-white text-zinc-900 font-sans antialiased selection:bg-purple-100 selection:text-purple-700">

<!-- Public Navbar -->
<header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-zinc-200/80 transition-all" x-data="{ mobileMenu: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Brand Logo -->
            <a href="/index.php" class="flex items-center gap-2.5 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-purple-800 to-purple-600 flex items-center justify-center text-white shadow-md shadow-purple-600/25 group-hover:scale-105 transition-transform duration-200">
                    <?= heroicon('bolt', 'w-5 h-5 text-white') ?>
                </div>
                <div class="flex flex-col">
                    <span class="font-extrabold text-xl tracking-tight text-zinc-900 leading-none group-hover:text-purple-700 transition-colors">
                        <?= e($appName) ?>
                    </span>
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-purple-600 leading-tight">Growth Cloud</span>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden md:flex items-center gap-7">
                <a href="/index.php" class="text-sm font-medium text-zinc-600 hover:text-purple-600 transition-colors">Home</a>
                <a href="/services.php" class="text-sm font-medium text-zinc-600 hover:text-purple-600 transition-colors">Services</a>
                <a href="/how-it-works.php" class="text-sm font-medium text-zinc-600 hover:text-purple-600 transition-colors">How It Works</a>
                <a href="/faq.php" class="text-sm font-medium text-zinc-600 hover:text-purple-600 transition-colors">FAQ</a>
                <a href="/contact.php" class="text-sm font-medium text-zinc-600 hover:text-purple-600 transition-colors">Support</a>
            </nav>

            <!-- Actions / Auth -->
            <div class="hidden md:flex items-center gap-3">
                <?php if ($isAuth && $currentUser): ?>
                    <a href="/user/dashboard.php" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-purple-50 text-purple-700 hover:bg-purple-100 text-sm font-semibold transition-colors">
                        <?= heroicon('wallet', 'w-4 h-4 text-purple-600') ?>
                        <span><?= formatCurrency($currentUser['wallet_balance'] ?? 0) ?></span>
                    </a>
                    <a href="/user/dashboard.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold shadow-md shadow-purple-600/20 transition-all">
                        <?= heroicon('home', 'w-4 h-4') ?>
                        <span>Dashboard</span>
                    </a>
                <?php else: ?>
                    <a href="/login.php" class="text-sm font-semibold text-zinc-700 hover:text-purple-600 px-3 py-2 transition-colors">
                        Sign In
                    </a>
                    <a href="/register.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold shadow-md shadow-purple-600/20 transition-all">
                        <span>Get Started</span>
                        <?= heroicon('arrow-right-on-rectangle', 'w-4 h-4') ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile menu button -->
            <div class="flex md:hidden items-center">
                <button type="button" @click="mobileMenu = !mobileMenu" class="p-2 rounded-xl text-zinc-600 hover:bg-zinc-100">
                    <span class="sr-only">Open menu</span>
                    <span x-show="!mobileMenu"><?= heroicon('bars-3', 'w-6 h-6') ?></span>
                    <span x-show="mobileMenu" x-cloak><?= heroicon('x-mark', 'w-6 h-6') ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Nav Drawer -->
    <div x-show="mobileMenu" x-cloak class="md:hidden border-b border-zinc-200 bg-white px-4 pt-2 pb-4 space-y-1">
        <a href="/index.php" class="block px-3 py-2 rounded-lg text-base font-medium text-zinc-700 hover:bg-purple-50 hover:text-purple-700">Home</a>
        <a href="/services.php" class="block px-3 py-2 rounded-lg text-base font-medium text-zinc-700 hover:bg-purple-50 hover:text-purple-700">Services</a>
        <a href="/how-it-works.php" class="block px-3 py-2 rounded-lg text-base font-medium text-zinc-700 hover:bg-purple-50 hover:text-purple-700">How It Works</a>
        <a href="/faq.php" class="block px-3 py-2 rounded-lg text-base font-medium text-zinc-700 hover:bg-purple-50 hover:text-purple-700">FAQ</a>
        <a href="/contact.php" class="block px-3 py-2 rounded-lg text-base font-medium text-zinc-700 hover:bg-purple-50 hover:text-purple-700">Support</a>
        <div class="pt-3 border-t border-zinc-100 flex flex-col gap-2">
            <?php if ($isAuth): ?>
                <a href="/user/dashboard.php" class="w-full text-center px-4 py-2.5 rounded-xl bg-purple-600 text-white font-semibold">Dashboard</a>
            <?php else: ?>
                <a href="/login.php" class="w-full text-center px-4 py-2 rounded-xl border border-zinc-200 text-zinc-700 font-semibold">Sign In</a>
                <a href="/register.php" class="w-full text-center px-4 py-2.5 rounded-xl bg-purple-600 text-white font-semibold">Get Started</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="flex-grow">
