<?php
declare(strict_types=1);

$pageTitle = 'Database Backups';
require_once __DIR__ . '/includes/header.php';

$backupDir = BASE_PATH . '/storage/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_backup') {
        try {
            $filename = 'backup_' . DB_NAME . '_' . date('Y-m-d_H-i-s') . '.sql';
            $filePath = $backupDir . '/' . $filename;

            // Generate clean SQL dump using mysqldump if available or PHP PDO dump
            $cmd = sprintf(
                'mysqldump --user=%s --password=%s --host=%s --port=%d %s > %s 2>&1',
                escapeshellarg(DB_USER),
                escapeshellarg(DB_PASS),
                escapeshellarg(DB_HOST),
                DB_PORT,
                escapeshellarg(DB_NAME),
                escapeshellarg($filePath)
            );

            exec($cmd, $out, $ret);

            if ($ret !== 0 || !file_exists($filePath) || filesize($filePath) === 0) {
                // Fallback to PHP table export
                $db = getDB();
                $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                $sqlContent = "-- SMM Panel Backup Generated " . date('Y-m-d H:i:s') . "\nSET FOREIGN_KEY_CHECKS=0;\n\n";

                foreach ($tables as $tbl) {
                    $createRow = $db->query("SHOW CREATE TABLE `{$tbl}`")->fetch();
                    $sqlContent .= $createRow['Create Table'] . ";\n\n";

                    $rows = $db->query("SELECT * FROM `{$tbl}`")->fetchAll();
                    if (!empty($rows)) {
                        foreach ($rows as $row) {
                            $escaped = array_map(function ($val) use ($db) {
                                return $val === null ? 'NULL' : $db->quote((string)$val);
                            }, $row);
                            $sqlContent .= "INSERT INTO `{$tbl}` VALUES (" . implode(',', $escaped) . ");\n";
                        }
                        $sqlContent .= "\n";
                    }
                }
                $sqlContent .= "SET FOREIGN_KEY_CHECKS=1;\n";
                file_put_contents($filePath, $sqlContent);
            }

            logAudit($adminId, true, 'create_backup', "Created database backup: {$filename}");
            flash('success', "Database backup {$filename} successfully created.");
            header('Location: /admin/backups.php');
            exit;
        } catch (Throwable $e) {
            $error = 'Backup failed: ' . $e->getMessage();
        }
    } elseif ($action === 'delete_backup') {
        $file = basename($_POST['filename'] ?? '');
        $target = $backupDir . '/' . $file;
        if (file_exists($target) && is_file($target)) {
            unlink($target);
            flash('success', "Backup file {$file} removed.");
        }
        header('Location: /admin/backups.php');
        exit;
    }
}

// Download action
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $target = $backupDir . '/' . $file;
    if (file_exists($target) && is_file($target)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($target));
        readfile($target);
        exit;
    }
}

// List backups
$files = glob($backupDir . '/*.sql') ?: [];
rsort($files);
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-5xl w-full mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-zinc-900 tracking-tight">Database Backups</h1>
            <p class="text-xs text-zinc-500">Create full SQL snapshots of your database schemas, services, and transactions.</p>
        </div>
        <form action="" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_backup">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-md shadow-purple-600/20 transition-all flex items-center gap-2">
                <?= heroicon('arrow-down-tray', 'w-4 h-4') ?> Create Backup Now
            </button>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-zinc-200/80 rounded-3xl p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-400">Existing SQL Snapshots</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50 border-b border-zinc-200 text-zinc-500 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="p-3">File Name</th>
                        <th class="p-3">Size</th>
                        <th class="p-3">Created Date</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 font-mono">
                    <?php if (empty($files)): ?>
                        <tr>
                            <td colspan="4" class="p-8 text-center text-zinc-400 font-sans">No backups generated yet. Click "Create Backup Now" above.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($files as $f): 
                            $name = basename($f);
                            $size = round(filesize($f) / 1024, 2) . ' KB';
                            $mtime = date('Y-m-d H:i:s', filemtime($f));
                        ?>
                            <tr class="hover:bg-zinc-50/50">
                                <td class="p-3 font-bold text-zinc-900"><?= e($name) ?></td>
                                <td class="p-3 text-zinc-600"><?= $size ?></td>
                                <td class="p-3 text-zinc-400"><?= $mtime ?></td>
                                <td class="p-3 text-right space-x-2 font-sans">
                                    <a href="?download=<?= urlencode($name) ?>" class="px-3 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold rounded-lg text-xs">
                                        Download
                                    </a>
                                    <form action="" method="POST" class="inline-block" onsubmit="return confirm('Delete this backup snapshot?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_backup">
                                        <input type="hidden" name="filename" value="<?= e($name) ?>">
                                        <button type="submit" class="p-1 text-zinc-400 hover:text-rose-600">
                                            <?= heroicon('trash', 'w-4 h-4') ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
