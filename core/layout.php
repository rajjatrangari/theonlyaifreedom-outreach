<?php
// Outreach AI Freedom - Master UI Layout
declare(strict_types=1);

function render_layout(string $title, string $activePage, string $content, ?array $user = null): void {
    $user = $user ?? current_user() ?? [
        'name' => 'Freedom Guest',
        'email' => 'guest@theonlyaifreedom.in',
        'business_name' => 'The Only AI Freedom',
    ];

    $dueCount = 0;
    if (!empty($user['id'])) {
        $dueCount = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ? AND next_followup_at <= ? AND pipeline_stage NOT IN ('closed_won', 'closed_lost')", [
            $user['id'],
            today()
        ]);
    }

    $navItems = [
        'dashboard' => ['label' => 'Dashboard', 'url' => '/index.php', 'icon' => 'layout-dashboard'],
        'scraper'   => ['label' => 'Lead Scraper', 'url' => '/scraper.php', 'icon' => 'radar', 'badge' => 'AI Engine'],
        'crm'       => ['label' => 'CRM Pipeline', 'url' => '/crm.php', 'icon' => 'kanban', 'badge' => $dueCount > 0 ? (string)$dueCount : null, 'badgeColor' => 'rose'],
        'outreach'  => ['label' => 'AI Outreach', 'url' => '/outreach.php', 'icon' => 'send-horizontal'],
        'channels'  => ['label' => 'Channels Hub', 'url' => '/channels.php', 'icon' => 'link-2'],
        'analytics' => ['label' => 'Analytics', 'url' => '/analytics.php', 'icon' => 'bar-chart-3'],
        'onboarding'=> ['label' => 'My Offering & ICP', 'url' => '/onboarding.php', 'icon' => 'briefcase'],
    ];
