<?php
declare(strict_types=1);

$pageTitle = 'Profile & Settings';

require_once __DIR__ . '/includes/header.php';

$profileSuccess = null;
$profileError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if (!password_verify($currentPassword, $user['password'])) {
            $profileError = 'Current password does not match.';
        } elseif (mb_strlen($newPassword) < 8) {
            $profileError = 'New password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $profileError = 'New passwords do not match.';
        } else {
            $db = getDB();
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);
            $profileSuccess = 'Your password has been successfully updated.';
        }
    } elseif ($action === 'update_profile') {
        $timezone = trim($_POST['timezone'] ?? 'UTC');
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET timezone = ? WHERE id = ?");
        $stmt->execute([$timezone, $userId]);
        $profileSuccess = 'Profile preferences updated.';
    }
}

require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="bg-white p-6 rounded-3xl border border-zinc-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-zinc-900 tracking-tight">Account Settings</h2>
            <p class="text-xs text-zinc-500 mt-1">Manage security credentials, timezones, and authentication profile.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">
                Active Account
            </span>
        </div>
    </div>

    <?php if ($profileSuccess): ?>
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-3">
            <?= heroicon('check-circle', 'w-5 h-5 text-emerald-600 flex-shrink-0') ?>
            <span><?= e($profileSuccess) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($profileError): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-3">
            <?= heroicon('x-circle', 'w-5 h-5 text-rose-600 flex-shrink-0') ?>
            <span><?= e($profileError) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Account Info -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-zinc-200/80 shadow-sm space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-700 pb-3 border-b border-zinc-100">
                User Details
            </h3>

            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-zinc-400 block text-[11px]">Username</span>
                    <span class="font-bold text-zinc-900 text-sm mt-0.5 block"><?= e($user['username']) ?></span>
                </div>
                <div>
                    <span class="text-zinc-400 block text-[11px]">Registered Email</span>
                    <span class="font-bold text-zinc-900 text-sm mt-0.5 block"><?= e($user['email']) ?></span>
                </div>
                <div>
                    <span class="text-zinc-400 block text-[11px]">Member Since</span>
                    <span class="font-medium text-zinc-600 mt-0.5 block"><?= date('F j, Y', strtotime($user['created_at'])) ?></span>
                </div>
            </div>

            <!-- Timezone update -->
            <form action="/user/profile.php" method="POST" class="pt-4 border-t border-zinc-100 space-y-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">
                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700">Preferred Timezone</label>
                <select name="timezone" class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 transition-all">
                    <?php
                    $zones = ['UTC', 'America/New_York', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Dubai', 'Asia/Singapore', 'Asia/Tokyo'];
                    foreach ($zones as $tz):
                    ?>
                        <option value="<?= $tz ?>" <?= ($user['timezone'] ?? 'UTC') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-4 py-2 rounded-xl bg-purple-50 text-purple-700 hover:bg-purple-100 text-xs font-bold transition-colors">
                    Save Timezone
                </button>
            </form>
        </div>

        <!-- Security / Password Change -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-zinc-200/80 shadow-sm space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-700 pb-3 border-b border-zinc-100">
                Security & Password
            </h3>

            <form action="/user/profile.php" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1">Current Password</label>
                    <input type="password" name="current_password" required 
                           class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1">New Password</label>
                    <input type="password" name="new_password" required minlength="8" 
                           class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 mb-1">Confirm New Password</label>
                    <input type="password" name="confirm_password" required minlength="8" 
                           class="w-full px-4 py-2.5 rounded-2xl border border-zinc-200 bg-zinc-50 text-xs text-zinc-900 focus:bg-white focus:border-purple-600 transition-all">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/25 transition-all">
                        Update Password
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
