<!-- Mobile Sidebar Backdrop -->
<div x-show="sidebarOpen" x-cloak 
     @click="sidebarOpen = false" 
     class="fixed inset-0 z-40 bg-zinc-900/50 backdrop-blur-sm lg:hidden transition-opacity">
</div>

<!-- Sidebar Component -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
       class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-zinc-200/90 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
    
    <!-- Brand Header -->
    <div class="h-16 flex items-center justify-between px-6 border-b border-zinc-100">
        <a href="/user/dashboard.php" class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-purple-800 to-purple-600 flex items-center justify-center text-white shadow-md shadow-purple-600/25">
                <?= heroicon('bolt', 'w-5 h-5 text-white') ?>
            </div>
            <span class="font-extrabold text-lg tracking-tight text-zinc-900"><?= e($appName) ?></span>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden p-1.5 rounded-lg text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100">
            <?= heroicon('x-mark', 'w-5 h-5') ?>
        </button>
    </div>

    <!-- User Mini Profile & Balance -->
    <div class="p-4 mx-3 my-3 bg-purple-50/60 border border-purple-100/80 rounded-2xl">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-purple-700">Account Balance</span>
            <a href="/user/add-funds.php" class="text-[11px] font-semibold text-purple-600 hover:text-purple-800 flex items-center gap-0.5">
                <span>Top up</span>
                <?= heroicon('plus', 'w-3 h-3') ?>
            </a>
        </div>
        <div class="text-xl font-black text-purple-900 tracking-tight">
            <?= formatCurrency($user['wallet_balance'] ?? 0) ?>
        </div>
        <div class="mt-2 pt-2 border-t border-purple-100/60 flex items-center justify-between text-[11px] text-zinc-500">
            <span>Spent: <?= formatCurrency($user['total_spent'] ?? 0) ?></span>
            <span class="font-medium text-zinc-700"><?= e($user['username']) ?></span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-3 space-y-1 overflow-y-auto">
        <?php
        $navLinks = [
            ['url' => '/user/dashboard.php', 'name' => 'Dashboard', 'icon' => 'home', 'file' => 'dashboard.php'],
            ['url' => '/user/new-order.php', 'name' => 'New Order', 'icon' => 'plus-circle', 'file' => 'new-order.php', 'highlight' => true],
            ['url' => '/user/services.php', 'name' => 'Services', 'icon' => 'cube', 'file' => 'services.php'],
            ['url' => '/user/orders.php', 'name' => 'Order History', 'icon' => 'shopping-cart', 'file' => 'orders.php'],
            ['url' => '/user/add-funds.php', 'name' => 'Add Funds', 'icon' => 'wallet', 'file' => 'add-funds.php'],
            ['url' => '/user/transactions.php', 'name' => 'Transactions', 'icon' => 'credit-card', 'file' => 'transactions.php'],
            ['url' => '/user/tickets.php', 'name' => 'Support Tickets', 'icon' => 'ticket', 'file' => 'tickets.php'],
            ['url' => '/user/api.php', 'name' => 'API Access', 'icon' => 'key', 'file' => 'api.php'],
            ['url' => '/user/profile.php', 'name' => 'Profile & Settings', 'icon' => 'cog', 'file' => 'profile.php'],
        ];

        foreach ($navLinks as $nav):
            $isActive = ($currentRoute === $nav['file']);
            $isHighlight = !empty($nav['highlight']);
        ?>
            <a href="<?= $nav['url'] ?>" 
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 <?= $isActive ? 'bg-purple-600 text-white shadow-md shadow-purple-600/25' : ($isHighlight ? 'bg-purple-50 text-purple-700 hover:bg-purple-100 font-bold' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900') ?>">
                <?= heroicon($nav['icon'], 'w-5 h-5 ' . ($isActive ? 'text-white' : ($isHighlight ? 'text-purple-600' : 'text-zinc-400 group-hover:text-zinc-600'))) ?>
                <span class="truncate"><?= $nav['name'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Bottom Actions -->
    <div class="p-3 border-t border-zinc-100 space-y-1">
        <a href="/logout.php" class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-sm font-medium text-rose-600 hover:bg-rose-50 transition-colors">
            <?= heroicon('arrow-left-on-rectangle', 'w-5 h-5 text-rose-500') ?>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
