<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/session.php';
require_once BASE_PATH . '/includes/admin-auth.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/security.php';

sendSecurityHeaders();

$admin = AdminAuth::requireAdmin();
$adminId = (int)$admin['id'];
$pageTitle = $pageTitle ?? 'Admin Console';
$appName = getSetting('site_name', APP_NAME);
$currentRoute = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($pageTitle) ?> | <?= e($appName) ?> Admin</title>
    
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
                            600: '#7C3AED',
                            700: '#6D28D9',
                            800: '#5B21B6',
                            900: '#4C1D95'
                        },
                        slate: {
                            50: '#F8FAFC'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js & GSAP -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>

    <?php if (isset($loadApexCharts) && $loadApexCharts): ?>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <?php endif; ?>

    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/animations.js"></script>
    <script src="/assets/js/notifications.js"></script>
    <script src="/assets/js/app.js"></script>
</head>
<body class="h-full antialiased font-sans text-zinc-900 bg-slate-50 flex overflow-hidden" x-data="{ sidebarOpen: false }">
