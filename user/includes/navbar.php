<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <!-- Top Navigation Header -->
    <header class="h-16 bg-white border-b border-zinc-200/80 px-4 sm:px-6 lg:px-8 flex items-center justify-between z-30 sticky top-0">
        <!-- Left: Mobile Menu Toggle & Title -->
        <div class="flex items-center gap-3">
            <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-xl text-zinc-500 hover:text-zinc-700 hover:bg-zinc-100">
                <?= heroicon('bars-3', 'w-6 h-6') ?>
            </button>
            <h1 class="text-lg font-bold text-zinc-900 truncate">
                <?= e($pageTitle) ?>
            </h1>
        </div>

        <!-- Right: Actions, Notifications, User Menu -->
        <div class="flex items-center gap-3">
            <!-- Add Funds CTA -->
            <a href="/user/add-funds.php" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 hover:bg-purple-100 text-xs font-semibold border border-purple-200/50 transition-colors">
                <?= heroicon('plus', 'w-3.5 h-3.5') ?>
                <span>Deposit</span>
            </a>

            <!-- Notification Bell with Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="relative p-2 rounded-xl text-zinc-500 hover:text-zinc-700 hover:bg-zinc-100 transition-colors">
                    <?= heroicon('bell', 'w-5 h-5') ?>
                    <?php if ($unreadCount > 0): ?>
                        <span id="notif-badge" class="absolute top-1 right-1 flex items-center justify-center min-w-[18px] h-[18px] px-1 bg-purple-600 text-white text-[10px] font-bold rounded-full ring-2 ring-white">
                            <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
                        </span>
                    <?php endif; ?>
                </button>

                <!-- Notifications Dropdown Panel -->
                <div x-show="open" x-cloak 
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                     class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-xl shadow-purple-600/10 border border-zinc-200 p-0 overflow-hidden z-50">
                    
                    <div class="p-3.5 border-b border-zinc-100 flex items-center justify-between bg-zinc-50/50">
                        <span class="text-xs font-bold text-zinc-900">Notifications</span>
                        <div class="flex items-center gap-2">
                            <?php if ($unreadCount > 0): ?>
                                <button onclick="ApexNotifications.markAllAsRead()" class="text-[11px] font-semibold text-purple-600 hover:text-purple-800">
                                    Mark all read
                                </button>
                            <?php endif; ?>
                            <a href="/user/notifications.php" class="text-[11px] text-zinc-500 hover:text-zinc-700">View all</a>
                        </div>
                    </div>

                    <div class="divide-y divide-zinc-100 max-h-80 overflow-y-auto">
                        <?php if (empty($notifications)): ?>
                            <div class="py-8 px-4 text-center">
                                <div class="w-10 h-10 rounded-full bg-zinc-100 text-zinc-400 flex items-center justify-center mx-auto mb-2">
                                    <?= heroicon('bell', 'w-5 h-5') ?>
                                </div>
                                <p class="text-xs font-medium text-zinc-500">No new notifications</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): ?>
                                <div class="p-3 text-left transition-colors notification-item <?= empty($n['is_read']) ? 'bg-purple-50/40' : 'hover:bg-zinc-50' ?>">
                                    <div class="flex items-start justify-between gap-2">
                                        <h5 class="text-xs font-bold text-zinc-900"><?= e($n['title']) ?></h5>
                                        <span class="text-[10px] text-zinc-400 whitespace-nowrap"><?= timeAgo($n['created_at']) ?></span>
                                    </div>
                                    <p class="text-xs text-zinc-600 mt-1 leading-snug"><?= e($n['message']) ?></p>
                                    <?php if (empty($n['is_read'])): ?>
                                        <button onclick="ApexNotifications.markAsRead(<?= $n['id'] ?>, this)" class="mark-read-btn text-[10px] font-semibold text-purple-600 hover:text-purple-800 mt-1.5 inline-block">
                                            Mark as read
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- User Menu Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-zinc-100 transition-colors">
                    <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 font-bold text-xs flex items-center justify-center border border-purple-200">
                        <?= strtoupper(substr($user['username'], 0, 2)) ?>
                    </div>
                    <span class="hidden sm:inline text-xs font-semibold text-zinc-800"><?= e($user['username']) ?></span>
                    <?= heroicon('chevron-down', 'w-3.5 h-3.5 text-zinc-400') ?>
                </button>

                <div x-show="open" x-cloak 
                     @click.away="open = false"
                     x-transition
                     class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl shadow-purple-600/10 border border-zinc-200 py-1 z-50">
                    <div class="px-4 py-2 border-b border-zinc-100">
                        <p class="text-xs text-zinc-500">Signed in as</p>
                        <p class="text-xs font-bold text-zinc-900 truncate"><?= e($user['email']) ?></p>
                    </div>
                    <a href="/user/profile.php" class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-zinc-700 hover:bg-purple-50 hover:text-purple-700">
                        <?= heroicon('user', 'w-4 h-4 text-zinc-400') ?>
                        <span>Profile & Settings</span>
                    </a>
                    <a href="/user/api.php" class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-zinc-700 hover:bg-purple-50 hover:text-purple-700">
                        <?= heroicon('key', 'w-4 h-4 text-zinc-400') ?>
                        <span>API Keys</span>
                    </a>
                    <div class="border-t border-zinc-100 my-1"></div>
                    <a href="/logout.php" class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50">
                        <?= heroicon('arrow-left-on-rectangle', 'w-4 h-4 text-rose-500') ?>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Scrollable Body Content -->
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
