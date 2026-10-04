<!-- Admin Mobile Backdrop -->
<div x-show="sidebarOpen" x-cloak 
     @click="sidebarOpen = false" 
     class="fixed inset-0 z-40 bg-zinc-900/50 backdrop-blur-sm lg:hidden transition-opacity">
</div>

<!-- Admin Sidebar -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
       class="fixed inset-y-0 left-0 z-50 w-64 bg-zinc-900 text-zinc-300 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 border-r border-zinc-800">
    
    <!-- Brand Header -->
    <div class="h-16 flex items-center justify-between px-6 border-b border-zinc-800">
        <a href="/admin/index.php" class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-purple-600 flex items-center justify-center text-white shadow-md shadow-purple-600/30">
                <?= heroicon('shield-check', 'w-5 h-5 text-white') ?>
            </div>
            <div class="flex flex-col">
                <span class="font-extrabold text-sm text-white tracking-tight leading-none"><?= e($appName) ?></span>
                <span class="text-[10px] font-semibold text-purple-400 uppercase tracking-widest mt-0.5">Admin Desk</span>
            </div>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden p-1.5 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800">
            <?= heroicon('x-mark', 'w-5 h-5') ?>
        </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto text-xs font-semibold">
        <?php
        $adminLinks = [
            ['url' => '/admin/index.php', 'name' => 'Dashboard', 'icon' => 'home', 'file' => 'index.php'],
            ['url' => '/admin/orders.php', 'name' => 'Orders', 'icon' => 'shopping-cart', 'file' => 'orders.php'],
            ['url' => '/admin/services.php', 'name' => 'Services', 'icon' => 'cube', 'file' => 'services.php'],
            ['url' => '/admin/service-import.php', 'name' => 'Import Services', 'icon' => 'arrow-down-tray', 'file' => 'service-import.php'],
            ['url' => '/admin/categories.php', 'name' => 'Categories', 'icon' => 'list-bullet', 'file' => 'categories.php'],
            ['url' => '/admin/providers.php', 'name' => 'API Providers', 'icon' => 'server', 'file' => 'providers.php'],
            ['url' => '/admin/users.php', 'name' => 'Users', 'icon' => 'users', 'file' => 'users.php'],
            ['url' => '/admin/payments.php', 'name' => 'Payments', 'icon' => 'wallet', 'file' => 'payments.php'],
            ['url' => '/admin/payment-settings.php', 'name' => 'Payment Gateways', 'icon' => 'credit-card', 'file' => 'payment-settings.php'],
            ['url' => '/admin/tickets.php', 'name' => 'Support Tickets', 'icon' => 'ticket', 'file' => 'tickets.php'],
            ['url' => '/admin/banners.php', 'name' => 'Hero Banners', 'icon' => 'photo', 'file' => 'banners.php'],
            ['url' => '/admin/flash-sales.php', 'name' => 'Flash Sales', 'icon' => 'bolt', 'file' => 'flash-sales.php'],
            ['url' => '/admin/announcements.php', 'name' => 'Announcements', 'icon' => 'megaphone', 'file' => 'announcements.php'],
            ['url' => '/admin/reports.php', 'name' => 'Reports & Analytics', 'icon' => 'chart-bar', 'file' => 'reports.php'],
            ['url' => '/admin/settings.php', 'name' => 'System Settings', 'icon' => 'cog', 'file' => 'settings.php'],
            ['url' => '/admin/logs.php', 'name' => 'Audit & System Logs', 'icon' => 'document-text', 'file' => 'logs.php'],
            ['url' => '/admin/backups.php', 'name' => 'Database Backups', 'icon' => 'server-stack', 'file' => 'backups.php'],
        ];

        foreach ($adminLinks as $nav):
            $isActive = ($currentRoute === $nav['file']);
        ?>
            <a href="<?= $nav['url'] ?>" 
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 <?= $isActive ? 'bg-purple-600 text-white shadow-md shadow-purple-600/25' : 'text-zinc-400 hover:bg-zinc-800/80 hover:text-white' ?>">
                <?= heroicon($nav['icon'], 'w-4 h-4 ' . ($isActive ? 'text-white' : 'text-zinc-400')) ?>
                <span class="truncate"><?= $nav['name'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Admin User Info & Logout -->
    <div class="p-3 border-t border-zinc-800">
        <div class="px-3.5 py-2 flex items-center justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold text-white truncate"><?= e($admin['username']) ?></p>
                <p class="text-[10px] text-purple-400 uppercase font-bold"><?= e($admin['role']) ?></p>
            </div>
            <a href="/admin/login.php?logout=1" class="text-zinc-400 hover:text-rose-400 p-1" title="Sign Out">
                <?= heroicon('arrow-left-on-rectangle', 'w-4 h-4') ?>
            </a>
        </div>
    </div>
</aside>
