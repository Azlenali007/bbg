<?php
declare(strict_types=1);

function renderErrorPage(int $statusCode, string $title, string $message): void
{
    http_response_code($statusCode);

    // If request is AJAX or API, return JSON
    $isJson = (
        (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
        (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
        (isset($_SERVER['REQUEST_URI']) && (str_starts_with($_SERVER['REQUEST_URI'], '/api/') || str_starts_with($_SERVER['REQUEST_URI'], '/ajax/')))
    );

    if ($isJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'code' => $statusCode,
            'message' => $message,
            'title' => $title
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $appName = defined('APP_NAME') ? APP_NAME : 'ApexSMM';
    $appUrl = defined('APP_URL') && APP_URL ? APP_URL : '';
    ?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($statusCode . ' - ' . $title, ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
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
                            900: '#5B21B6'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full flex items-center justify-center p-6 text-zinc-900 font-sans antialiased">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl shadow-purple-500/5 border border-zinc-200 p-8 text-center">
        <div class="w-16 h-16 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-sm border border-purple-100">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <span class="inline-block px-3 py-1 bg-purple-100 text-purple-700 text-xs font-semibold rounded-full uppercase tracking-wider mb-3">
            Error <?= $statusCode ?>
        </span>
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 mb-2"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-zinc-500 text-sm leading-relaxed mb-6">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= $appUrl ?>/index.php" class="inline-flex justify-center items-center px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold shadow-md shadow-purple-600/20 transition-all">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Return to Home
            </a>
            <button onclick="window.history.back()" class="inline-flex justify-center items-center px-4 py-2.5 rounded-xl bg-white border border-zinc-200 hover:bg-zinc-50 text-zinc-700 text-sm font-semibold transition-all">
                Go Back
            </button>
        </div>
        <div class="mt-8 pt-6 border-t border-zinc-100 text-xs text-zinc-400">
            <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> &bull; Ref: <?= date('Ymd-His') ?>
        </div>
    </div>
</body>
</html>
    <?php
    exit;
}
