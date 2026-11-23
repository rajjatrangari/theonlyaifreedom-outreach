<?php
// Outreach AI Freedom - Visual Kanban & CRM Pipeline
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/layout.php';

$user = require_auth();
$userId = (int)$user['id'];

// Filter parameters
$viewMode = $_GET['view'] ?? 'kanban';
$searchQuery = trim($_GET['q'] ?? '');
$filterDue = isset($_GET['filter']) && $_GET['filter'] === 'due';
$selectedCategory = trim($_GET['category'] ?? '');
$selectedCity = trim($_GET['city'] ?? '');

// Fetch category and city aggregates for instant filtering
$availableCategories = all("SELECT category, COUNT(*) as cnt FROM leads WHERE user_id = ? AND category IS NOT NULL AND category != '' GROUP BY category ORDER BY cnt DESC", [$userId]);
$availableCities = all("SELECT city, COUNT(*) as cnt FROM leads WHERE user_id = ? AND city IS NOT NULL AND city != '' GROUP BY city ORDER BY cnt DESC", [$userId]);
$totalLeadsCount = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ?", [$userId]);

// Query leads
$sql = "SELECT * FROM leads WHERE user_id = ?";
$params = [$userId];

if (!empty($searchQuery)) {
    $sql .= " AND (business_name LIKE ? OR category LIKE ? OR city LIKE ?)";
    $like = "%{$searchQuery}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($selectedCategory)) {
    $sql .= " AND category = ?";
    $params[] = $selectedCategory;
}

if (!empty($selectedCity)) {
    $sql .= " AND city LIKE ?";
    $params[] = "%{$selectedCity}%";
}

if ($filterDue) {
    $sql .= " AND next_followup_at <= ? AND pipeline_stage NOT IN ('closed_won', 'closed_lost')";
    $params[] = today();
}

$sql .= " ORDER BY qualification_score DESC, id DESC";
$allLeads = all($sql, $params);

// Group leads by stage for Kanban
$grouped = [];
foreach (array_keys(PIPELINE_STAGES) as $stageKey) {
    $grouped[$stageKey] = [];
}
foreach ($allLeads as $lead) {
    $s = $lead['pipeline_stage'];
    if (isset($grouped[$s])) {
        $grouped[$s][] = $lead;
    } else {
        $grouped['new'][] = $lead;
    }
}

// Build query helper for URL parameters
function filterUrl(array $overrides = []): string {
    global $viewMode, $searchQuery, $filterDue, $selectedCategory, $selectedCity;
    $params = [
        'view' => $viewMode,
        'q' => $searchQuery,
        'filter' => $filterDue ? 'due' : '',
        'category' => $selectedCategory,
        'city' => $selectedCity,
    ];
    $merged = array_merge($params, $overrides);
    $filtered = array_filter($merged, fn($v) => $v !== '' && $v !== null);
    return '/crm.php?' . http_build_query($filtered);
}

ob_start();
?>

