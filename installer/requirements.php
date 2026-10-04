<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

$checks = [
    'PHP Version >= 8.1' => [
        'passed' => version_compare(PHP_VERSION, '8.1.0', '>='),
        'current' => PHP_VERSION,
        'required' => '>= 8.1.0'
    ],
    'PDO MySQL Extension' => [
        'passed' => extension_loaded('pdo_mysql'),
        'current' => extension_loaded('pdo_mysql') ? 'Loaded' : 'Missing',
        'required' => 'Required'
    ],
    'cURL Extension' => [
        'passed' => extension_loaded('curl'),
        'current' => extension_loaded('curl') ? 'Loaded' : 'Missing',
        'required' => 'Required'
    ],
    'OpenSSL Extension' => [
        'passed' => extension_loaded('openssl'),
        'current' => extension_loaded('openssl') ? 'Loaded' : 'Missing',
        'required' => 'Required'
    ],
    'Mbstring Extension' => [
        'passed' => extension_loaded('mbstring'),
        'current' => extension_loaded('mbstring') ? 'Loaded' : 'Missing',
        'required' => 'Required'
    ],
    'JSON Extension' => [
        'passed' => extension_loaded('json'),
        'current' => extension_loaded('json') ? 'Loaded' : 'Missing',
        'required' => 'Required'
    ],
    'Storage Logs Directory Writable' => [
        'passed' => is_writable(BASE_PATH . '/storage/logs') || @mkdir(BASE_PATH . '/storage/logs', 0777, true),
        'current' => is_writable(BASE_PATH . '/storage/logs') ? 'Writable' : 'Not Writable',
        'required' => 'Writable'
    ],
    'Storage Cache Directory Writable' => [
        'passed' => is_writable(BASE_PATH . '/storage/cache') || @mkdir(BASE_PATH . '/storage/cache', 0777, true),
        'current' => is_writable(BASE_PATH . '/storage/cache') ? 'Writable' : 'Not Writable',
        'required' => 'Writable'
    ]
];

$allPassed = true;
foreach ($checks as $c) {
    if (!$c['passed']) {
        $allPassed = false;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Requirements - Installer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex items-center justify-center p-4 antialiased text-zinc-900 font-sans">
    <div class="max-w-xl w-full bg-white border border-zinc-200/90 rounded-3xl p-8 sm:p-10 shadow-xl space-y-6">
        <div>
            <h1 class="text-xl font-black tracking-tight text-zinc-900">Step 1: System Requirements</h1>
            <p class="text-xs text-zinc-500 mt-1">Verifying server environment compatibility.</p>
        </div>

        <div class="divide-y divide-zinc-100 border border-zinc-100 rounded-2xl overflow-hidden text-xs">
            <?php foreach ($checks as $name => $c): ?>
                <div class="p-3.5 flex items-center justify-between <?= $c['passed'] ? 'bg-white' : 'bg-rose-50/50' ?>">
                    <div>
                        <span class="font-bold text-zinc-800"><?= e($name) ?></span>
                        <span class="block text-[11px] text-zinc-400 mt-0.5">Required: <?= e($c['required']) ?> | Found: <?= e($c['current']) ?></span>
                    </div>
                    <div>
                        <?php if ($c['passed']): ?>
                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase tracking-wider">Pass</span>
                        <?php else: ?>
                            <span class="px-2.5 py-1 rounded-full bg-rose-100 text-rose-800 text-[10px] font-black uppercase tracking-wider">Fail</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pt-2 flex items-center justify-between">
            <a href="/installer/index.php" class="px-4 py-2.5 rounded-xl border border-zinc-200 text-zinc-600 font-bold text-xs hover:bg-zinc-50">
                &larr; Back
            </a>
            <?php if ($allPassed): ?>
                <a href="/installer/database.php" class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-md transition-all">
                    Next: Configure Database &rarr;
                </a>
            <?php else: ?>
                <button disabled class="px-6 py-2.5 rounded-xl bg-zinc-300 text-zinc-500 font-bold text-xs cursor-not-allowed">
                    Fix Issues to Continue
                </button>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