?><!DOCTYPE html>
<html lang="en" class="dark h-full bg-[#090d16] text-slate-100 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title) ?> | Outreach AI Freedom</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        },
                        accent: {
                            500: '#8b5cf6',
                            600: '#7c3aed',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full flex overflow-hidden font-sans bg-[#090d16] text-slate-200">

    <!-- Mobile Navigation Backdrop -->
    <div id="mobileBackdrop" class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm hidden lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar Navigation -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-[#0c1220]/95 backdrop-blur-xl border-r border-slate-800/80 flex flex-col transition-transform duration-300 -translate-x-full lg:translate-x-0 lg:static">
        
        <!-- App Logo & Workspace Header -->
        <div class="h-20 flex items-center justify-between px-6 border-b border-slate-800/80">
            <a href="/index.php" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 via-teal-400 to-indigo-500 p-0.5 shadow-lg shadow-emerald-500/20">
                    <div class="w-full h-full bg-[#090d16] rounded-[10px] flex items-center justify-center">
                        <i data-lucide="crosshair" class="w-5 h-5 text-emerald-400"></i>
                    </div>
                </div>
                <div>
                    <div class="font-bold text-white text-base tracking-tight flex items-center gap-1.5">
                        Outreach <span class="text-xs px-1.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-mono">v1.0</span>
                    </div>
                    <div class="text-xs text-slate-400 truncate max-w-[150px]">
                        <?= h($user['business_name'] ?: 'The Only AI Freedom') ?>
                    </div>
                </div>
            </a>
            <button class="lg:hidden text-slate-400 hover:text-white" onclick="toggleSidebar()">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <!-- Quick Scrape Call to Action -->
        <div class="p-4">
            <a href="/scraper.php" class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-black font-semibold text-sm shadow-lg shadow-emerald-500/25 transition duration-150">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
                <span>Find Qualified Leads</span>
            </a>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 space-y-1.5 overflow-y-auto custom-scroll">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-300 px-3 py-2">Navigation</div>
            <?php foreach ($navItems as $key => $item): 
                $isActive = ($activePage === $key);
            ?>
                <a href="<?= h($item['url']) ?>" 
                   class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition group <?= $isActive ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50' ?>">
                    <div class="flex items-center gap-3">
                        <i data-lucide="<?= $item['icon'] ?>" class="w-4 h-4 <?= $isActive ? 'text-emerald-400' : 'text-slate-400 group-hover:text-slate-200' ?>"></i>
                        <span><?= h($item['label']) ?></span>
                    </div>
                    <?php if (!empty($item['badge'])): ?>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full <?= ($item['badgeColor'] ?? 'emerald') === 'rose' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' ?>">
                            <?= h($item['badge']) ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- User Profile Card in Sidebar -->
        <div class="p-4 border-t border-slate-800/80 bg-slate-900/40">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-emerald-400 text-sm">
                        <?= strtoupper(substr($user['name'] ?: 'U', 0, 1)) ?>
                    </div>
                    <div class="truncate">
                        <div class="text-sm font-semibold text-slate-200 truncate"><?= h($user['name']) ?></div>
                        <div class="text-xs text-slate-400 truncate"><?= h($user['email']) ?></div>
                    </div>
                </div>
                <a href="/logout.php" title="Sign Out" class="p-2 text-slate-400 hover:text-rose-400 transition rounded-lg hover:bg-slate-800/80">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        
        <!-- Top App Bar -->
        <header class="h-16 bg-[#0c1220]/70 backdrop-blur-md border-b border-slate-800/80 flex items-center justify-between px-6 z-10">
            <div class="flex items-center gap-4">
                <button class="lg:hidden p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800" onclick="toggleSidebar()">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-bold text-white tracking-tight"><?= h($title) ?></h1>
                    <span class="text-slate-600 hidden sm:inline">•</span>
                    <span class="text-xs text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-0.5 rounded-full hidden sm:inline-flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        High-Paying Qualified Filter Active
                    </span>
                </div>
            </div>

            <!-- Topbar Action Utilities -->
            <div class="flex items-center gap-3">
                <a href="/onboarding.php" class="text-xs text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-800 border border-slate-700/80 px-3 py-1.5 rounded-xl transition flex items-center gap-2">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>Offer: <strong class="text-white"><?= h(clip($user['business_name'] ?: 'Configure Profile', 20)) ?></strong></span>
                </a>

                <?php if ($dueCount > 0): ?>
                    <a href="/crm.php?filter=due" class="text-xs bg-rose-500/15 border border-rose-500/30 text-rose-300 hover:bg-rose-500/25 px-3 py-1.5 rounded-xl transition flex items-center gap-1.5">
                        <i data-lucide="bell" class="w-3.5 h-3.5 text-rose-400 animate-bounce"></i>
                        <span><?= $dueCount ?> Follow-up<?= $dueCount > 1 ? 's' : '' ?> Due</span>
                    </a>
                <?php endif; ?>

                <a href="https://theonlyaifreedom.in" target="_blank" class="p-2 text-slate-400 hover:text-slate-200 transition" title="The Only AI Freedom Hub">
                    <i data-lucide="globe" class="w-4 h-4"></i>
                </a>
            </div>
        </header>

        <!-- Main Workspace Body -->
        <main class="flex-1 overflow-y-auto custom-scroll p-6 lg:p-8">
            <?= $content ?>
        </main>
    </div>

    <!-- Modal Container for Outreach Dispatcher -->
    <div id="modalBackdrop" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-md hidden items-center justify-center p-4">
        <div id="modalBox" class="bg-[#0f172a] border border-slate-700/80 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl">
            <!-- Dynamic Modal Content populated by app.js -->
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toastContainer" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <script src="/assets/js/app.js"></script>
    <script>
        lucide.createIcons();
        function toggleSidebar() {
            const sb = document.getElementById('sidebar');
            const bd = document.getElementById('mobileBackdrop');
            sb.classList.toggle('-translate-x-full');
            bd.classList.toggle('hidden');
        }
    </script>
</body>
</html><?php
}
