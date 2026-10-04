<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

$error = null;
$dbHost = $_POST['db_host'] ?? '127.0.0.1';
$dbPort = (int)($_POST['db_port'] ?? 3306);
$dbName = $_POST['db_name'] ?? 'smm_panel';
$dbUser = $_POST['db_user'] ?? 'root';
$dbPass = $_POST['db_pass'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        // Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Save temporary session or query params for install.php
        session_start();
        $_SESSION['install_db'] = [
            'host' => $dbHost,
            'port' => $dbPort,
            'name' => $dbName,
            'user' => $dbUser,
            'pass' => $dbPass
        ];

        header('Location: /installer/install.php');
        exit;
    } catch (PDOException $e) {
        $error = 'Database Connection Failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Configuration - Installer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex items-center justify-center p-4 antialiased text-zinc-900 font-sans">
    <div class="max-w-xl w-full bg-white border border-zinc-200/90 rounded-3xl p-8 sm:p-10 shadow-xl space-y-6">
        <div>
            <h1 class="text-xl font-black tracking-tight text-zinc-900">Step 2: Database Connection</h1>
            <p class="text-xs text-zinc-500 mt-1">Configure MySQL credentials for database initialization.</p>
        </div>

        <?php if ($error): ?>
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4 text-xs">
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Host</label>
                    <input type="text" name="db_host" value="<?= htmlspecialchars($dbHost, ENT_QUOTES, 'UTF-8') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                </div>
                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Port</label>
                    <input type="number" name="db_port" value="<?= $dbPort ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                </div>
            </div>

            <div>
                <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Database Name</label>
                <input type="text" name="db_name" value="<?= htmlspecialchars($dbName, ENT_QUOTES, 'UTF-8') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
            </div>

            <div>
                <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Database Username</label>
                <input type="text" name="db_user" value="<?= htmlspecialchars($dbUser, ENT_QUOTES, 'UTF-8') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
            </div>

            <div>
                <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Database Password</label>
                <input type="password" name="db_pass" value="<?= htmlspecialchars($dbPass, ENT_QUOTES, 'UTF-8') ?>" placeholder="Empty if no password" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
            </div>

            <div class="pt-4 flex items-center justify-between">
                <a href="/installer/requirements.php" class="px-4 py-2.5 rounded-xl border border-zinc-200 text-zinc-600 font-bold text-xs hover:bg-zinc-50">
                    &larr; Back
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-md transition-all">
                    Test & Continue &rarr;
                </button>
            </div>
        </form>
    </div>
</body>
</html>
