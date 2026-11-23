<?php
// Outreach AI Freedom - Master UI Layout (The Only AI Freedom Obsidian & Champagne Gold Theme)
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
        'scraper'   => ['label' => 'Lead Scraper', 'url' => '/scraper.php', 'icon' => 'radar', 'badge' => 'Q-Score™'],
        'inbox'     => ['label' => 'Unified Inbox', 'url' => '/inbox.php', 'icon' => 'messages-square', 'badge' => 'Live', 'badgeColor' => 'emerald'],
        'crm'       => ['label' => 'CRM Pipeline', 'url' => '/crm.php', 'icon' => 'kanban', 'badge' => $dueCount > 0 ? (string)$dueCount : null, 'badgeColor' => 'rose'],
        'outreach'  => ['label' => 'AI Outreach', 'url' => '/outreach.php', 'icon' => 'send-horizontal'],
        'channels'  => ['label' => 'Channels Hub', 'url' => '/channels.php', 'icon' => 'link-2'],
        'analytics' => ['label' => 'Analytics & ROI', 'url' => '/analytics.php', 'icon' => 'bar-chart-3'],
        'onboarding'=> ['label' => 'My Offering & ICP', 'url' => '/onboarding.php', 'icon' => 'briefcase'],
    ];
?><!DOCTYPE html>
<html lang="en" class="h-full antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title) ?> | Outreach — The Only AI Freedom</title>
    <link rel="icon" type="image/png" href="/assets/brand/logo-emblem.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/assets/css/app.css?v=11">
    <script src="/assets/js/theme.js?v=10"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Manrope"', 'system-ui', '-apple-system', 'sans-serif'],
                        serif: ['"Instrument Serif"', 'Georgia', 'serif'],
                    },
                    colors: {
                        gold: {
                            light: '#f3dca4',
                            DEFAULT: '#d9b46c',
                            deep: '#a9823c',
                        },
                        obsidian: {
                            DEFAULT: '#07080c',
                            surface: '#11141c',
                            surface2: '#161a24',
                            border: 'rgba(255, 255, 255, 0.08)',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full flex overflow-hidden font-sans">

    <!-- Ambient Glowing Background (Signature The Only AI Freedom Aesthetic) -->
    <div class="ambient" aria-hidden="true"></div>
    <div class="grain" aria-hidden="true"></div>

    <!-- Mobile Navigation Backdrop -->
    <div id="mobileBackdrop" class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm hidden lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar Navigation -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-[#0c0e14]/95 backdrop-blur-2xl border-r border-white/[0.08] flex flex-col transition-transform duration-300 -translate-x-full lg:translate-x-0 lg:static">
        
        <!-- App Logo & Branding Header -->
        <div class="h-20 flex items-center justify-between px-6 border-b border-white/[0.08] bg-[#0c0e14]">
            <a href="/index.php" class="flex items-center gap-3">
                <div class="h-11 w-11 rounded-xl p-1.5 flex items-center justify-center flex-shrink-0 logo-emblem-badge" style="background-color: #07080c !important; border: 1.5px solid #d9b46c !important; box-shadow: 0 4px 18px rgba(0,0,0,0.5) !important;">
                    <img src="/assets/brand/logo-emblem.png" alt="The Only AI Freedom" class="h-full w-auto object-contain">
                </div>
                <div>
                    <div class="font-extrabold text-[#f4f1ea] text-sm tracking-tight leading-tight flex items-center gap-1">
                        The Only <span class="text-[#d9b46c] font-black">Ai</span> Freedom
                    </div>
                    <div class="text-[11px] text-[#a3a6b0] flex items-center gap-1 font-mono">
                        <span>Outreach OS</span>
                        <span class="text-[9px] px-1.5 py-0.2 rounded-full bg-[#d9b46c]/15 text-[#f3dca4] border border-[#d9b46c]/30 font-bold">v1.0</span>
                    </div>
                </div>
            </a>
            <button class="lg:hidden text-[#a3a6b0] hover:text-white" onclick="toggleSidebar()">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Quick Launch CTA Button -->
        <div class="p-4">
            <a href="/scraper.php" class="btn-gold-cta w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-black font-extrabold text-xs transition duration-200 shadow-[0_4px_20px_rgba(217,180,108,0.2)]">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
                <span>Find Qualified Leads</span>
            </a>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-3 space-y-1 overflow-y-auto custom-scroll">
            <div class="text-[10px] font-bold uppercase tracking-widest text-[#a3a6b0] px-3 py-2">Workspace</div>
            <?php foreach ($navItems as $key => $item): 
                $isActive = ($activePage === $key);
            ?>
                <a href="<?= h($item['url']) ?>" 
                   class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition group <?= $isActive ? 'bg-gradient-to-r from-[#d9b46c]/20 via-[#d9b46c]/10 to-transparent text-[#f3dca4] border-l-2 border-[#d9b46c] shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]' : 'text-[#a3a6b0] hover:text-[#f4f1ea] hover:bg-white/[0.04]' ?>">
                    <div class="flex items-center gap-3">
                        <i data-lucide="<?= $item['icon'] ?>" class="w-4 h-4 <?= $isActive ? 'text-[#d9b46c]' : 'text-[#6e7280] group-hover:text-[#a3a6b0]' ?>"></i>
                        <span><?= h($item['label']) ?></span>
                    </div>
                    <?php if (!empty($item['badge'])): ?>
                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full <?= ($item['badgeColor'] ?? 'gold') === 'rose' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-[#d9b46c]/15 text-[#f3dca4] border border-[#d9b46c]/30' ?>">
                            <?= h($item['badge']) ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- User Profile Card in Sidebar -->
        <div class="p-4 border-t border-white/[0.08] bg-[#07080c]/80">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#a9823c] to-[#f3dca4] p-0.5 flex-shrink-0">
                        <div class="w-full h-full bg-[#11141c] rounded-[10px] flex items-center justify-center font-bold text-[#d9b46c] text-xs">
                            <?= strtoupper(substr($user['name'] ?: 'U', 0, 1)) ?>
                        </div>
                    </div>
                    <div class="truncate">
                        <div class="text-xs font-bold text-[#f4f1ea] truncate"><?= h($user['name']) ?></div>
                        <div class="text-[11px] text-[#a3a6b0] truncate"><?= h($user['business_name'] ?: 'The Only AI Freedom') ?></div>
                    </div>
                </div>
                <a href="/logout.php" title="Sign Out" class="p-2 text-[#a3a6b0] hover:text-rose-400 transition rounded-lg hover:bg-white/[0.06]">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Workspace Container -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-[#07080c]/60">
        
        <!-- Header Bar -->
        <header class="h-16 bg-[#0c0e14]/80 backdrop-blur-xl border-b border-white/[0.08] flex items-center justify-between px-6 z-10">
            <div class="flex items-center gap-4">
                <button class="lg:hidden p-2 text-[#a3a6b0] hover:text-white rounded-lg hover:bg-white/[0.06]" onclick="toggleSidebar()">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-base font-extrabold text-[#f4f1ea] tracking-tight"><?= h($title) ?></h1>
                    <span class="text-white/20 hidden sm:inline">•</span>
                    <span class="text-[11px] text-[#d9b46c] bg-[#d9b46c]/10 border border-[#d9b46c]/20 px-2.5 py-0.5 rounded-full hidden sm:inline-flex items-center gap-1.5 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#d9b46c] animate-pulse"></span>
                        High-Paying Verified Filter Active
                    </span>
                </div>
            </div>

            <!-- Topbar Utilities -->
            <div class="flex items-center gap-3">
                <!-- Theme Switcher (Auto, Light, Dark) -->
                <div class="flex items-center p-1 rounded-xl bg-white/[0.04] border border-white/[0.08] text-xs">
                    <button type="button" data-theme-btn="auto" onclick="setAppTheme('auto')" class="px-2.5 py-1 rounded-lg transition flex items-center gap-1.5 border border-transparent" title="Auto: Follow System Theme">
                        <i data-lucide="laptop" class="w-3.5 h-3.5"></i>
                        <span class="hidden md:inline text-[11px]">Auto</span>
                    </button>
                    <button type="button" data-theme-btn="light" onclick="setAppTheme('light')" class="px-2.5 py-1 rounded-lg transition flex items-center gap-1.5 border border-transparent" title="Light: Champagne Cream Theme">
                        <i data-lucide="sun" class="w-3.5 h-3.5"></i>
                        <span class="hidden md:inline text-[11px]">Light</span>
                    </button>
                    <button type="button" data-theme-btn="dark" onclick="setAppTheme('dark')" class="px-2.5 py-1 rounded-lg transition flex items-center gap-1.5 border border-transparent" title="Dark: Obsidian Night Theme">
                        <i data-lucide="moon" class="w-3.5 h-3.5"></i>
                        <span class="hidden md:inline text-[11px]">Dark</span>
                    </button>
                </div>

                <a href="/onboarding.php" class="text-xs text-[#a3a6b0] hover:text-white bg-[#11141c] hover:bg-[#161a24] border border-white/[0.08] px-3.5 py-1.5 rounded-xl transition flex items-center gap-2">
                    <i data-lucide="briefcase" class="w-3.5 h-3.5 text-[#d9b46c]"></i>
                    <span>Offer: <strong class="text-[#f4f1ea]"><?= h(clip($user['business_name'] ?: 'Configure Profile', 20)) ?></strong></span>
                </a>

                <?php if ($dueCount > 0): ?>
                    <a href="/crm.php?filter=due" class="text-xs bg-rose-500/15 border border-rose-500/30 text-rose-300 hover:bg-rose-500/25 px-3 py-1.5 rounded-xl transition flex items-center gap-1.5">
                        <i data-lucide="bell" class="w-3.5 h-3.5 text-rose-400 animate-bounce"></i>
                        <span><?= $dueCount ?> Due</span>
                    </a>
                <?php endif; ?>

                <a href="https://theonlyaifreedom.com" target="_blank" class="p-2 text-[#a3a6b0] hover:text-[#d9b46c] transition" title="Visit theonlyaifreedom.com">
                    <i data-lucide="globe" class="w-4 h-4"></i>
                </a>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-1 overflow-y-auto custom-scroll p-6 lg:p-8">
            <?= $content ?>
        </main>
    </div>

    <!-- Modal Container for Outreach Dispatcher -->
    <div id="modalBackdrop" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md hidden items-center justify-center p-4">
        <div id="modalBox" class="bg-[#0e111a] border border-white/[0.12] rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col shadow-[0_20px_80px_rgba(0,0,0,0.9)]">
            <!-- Dynamic Modal Content populated by app.js -->
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toastContainer" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <script src="/assets/js/app.js?v=7"></script>
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
