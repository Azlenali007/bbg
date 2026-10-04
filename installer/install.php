<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

session_start();
$installDb = $_SESSION['install_db'] ?? null;

if (!$installDb) {
    header('Location: /installer/database.php');
    exit;
}

$error = null;
$completed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminEmail = trim($_POST['admin_email'] ?? 'admin@smm.local');
    $adminPass = (string)($_POST['admin_pass'] ?? '');

    if (empty($adminUser) || empty($adminEmail) || mb_strlen($adminPass) < 8) {
        $error = 'Please provide valid admin credentials (password at least 8 characters).';
    } else {
        try {
            $dsn = "mysql:host={$installDb['host']};port={$installDb['port']};dbname={$installDb['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $installDb['user'], $installDb['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);

            // Execute schema.sql
            $schemaFile = BASE_PATH . '/database/schema.sql';
            if (!file_exists($schemaFile)) {
                throw new Exception('Schema file database/schema.sql not found.');
            }

            $sql = file_get_contents($schemaFile);
            $pdo->exec($sql);

            // Upsert Admin account
            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $chk = $pdo->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
            $chk->execute([$adminUser, $adminEmail]);
            $existing = $chk->fetch();

            if ($existing) {
                $up = $pdo->prepare("UPDATE admins SET password = ?, role = 'superadmin', status = 'active' WHERE id = ?");
                $up->execute([$hash, $existing['id']]);
            } else {
                $ins = $pdo->prepare("INSERT INTO admins (username, email, password, role, status, created_at) VALUES (?, ?, ?, 'superadmin', 'active', NOW())");
                $ins->execute([$adminUser, $adminEmail, $hash]);
            }

            // Write .env
            $envContent = <<<ENV
APP_NAME="ApexSMM"
APP_ENV="production"
APP_DEBUG="false"
APP_URL=""

DB_HOST="{$installDb['host']}"
DB_PORT="{$installDb['port']}"
DB_NAME="{$installDb['name']}"
DB_USER="{$installDb['user']}"
DB_PASS="{$installDb['pass']}"

CURRENCY="USD"
CURRENCY_SYMBOL="$"
TIMEZONE="UTC"
SESSION_LIFETIME="86400"
CRON_KEY="smm_cron_secret"
ENV;
            file_put_contents(BASE_PATH . '/.env', $envContent);

            $completed = true;
            unset($_SESSION['install_db']);
        } catch (Throwable $e) {
            $error = 'Installation Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finish Setup - Installer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex items-center justify-center p-4 antialiased text-zinc-900 font-sans">
    <div class="max-w-xl w-full bg-white border border-zinc-200/90 rounded-3xl p-8 sm:p-10 shadow-xl space-y-6">
        <?php if ($completed): ?>
            <div class="text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="text-2xl font-black text-zinc-900">Installation Finished!</h1>
                <p class="text-xs text-zinc-500">Your database has been migrated and administrator credentials initialized.</p>
                
                <div class="pt-4 flex items-center gap-3">
                    <a href="/admin/login.php" class="flex-1 py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-md transition-all">
                        Go to Admin Console &rarr;
                    </a>
                    <a href="/login.php" class="flex-1 py-3 rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-800 font-bold text-xs transition-all">
                        User Login &rarr;
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div>
                <h1 class="text-xl font-black tracking-tight text-zinc-900">Step 3: Superadmin Account</h1>
                <p class="text-xs text-zinc-500 mt-1">Configure primary administrator credentials.</p>
            </div>

            <?php if ($error): ?>
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Admin Username</label>
                    <input type="text" name="admin_user" value="admin" required class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Admin Email</label>
                    <input type="email" name="admin_email" value="admin@smm.local" required class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Admin Password</label>
                    <input type="password" name="admin_pass" required minlength="8" placeholder="••••••••" class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:outline-none focus:border-purple-600 font-semibold">
                </div>

                <div class="pt-4 flex items-center justify-between">
                    <a href="/installer/database.php" class="px-4 py-2.5 rounded-xl border border-zinc-200 text-zinc-600 font-bold text-xs hover:bg-zinc-50">
                        &larr; Back
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-md transition-all">
                        Execute Complete Install &rarr;
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
