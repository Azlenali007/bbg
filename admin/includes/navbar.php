<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <!-- Top Header -->
    <header class="h-16 bg-white border-b border-zinc-200/80 px-4 sm:px-6 lg:px-8 flex items-center justify-between z-30 sticky top-0">
        <div class="flex items-center gap-3">
            <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-xl text-zinc-500 hover:text-zinc-700 hover:bg-zinc-100">
                <?= heroicon('bars-3', 'w-6 h-6') ?>
            </button>
            <h1 class="text-base font-bold text-zinc-900 truncate">
                <?= e($pageTitle) ?>
            </h1>
        </div>

        <div class="flex items-center gap-3">
            <a href="/index.php" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-zinc-200 text-xs font-semibold text-zinc-600 hover:bg-zinc-50 transition-colors">
                <?= heroicon('globe-alt', 'w-3.5 h-3.5') ?>
                <span>View Site</span>
            </a>

            <div class="flex items-center gap-2 pl-3 border-l border-zinc-200">
                <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 font-bold text-xs flex items-center justify-center">
                    A
                </div>
                <div class="hidden sm:block text-left">
                    <span class="text-xs font-bold text-zinc-800 block leading-tight"><?= e($admin['username']) ?></span>
                    <span class="text-[10px] text-zinc-400 uppercase font-semibold"><?= e($admin['role']) ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