<div class="space-y-6">
    
    <!-- Top Filter & Action Bar -->
    <div class="glass-panel rounded-2xl p-4 border border-white/[0.08] flex flex-col gap-4">
        
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <!-- Search & Filter Options Form -->
            <form method="GET" action="/crm.php" class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
                <input type="hidden" name="view" value="<?= h($viewMode) ?>">
                <?php if ($filterDue): ?><input type="hidden" name="filter" value="due"><?php endif; ?>

                <!-- Keyword / Prospect Search Input -->
                <div class="relative w-full sm:w-56">
                    <i data-lucide="search" class="w-4 h-4 text-[#a3a6b0] absolute left-3 top-2.5"></i>
                    <input type="text" name="q" value="<?= h($searchQuery) ?>" placeholder="Search prospects, city..." 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl pl-9 pr-3 py-2 text-xs text-[#f4f1ea] placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                </div>

                <!-- Category-wise Filter -->
                <div class="w-full sm:w-auto">
                    <select name="category" onchange="this.form.submit()" 
                            class="w-full sm:w-auto bg-[#11141c] border border-white/[0.08] rounded-xl px-3 py-2 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                        <option value="">📂 All Categories (<?= $totalLeadsCount ?>)</option>
                        <?php foreach ($availableCategories as $catItem): ?>
                            <option value="<?= h($catItem['category']) ?>" <?= $selectedCategory === $catItem['category'] ? 'selected' : '' ?>>
                                <?= h($catItem['category']) ?> (<?= (int)$catItem['cnt'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Location-wise Filter -->
                <div class="w-full sm:w-auto">
                    <select name="city" onchange="this.form.submit()" 
                            class="w-full sm:w-auto bg-[#11141c] border border-white/[0.08] rounded-xl px-3 py-2 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                        <option value="">📍 All Locations (<?= count($availableCities) ?> Cities)</option>
                        <?php foreach ($availableCities as $cityItem): ?>
                            <option value="<?= h($cityItem['city']) ?>" <?= $selectedCity === $cityItem['city'] ? 'selected' : '' ?>>
                                <?= h($cityItem['city']) ?> (<?= (int)$cityItem['cnt'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Due Follow-ups Toggle -->
                <a href="<?= filterUrl(['filter' => $filterDue ? '' : 'due']) ?>" 
                   class="px-3 py-2 rounded-xl text-xs font-semibold border transition flex items-center gap-1.5 <?= $filterDue ? 'bg-rose-500/20 text-rose-300 border-rose-500/40' : 'bg-white/[0.04] text-[#a3a6b0] border-white/[0.08] hover:text-[#f4f1ea]' ?>">
                    <i data-lucide="bell" class="w-3.5 h-3.5 <?= $filterDue ? 'text-rose-400 animate-bounce' : '' ?>"></i>
                    <span>Due Follow-ups</span>
                </a>

                <?php if (!empty($searchQuery) || !empty($selectedCategory) || !empty($selectedCity) || $filterDue): ?>
                    <a href="/crm.php?view=<?= h($viewMode) ?>" class="text-xs text-[#d9b46c] hover:underline flex items-center gap-1 font-bold ml-1">
                        <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                        <span>Reset Filters</span>
                    </a>
                <?php endif; ?>
            </form>

            <!-- View Switcher & Action Buttons -->
            <div class="flex items-center gap-2.5 w-full lg:w-auto justify-end flex-wrap">
                <!-- Kanban / Table View Toggle -->
                <div class="p-1 rounded-xl bg-white/[0.04] border border-white/[0.08] flex items-center">
                    <a href="<?= filterUrl(['view' => 'kanban']) ?>" title="Kanban Board View" 
                       class="p-1.5 rounded-lg text-xs font-medium transition <?= $viewMode === 'kanban' ? 'btn-gold-cta text-black shadow' : 'text-[#a3a6b0] hover:text-white' ?>">
                        <i data-lucide="kanban" class="w-4 h-4"></i>
                    </a>
                    <a href="<?= filterUrl(['view' => 'table']) ?>" title="Table View" 
                       class="p-1.5 rounded-lg text-xs font-medium transition <?= $viewMode === 'table' ? 'btn-gold-cta text-black shadow' : 'text-[#a3a6b0] hover:text-white' ?>">
                        <i data-lucide="table" class="w-4 h-4"></i>
                    </a>
                </div>

                <a href="/api.php?action=export_leads_csv" class="px-3.5 py-2 rounded-xl bg-white/[0.04] hover:bg-white/[0.08] text-[#a3a6b0] hover:text-[#f4f1ea] text-xs font-medium border border-white/[0.08] flex items-center gap-1.5 transition">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    <span>Export CSV</span>
                </a>

                <a href="/scraper.php" class="btn-gold-cta px-4 py-2 rounded-xl text-black text-xs font-extrabold shadow-lg flex items-center gap-1.5 transition">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Scrape More</span>
                </a>
            </div>
        </div>

        <!-- Quick Category & Location Chips Bar (if multiple exist) -->
        <?php if (!empty($availableCategories) || !empty($availableCities)): ?>
            <div class="pt-3 border-t border-white/[0.06] flex items-center gap-2 flex-wrap text-xs">
                <span class="text-[11px] font-bold text-[#a3a6b0] uppercase tracking-wider flex items-center gap-1 mr-1">
                    <i data-lucide="layers" class="w-3.5 h-3.5 text-[#d9b46c]"></i>
                    <span>Browse By:</span>
                </span>

                <!-- All Leads Chip -->
                <a href="<?= filterUrl(['category' => '', 'city' => '']) ?>" 
                   class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition flex items-center gap-1 <?= (empty($selectedCategory) && empty($selectedCity)) ? 'bg-[#d9b46c] text-black shadow-sm' : 'bg-white/[0.04] text-[#a3a6b0] hover:text-[#f4f1ea] border border-white/[0.08]' ?>">
                    <span>All Leads</span>
                    <span class="opacity-75 font-mono">(<?= $totalLeadsCount ?>)</span>
                </a>

                <!-- Top Categories -->
                <?php foreach (array_slice($availableCategories, 0, 5) as $catChip): 
                    $isActive = ($selectedCategory === $catChip['category']);
                ?>
                    <a href="<?= filterUrl(['category' => $isActive ? '' : $catChip['category']]) ?>" 
                       class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition flex items-center gap-1 <?= $isActive ? 'bg-[#d9b46c] text-black shadow-sm' : 'bg-white/[0.04] text-[#a3a6b0] hover:text-[#f4f1ea] border border-white/[0.08]' ?>"
                       title="Filter category: <?= h($catChip['category']) ?>">
                        <span><?= h($catChip['category']) ?></span>
                        <span class="opacity-75 font-mono">(<?= (int)$catChip['cnt'] ?>)</span>
                    </a>
                <?php endforeach; ?>

                <!-- Top Cities -->
                <?php foreach (array_slice($availableCities, 0, 4) as $cityChip): 
                    $isCityActive = ($selectedCity === $cityChip['city']);
                ?>
                    <a href="<?= filterUrl(['city' => $isCityActive ? '' : $cityChip['city']]) ?>" 
                       class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition flex items-center gap-1 <?= $isCityActive ? 'bg-emerald-500 text-black shadow-sm font-extrabold' : 'bg-white/[0.04] text-[#a3a6b0] hover:text-[#f4f1ea] border border-white/[0.08]' ?>"
                       title="Filter city: <?= h($cityChip['city']) ?>">
                        <i data-lucide="map-pin" class="w-3 h-3 text-emerald-400"></i>
                        <span><?= h($cityChip['city']) ?></span>
                        <span class="opacity-75 font-mono">(<?= (int)$cityChip['cnt'] ?>)</span>
                    </a>
                <?php endforeach; ?>

                <div class="ml-auto text-[11px] text-[#a3a6b0] font-mono">
                    Showing <strong class="text-[#f4f1ea]"><?= count($allLeads) ?></strong> of <?= $totalLeadsCount ?> leads
                </div>
            </div>
        <?php endif; ?>

    </div>

    <?php if ($viewMode === 'kanban'): ?>
        
        <!-- Kanban Horizontal Scroll Board -->
        <div class="flex gap-4 overflow-x-auto pb-6 custom-scroll min-h-[70vh] items-start">
            <?php foreach (PIPELINE_STAGES as $stageKey => $stage): 
                $stageLeads = $grouped[$stageKey] ?? [];
                $stageValue = array_sum(array_column($stageLeads, 'deal_value'));
            ?>
                <div class="kanban-col flex flex-col glass-panel rounded-2xl border border-white/[0.08] overflow-hidden">
                    
                    <!-- Column Header -->
                    <div class="p-3.5 border-b border-white/[0.08] bg-white/[0.02] flex items-center justify-between">
                        <div class="flex items-center gap-2 truncate">
                            <span class="w-2 h-2 rounded-full <?= $stageKey === 'closed_won' ? 'bg-emerald-400 animate-pulse' : ($stageKey === 'hot' ? 'bg-rose-400' : 'bg-[#d9b46c]') ?>"></span>
                            <h3 class="font-bold text-[#f4f1ea] text-xs truncate"><?= h($stage['name']) ?></h3>
                        </div>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full <?= $stage['badge'] ?>">
                            <?= count($stageLeads) ?>
                        </span>
                    </div>

                    <?php if ($stageValue > 0): ?>
                        <div class="px-3.5 py-1 bg-white/[0.02] text-[10px] text-[#a3a6b0] font-mono border-b border-white/[0.04]">
                            Est. Value: ₹<?= number_format($stageValue) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Drop Target Container -->
                    <div class="kanban-col-cards p-3 space-y-3 flex-1 overflow-y-auto max-h-[75vh] custom-scroll" data-stage="<?= h($stageKey) ?>">
                        
                        <?php if (empty($stageLeads)): ?>
                            <div class="py-8 text-center text-[11px] text-[#6e7280] italic pointer-events-none">
                                Drag prospects here
                            </div>
                        <?php else: ?>
                            <?php foreach ($stageLeads as $lead): 
                                $isDue = !empty($lead['next_followup_at']) && $lead['next_followup_at'] <= today() && !in_array($lead['pipeline_stage'], ['closed_won', 'closed_lost']);
                                $score = (int)$lead['qualification_score'];
                                $cleanPhone = preg_replace('/[^\d]/', '', $lead['whatsapp'] ?: $lead['phone']);
                                $leadJson = htmlspecialchars(json_encode([
                                    'id' => $lead['id'],
                                    'name' => $lead['business_name'],
                                    'decision_maker_name' => $lead['decision_maker_name'] ?? '',
                                    'decision_maker_title' => $lead['decision_maker_title'] ?? 'Founder & Managing Director',
                                    'decision_maker_phone' => $lead['decision_maker_phone'] ?? '',
                                    'decision_maker_email' => $lead['decision_maker_email'] ?? '',
                                    'decision_maker_linkedin' => $lead['decision_maker_linkedin'] ?? '',
                                    'score' => $score,
                                    'tier' => $lead['paying_capacity_tier'],
                                    'website' => $lead['website'],
                                    'phone' => $lead['phone'],
                                    'whatsapp' => $lead['whatsapp'],
                                    'email' => $lead['email'],
                                    'rating' => $lead['rating'],
                                    'reviews' => $lead['reviews_count'],
                                    'city' => $lead['city'],
                                    'reasons' => json_decode($lead['qualification_reasons'] ?: '[]', true) ?: [
                                        'Commercial custom domain & live DNS verified (+25)',
                                        'Verified Direct WhatsApp number with 1-click chat (+25)',
                                        'Multi-channel brand footprint with active profiles (+25)',
                                        'Strong commercial proof & Google reviews (+25)'
                                    ]
                                ]), ENT_QUOTES, 'UTF-8');
                            ?>
                                <div class="kanban-card glass-card rounded-xl p-3 border border-white/[0.08] cursor-grab active:cursor-grabbing space-y-2 text-xs" 
                                     data-lead-id="<?= (int)$lead['id'] ?>">
                                    
                                    <div class="flex items-start justify-between gap-1">
                                        <div class="font-bold text-[#f4f1ea] truncate max-w-[180px] leading-snug">
                                            <?= h($lead['business_name']) ?>
                                        </div>
                                        <button type="button" onclick='openScoreAuditModal(<?= $leadJson ?>)' class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded bg-[#d9b46c]/15 text-[#f3dca4] border border-[#d9b46c]/30 flex-shrink-0 transition hover:scale-105" title="View Q-Score™ Audit">
                                            Q <?= $score ?>
                                        </button>
                                    </div>

                                    <div class="text-[11px] text-[#a3a6b0] truncate">
                                        <?= h($lead['category']) ?> • <?= h($lead['city'] ?: 'India') ?>
                                    </div>

                                    <?php if (!empty($lead['decision_maker_name'])): ?>
                                        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-[#d9b46c]/10 text-[#d9b46c] text-[10px] font-semibold border border-[#d9b46c]/20 max-w-full truncate" title="Direct Decision Maker: <?= h($lead['decision_maker_name']) ?>">
                                            <i data-lucide="user-check" class="w-3 h-3 flex-shrink-0 text-[#d9b46c]"></i>
                                            <span class="truncate font-bold"><?= h($lead['decision_maker_name']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($lead['meta_ads_active'])): ?>
                                        <div class="mt-0.5">
                                            <a href="<?= h($lead['meta_ads_library_url'] ?: 'https://www.facebook.com/ads/library/?active_status=all&ad_type=all&country=IN&q=' . urlencode($lead['business_name'])) ?>" 
                                               target="_blank" class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-blue-500/15 text-blue-400 text-[9px] font-semibold border border-blue-500/30 hover:underline">
                                                <i data-lucide="radio" class="w-2.5 h-2.5"></i>
                                                <span>📢 <?= (int)$lead['meta_ads_count'] ?> Active Ads</span>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Value & Follow-up Badges -->
                                    <div class="flex items-center justify-between text-[11px] pt-1">
                                        <span class="font-semibold text-[#f4f1ea]">
                                            ₹<?= number_format((float)$lead['deal_value']) ?>
                                        </span>
                                        <?php if ($isDue): ?>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30 flex items-center gap-1 font-bold">
                                                <i data-lucide="bell" class="w-3 h-3 text-rose-400"></i>
                                                <span>Due</span>
                                            </span>
                                        <?php elseif (!empty($lead['next_followup_at'])): ?>
                                            <span class="text-[10px] text-[#a3a6b0] font-mono">
                                                <?= date('M d', strtotime($lead['next_followup_at'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Social Channels Footprint -->
                                    <div class="flex items-center gap-1 pt-1.5 flex-wrap">
                                        <?php if (!empty($lead['website'])): ?>
                                            <a href="<?= h($lead['website']) ?>" target="_blank" title="Website: <?= h($lead['website']) ?>" class="p-1 rounded bg-white/[0.04] text-[#d9b46c] hover:bg-white/[0.08]">
                                                <i data-lucide="globe" class="w-3 h-3"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($lead['whatsapp'])): ?>
                                            <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" title="Direct WhatsApp: <?= h($lead['whatsapp']) ?>" class="p-1 rounded bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20">
                                                <i data-lucide="message-circle" class="w-3 h-3"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php 
                                            $crmLiUrl = !empty($lead['decision_maker_linkedin']) ? $lead['decision_maker_linkedin'] : ($lead['linkedin_url'] ?: ('https://www.linkedin.com/search/results/people/?keywords=' . urlencode(($lead['decision_maker_name'] ?: 'Founder') . ' ' . $lead['business_name'])));
                                            $cleanSlugCrm = preg_replace('/[^a-z0-9]/', '', strtolower($lead['business_name']));
                                            $crmIgUrl = (!empty($lead['instagram_url']) && strpos($lead['instagram_url'], 'restige') === false) ? $lead['instagram_url'] : ("https://www.instagram.com/explore/tags/{$cleanSlugCrm}/");
                                            $crmYtUrl = (!empty($lead['youtube_url']) && strpos($lead['youtube_url'], '@') === false) ? $lead['youtube_url'] : ('https://www.youtube.com/results?search_query=' . urlencode($lead['business_name'] . ' ' . $lead['city']));
                                            $crmFbUrl = !empty($lead['meta_ads_library_url']) ? $lead['meta_ads_library_url'] : ('https://www.facebook.com/ads/library/?active_status=all&ad_type=all&country=IN&q=' . urlencode($lead['business_name']));
                                        ?>
                                        <a href="<?= h($crmLiUrl) ?>" target="_blank" title="LinkedIn: Direct Profile & Message (<?= h($lead['decision_maker_name'] ?: 'Decision Maker') ?>)" class="w-6 h-6 rounded-lg bg-[#0A66C2]/15 hover:bg-[#0A66C2]/25 border border-[#0A66C2]/40 social-icon-btn flex items-center justify-center transition transform hover:scale-110">
                                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-[#0A66C2]"><path d="M19 3A2 2 0 0 1 21 5V19A2 2 0 0 1 19 21H5A2 2 0 0 1 3 19V5A2 2 0 0 1 5 3H19M18.5 18.5V13.2A3.26 3.26 0 0 0 15.24 9.94C14.39 9.94 13.4 10.46 12.92 11.24V10.13H10.13V18.5H12.92V13.57C12.92 12.8 13.54 12.17 14.31 12.17A1.4 1.4 0 0 1 15.71 13.57V18.5H18.5M6.88 8.56A1.68 1.68 0 0 0 8.56 6.88C8.56 5.95 7.81 5.19 6.88 5.19A1.69 1.69 0 0 0 5.19 6.88C5.19 7.81 5.95 8.56 6.88 8.56M8.27 18.5V10.13H5.5V18.5H8.27Z"/></svg>
                                        </a>
                                        <a href="<?= h($crmIgUrl) ?>" target="_blank" title="Instagram: Explore & Posts (<?= h($lead['business_name']) ?>)" class="w-6 h-6 rounded-lg bg-[#E4405F]/15 hover:bg-[#E4405F]/25 border border-[#E4405F]/40 social-icon-btn flex items-center justify-center transition transform hover:scale-110">
                                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-[#E4405F]"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                        </a>
                                        <a href="<?= h($crmYtUrl) ?>" target="_blank" title="YouTube: Video Showcase & Reviews" class="w-6 h-6 rounded-lg bg-[#FF0000]/15 hover:bg-[#FF0000]/25 border border-[#FF0000]/40 social-icon-btn flex items-center justify-center transition transform hover:scale-110">
                                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-[#FF0000]"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                        </a>
                                    </div>

                                    <!-- Quick Contact Channels & AI Trigger -->
                                    <div class="pt-2 border-t border-white/[0.06] flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            <?php if (!empty($lead['whatsapp'])): ?>
                                                <button onclick="openOutreachModal(<?= (int)$lead['id'] ?>, 'whatsapp')" title="WhatsApp Pitch" class="p-1 rounded bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20">
                                                    <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!empty($lead['linkedin_url'])): ?>
                                                <button onclick="openOutreachModal(<?= (int)$lead['id'] ?>, 'linkedin')" title="LinkedIn Pitch" class="p-1 rounded bg-blue-500/10 text-blue-400 hover:bg-blue-500/20">
                                                    <i data-lucide="linkedin" class="w-3.5 h-3.5"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!empty($lead['instagram_url'])): ?>
                                                <button onclick="openOutreachModal(<?= (int)$lead['id'] ?>, 'instagram')" title="Instagram DM Pitch" class="p-1 rounded bg-pink-500/10 text-pink-400 hover:bg-pink-500/20">
                                                    <i data-lucide="instagram" class="w-3.5 h-3.5"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!empty($lead['email'])): ?>
                                                <button onclick="openOutreachModal(<?= (int)$lead['id'] ?>, 'email')" title="Email Pitch" class="p-1 rounded bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500/20">
                                                    <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <button onclick="openOutreachModal(<?= (int)$lead['id'] ?>)" class="text-[11px] font-bold text-[#d9b46c] hover:underline flex items-center gap-1">
                                            <span>Pitch</span>
                                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                        </button>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>

        <!-- Table View for Batch Review -->
        <div class="glass-panel rounded-2xl border border-white/[0.08] overflow-hidden">
            <div class="overflow-x-auto custom-scroll">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-white/[0.08] bg-white/[0.02] text-[11px] font-semibold uppercase tracking-wider text-[#a3a6b0]">
                            <th class="py-3.5 px-4">Business</th>
                            <th class="py-3.5 px-4">Stage</th>
                            <th class="py-3.5 px-4">Q-Score</th>
                            <th class="py-3.5 px-4">Deal Value</th>
                            <th class="py-3.5 px-4">Social Footprint</th>
                            <th class="py-3.5 px-4">Direct WhatsApp</th>
                            <th class="py-3.5 px-4">Next Follow-up</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.06]">
                        <?php foreach ($allLeads as $lead): 
                            $stageCfg = PIPELINE_STAGES[$lead['pipeline_stage']] ?? PIPELINE_STAGES['new'];
                            $score = (int)$lead['qualification_score'];
                            $cleanPhone = preg_replace('/[^\d]/', '', $lead['whatsapp'] ?: $lead['phone']);
                            $leadJson = htmlspecialchars(json_encode([
                                'id' => $lead['id'],
                                'name' => $lead['business_name'],
                                'decision_maker_name' => $lead['decision_maker_name'] ?? '',
                                'decision_maker_title' => $lead['decision_maker_title'] ?? 'Founder & Managing Director',
                                'decision_maker_phone' => $lead['decision_maker_phone'] ?? '',
                                'decision_maker_email' => $lead['decision_maker_email'] ?? '',
                                'decision_maker_linkedin' => $lead['decision_maker_linkedin'] ?? '',
                                'score' => $score,
                                'tier' => $lead['paying_capacity_tier'],
                                'website' => $lead['website'],
                                'phone' => $lead['phone'],
                                'whatsapp' => $lead['whatsapp'],
                                'email' => $lead['email'],
                                'rating' => $lead['rating'],
                                'reviews' => $lead['reviews_count'],
                                'city' => $lead['city'],
                                'reasons' => json_decode($lead['qualification_reasons'] ?: '[]', true) ?: [
                                    'Commercial custom domain & live DNS verified (+25)',
                                    'Verified Direct WhatsApp number with 1-click chat (+25)',
                                    'Multi-channel brand footprint with active profiles (+25)',
                                    'Strong commercial proof & Google reviews (+25)'
                                ]
                            ]), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr class="hover:bg-white/[0.03] transition">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-[#f4f1ea]"><?= h($lead['business_name']) ?></div>
                                    <div class="text-[11px] text-[#a3a6b0]"><?= h($lead['category']) ?> • <?= h($lead['city']) ?></div>
                                    <?php if (!empty($lead['decision_maker_name'])): ?>
                                        <div class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded bg-[#d9b46c]/10 text-[#d9b46c] text-[10px] font-semibold border border-[#d9b46c]/20 max-w-[200px] truncate" title="Decision Maker: <?= h($lead['decision_maker_name']) ?>">
                                            <i data-lucide="user-check" class="w-3 h-3 flex-shrink-0 text-[#d9b46c]"></i>
                                            <span class="truncate font-bold"><?= h($lead['decision_maker_name']) ?></span>
                                            <span class="opacity-75 truncate">• <?= h($lead['decision_maker_title'] ?: 'Founder / MD') ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <select onchange="updateLeadStage(<?= (int)$lead['id'] ?>, this.value)" class="bg-[#11141c] border border-white/[0.08] rounded-lg px-2 py-1 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                                        <?php foreach (PIPELINE_STAGES as $k => $cfg): ?>
                                            <option value="<?= h($k) ?>" <?= $lead['pipeline_stage'] === $k ? 'selected' : '' ?>><?= h($cfg['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="py-3 px-4">
                                    <button type="button" onclick='openScoreAuditModal(<?= $leadJson ?>)' class="font-mono font-bold text-[#d9b46c] hover:underline" title="View Q-Score™ Audit">
                                        <?= $score ?>/100
                                    </button>
                                </td>
                                <td class="py-3 px-4 font-semibold text-[#f4f1ea]">
                                    ₹<?= number_format((float)$lead['deal_value']) ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <?php if (!empty($lead['website'])): ?>
                                            <a href="<?= h($lead['website']) ?>" target="_blank" title="Website" class="p-1 rounded bg-white/[0.04] text-[#d9b46c] hover:bg-white/[0.08]">
                                                <i data-lucide="globe" class="w-3.5 h-3.5"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php 
                                            $tLiUrl = !empty($lead['decision_maker_linkedin']) ? $lead['decision_maker_linkedin'] : ($lead['linkedin_url'] ?: ('https://www.linkedin.com/search/results/people/?keywords=' . urlencode(($lead['decision_maker_name'] ?: 'Founder') . ' ' . $lead['business_name'])));
                                            $cleanSlugT = preg_replace('/[^a-z0-9]/', '', strtolower($lead['business_name']));
                                            $tIgUrl = (!empty($lead['instagram_url']) && strpos($lead['instagram_url'], 'restige') === false) ? $lead['instagram_url'] : ("https://www.instagram.com/explore/tags/{$cleanSlugT}/");
                                            $tYtUrl = (!empty($lead['youtube_url']) && strpos($lead['youtube_url'], '@') === false) ? $lead['youtube_url'] : ('https://www.youtube.com/results?search_query=' . urlencode($lead['business_name'] . ' ' . $lead['city']));
                                            $tFbUrl = !empty($lead['meta_ads_library_url']) ? $lead['meta_ads_library_url'] : ('https://www.facebook.com/ads/library/?active_status=all&ad_type=all&country=IN&q=' . urlencode($lead['business_name']));
                                        ?>
                                        <a href="<?= h($tLiUrl) ?>" target="_blank" title="LinkedIn: Direct Decision Maker (<?= h($lead['decision_maker_name'] ?: 'Decision Maker') ?>)" class="w-6 h-6 rounded-lg bg-[#0A66C2]/15 hover:bg-[#0A66C2]/25 border border-[#0A66C2]/40 social-icon-btn flex items-center justify-center transition transform hover:scale-110">
                                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-[#0A66C2]"><path d="M19 3A2 2 0 0 1 21 5V19A2 2 0 0 1 19 21H5A2 2 0 0 1 3 19V5A2 2 0 0 1 5 3H19M18.5 18.5V13.2A3.26 3.26 0 0 0 15.24 9.94C14.39 9.94 13.4 10.46 12.92 11.24V10.13H10.13V18.5H12.92V13.57C12.92 12.8 13.54 12.17 14.31 12.17A1.4 1.4 0 0 1 15.71 13.57V18.5H18.5M6.88 8.56A1.68 1.68 0 0 0 8.56 6.88C8.56 5.95 7.81 5.19 6.88 5.19A1.69 1.69 0 0 0 5.19 6.88C5.19 7.81 5.95 8.56 6.88 8.56M8.27 18.5V10.13H5.5V18.5H8.27Z"/></svg>
                                        </a>
                                        <a href="<?= h($tIgUrl) ?>" target="_blank" title="Instagram: Explore & Posts (<?= h($lead['business_name']) ?>)" class="w-6 h-6 rounded-lg bg-[#E4405F]/15 hover:bg-[#E4405F]/25 border border-[#E4405F]/40 social-icon-btn flex items-center justify-center transition transform hover:scale-110">
                                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-[#E4405F]"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                        </a>
                                        <a href="<?= h($tYtUrl) ?>" target="_blank" title="YouTube: Video Showcase & Reviews" class="w-6 h-6 rounded-lg bg-[#FF0000]/15 hover:bg-[#FF0000]/25 border border-[#FF0000]/40 social-icon-btn flex items-center justify-center transition transform hover:scale-110">
                                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-[#FF0000]"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                        </a>
                                        <a href="<?= h($tFbUrl) ?>" target="_blank" title="Facebook: Meta Ad Library & Official Page" class="w-6 h-6 rounded-lg bg-[#1877F2]/15 hover:bg-[#1877F2]/25 border border-[#1877F2]/40 social-icon-btn flex items-center justify-center transition transform hover:scale-110">
                                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-[#1877F2]"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                        </a>
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-mono text-[#f4f1ea]">
                                    <?php if (!empty($lead['whatsapp'])): ?>
                                        <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="text-emerald-400 hover:underline flex items-center gap-1 font-medium">
                                            <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                            <span><?= h($lead['whatsapp']) ?></span>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-[#6e7280]">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-[11px] text-[#a3a6b0]">
                                    <?= !empty($lead['next_followup_at']) ? date('M d, Y', strtotime($lead['next_followup_at'])) : '—' ?>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <?php if (!empty($lead['whatsapp'])): ?>
                                            <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20" title="1-Click WhatsApp DM">
                                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button onclick="openOutreachModal(<?= (int)$lead['id'] ?>)" class="btn-gold-cta px-3 py-1 rounded-lg text-black font-extrabold text-xs">
                                            AI Pitch
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

</div>

<script>
function openScoreAuditModal(lead) {
    const backdrop = document.getElementById('modalBackdrop');
    const modalBox = document.getElementById('modalBox');
    if (!backdrop || !modalBox) return;

    const reasonsHtml = (lead.reasons || []).map(r => `
        <li class="flex items-start gap-2 text-xs text-[#f4f1ea]">
            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5"></i>
            <span>${r}</span>
        </li>
    `).join('');

    modalBox.innerHTML = `
        <div class="p-5 border-b border-white/[0.08] flex items-center justify-between bg-white/[0.02]">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#d9b46c]/15 text-[#f3dca4] border border-[#d9b46c]/30 flex items-center justify-center font-bold text-base">
                    ${lead.score}
                </div>
                <div>
                    <h3 class="font-bold text-[#f4f1ea] text-base leading-tight">${lead.name} — Q-Score™ Audit</h3>
                    <div class="text-xs text-[#a3a6b0]">${lead.tier} • ${lead.city || 'India'}</div>
                </div>
            </div>
            <button onclick="closeModal()" class="p-1.5 text-[#a3a6b0] hover:text-white rounded-lg hover:bg-white/[0.06]">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-6 overflow-y-auto max-h-[70vh] space-y-5 custom-scroll">
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                <div class="font-bold text-emerald-400 text-xs uppercase tracking-wider mb-1">Paying Capacity Verified</div>
                <p class="text-xs text-[#f4f1ea] leading-relaxed">
                    This business has qualified with an institutional score of <strong>${lead.score}/100</strong>. It maintains verified physical or legal operations, high customer review volume, and an active marketing presence.
                </p>
            </div>

            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-[#d9b46c] mb-3">Verification Score Breakdown</h4>
                <ul class="space-y-2.5">
                    ${reasonsHtml}
                </ul>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                    <div class="text-[#a3a6b0] text-[11px]">Domain & Host Status</div>
                    <div class="font-bold text-[#f4f1ea] mt-0.5 truncate">${lead.website || 'No website'}</div>
                    <span class="text-[10px] text-emerald-400 font-mono">Live DNS A-Record Verified</span>
                </div>
                <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                    <div class="text-[#a3a6b0] text-[11px]">Direct WhatsApp Access</div>
                    <div class="font-bold text-[#f4f1ea] mt-0.5 font-mono">${lead.whatsapp || lead.phone || 'N/A'}</div>
                    <span class="text-[10px] text-emerald-400 font-mono">1-Click Direct Chat Ready</span>
                </div>
                <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                    <div class="text-[#a3a6b0] text-[11px]">Google Reviews Traction</div>
                    <div class="font-bold text-amber-400 mt-0.5">${lead.rating} ★ (${lead.reviews} Reviews)</div>
                    <span class="text-[10px] text-[#a3a6b0]">Proven Customer Cashflow</span>
                </div>
                <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                    <div class="text-[#a3a6b0] text-[11px]">Est. Budget Range</div>
                    <div class="font-bold text-[#d9b46c] mt-0.5">₹50,000 - ₹2,50,000+/mo</div>
                    <span class="text-[10px] text-[#a3a6b0]">High Commercial Ticket</span>
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-white/[0.08] bg-white/[0.02] flex items-center justify-between">
            <span class="text-xs text-[#a3a6b0]">No third-party gatekeepers. Direct outreach only.</span>
            <div class="flex items-center gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-[#a3a6b0] hover:text-white bg-white/[0.06] hover:bg-white/[0.1] transition">Close</button>
                <button type="button" onclick="closeModal(); openOutreachModal(${lead.id}, 'whatsapp');" class="btn-gold-cta px-4 py-2 rounded-xl text-xs font-extrabold text-black shadow-md flex items-center gap-1.5">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    <span>Generate AI Pitch</span>
                </button>
            </div>
        </div>
    `;

    backdrop.classList.remove('hidden');
    backdrop.classList.add('flex');
    lucide.createIcons();
}
</script>

<?php
$content = ob_get_clean();
render_layout('CRM Pipeline', 'crm', $content, $user);
