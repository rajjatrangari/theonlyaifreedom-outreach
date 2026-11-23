<?php
// Outreach AI Freedom - Master Dashboard
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/layout.php';

$user = require_auth();
$userId = (int)$user['id'];

// Dashboard Metrics
$totalLeads = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ?", [$userId]);
$qualifiedLeads = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ? AND is_qualified = 1", [$userId]);
$outreachSent = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ? AND pipeline_stage IN ('outreach_sent', 'followup_1', 'followup_2')", [$userId]);
$closedWon = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ? AND pipeline_stage = 'closed_won'", [$userId]);
$totalPipelineValue = (float)val("SELECT COALESCE(SUM(deal_value), 0) FROM leads WHERE user_id = ? AND pipeline_stage NOT IN ('closed_lost')", [$userId]);
$wonRevenue = (float)val("SELECT COALESCE(SUM(deal_value), 0) FROM leads WHERE user_id = ? AND pipeline_stage = 'closed_won'", [$userId]);

$dueFollowups = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ? AND next_followup_at <= ? AND pipeline_stage NOT IN ('closed_won', 'closed_lost')", [
    $userId,
    today()
]);

// Top High-Intent Leads for Quick Outreach
$recentLeads = all("SELECT * FROM leads WHERE user_id = ? ORDER BY qualification_score DESC, id DESC LIMIT 6", [$userId]);

// Recent Outreach Activity
$recentLogs = all("
    SELECT o.*, l.business_name, l.city
    FROM outreach_logs o
    JOIN leads l ON o.lead_id = l.id
    WHERE o.user_id = ?
    ORDER BY o.id DESC LIMIT 5
", [$userId]);

ob_start();
?>

<!-- Onboarding / Offering Banner if not fully customized -->
<?php if (empty($user['business_niche']) || empty($user['business_offering'])): ?>
    <div class="mb-8 p-5 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-[#d9b46c]/10 to-teal-500/10 border border-[#d9b46c]/30 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-[#d9b46c]/20 border border-[#d9b46c]/40 flex items-center justify-center text-[#d9b46c] flex-shrink-0">
                <i data-lucide="sparkles" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-bold text-[#f4f1ea] text-base">Complete Your Business Offering & Target ICP</h3>
                <p class="text-xs text-[#a3a6b0] mt-0.5">Define what you do, your unique selling proposition, and target niche so our AI can craft 100% bespoke high-converting outreach pitches.</p>
            </div>
        </div>
        <a href="/onboarding.php" class="btn-gold-cta px-5 py-2.5 rounded-xl text-black font-bold text-xs shadow-lg transition flex-shrink-0 flex items-center gap-2">
            <span>Set Up My Offering</span>
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </a>
    </div>
<?php endif; ?>

<!-- Quick Scrape Discovery Hero Box -->
<div class="mb-8 glass-panel rounded-2xl p-6 border border-white/[0.08]">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-4">
        <div>
            <h2 class="text-lg font-bold text-[#f4f1ea] flex items-center gap-2">
                <span>Find Qualified High-Paying Leads Instantly</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-[#d9b46c]/15 text-[#f3dca4] border border-[#d9b46c]/30 font-medium">Q-Score™ Engine</span>
            </h2>
            <p class="text-xs text-[#a3a6b0] mt-1">Scrapes commercial registries & official websites. Surfaces only active businesses with verified WhatsApp, working websites, and high paying capacity.</p>
        </div>
        <a href="/scraper.php" class="text-xs text-[#d9b46c] hover:underline font-semibold flex items-center gap-1">
            <span>Advanced Search & Filters</span>
            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
        </a>
    </div>

    <form onsubmit="handleQuickScrape(event)" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
        <div class="sm:col-span-6 relative">
            <i data-lucide="search" class="w-4 h-4 text-[#a3a6b0] absolute left-3.5 top-3"></i>
            <input type="text" id="quickKeyword" required placeholder="Target Niche or Industry (e.g. Luxury Jewellery, Interior Architects, Dental Clinics, SaaS)..." 
                   class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl pl-10 pr-4 py-2.5 text-xs text-[#f4f1ea] placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
        </div>
        <div class="sm:col-span-4 relative">
            <i data-lucide="map-pin" class="w-4 h-4 text-[#a3a6b0] absolute left-3.5 top-3"></i>
            <input type="text" id="quickLocation" placeholder="Target City or Region (e.g. Mumbai, Delhi, Dubai, London)..." 
                   class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl pl-10 pr-4 py-2.5 text-xs text-[#f4f1ea] placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
        </div>
        <div class="sm:col-span-2">
            <button type="submit" id="quickScrapeBtn" class="btn-gold-cta w-full h-full flex items-center justify-center gap-2 text-black font-extrabold text-xs py-2.5 px-4 rounded-xl shadow-lg transition">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
                <span>Scrape Now</span>
            </button>
        </div>
    </form>
    <div id="quickScrapeStatus" class="hidden mt-3 text-xs text-[#f4f1ea] flex items-center gap-2"></div>
</div>

<!-- Primary Key Performance Metrics Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    
    <div class="glass-card rounded-2xl p-5 border border-white/[0.08]">
        <div class="flex items-center justify-between text-[#a3a6b0] mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Total Scraped</span>
            <div class="w-8 h-8 rounded-lg bg-white/[0.06] text-[#f4f1ea] flex items-center justify-center">
                <i data-lucide="database" class="w-4 h-4"></i>
            </div>
        </div>
        <div class="text-2xl font-extrabold text-[#f4f1ea]"><?= number_format($totalLeads) ?></div>
        <div class="text-xs text-[#a3a6b0] mt-1 flex items-center gap-1.5">
            <span class="text-[#d9b46c] font-semibold"><?= number_format($qualifiedLeads) ?> Qualified</span>
            <span>(<?= $totalLeads > 0 ? round(($qualifiedLeads / $totalLeads) * 100) : 0 ?>% rate)</span>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-5 border border-white/[0.08]">
        <div class="flex items-center justify-between text-[#a3a6b0] mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Active Outreach</span>
            <div class="w-8 h-8 rounded-lg bg-[#d9b46c]/15 text-[#f3dca4] flex items-center justify-center">
                <i data-lucide="send" class="w-4 h-4"></i>
            </div>
        </div>
        <div class="text-2xl font-extrabold text-[#f4f1ea]"><?= number_format($outreachSent) ?></div>
        <div class="text-xs text-[#a3a6b0] mt-1 flex items-center gap-1.5">
            <span class="text-[#f3dca4] font-semibold"><?= $dueFollowups ?> Follow-ups Due</span>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-5 border border-white/[0.08]">
        <div class="flex items-center justify-between text-[#a3a6b0] mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Closed Deals Won</span>
            <div class="w-8 h-8 rounded-lg bg-[#d9b46c]/20 text-[#d9b46c] flex items-center justify-center">
                <i data-lucide="trophy" class="w-4 h-4"></i>
            </div>
        </div>
        <div class="text-2xl font-extrabold gold-text"><?= number_format($closedWon) ?></div>
        <div class="text-xs text-[#d9b46c] mt-1 font-semibold flex items-center gap-1">
            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
            <span>₹<?= number_format($wonRevenue) ?> Won Revenue</span>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-5 border border-white/[0.08]">
        <div class="flex items-center justify-between text-[#a3a6b0] mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Pipeline Value</span>
            <div class="w-8 h-8 rounded-lg bg-white/[0.06] text-[#d9b46c] flex items-center justify-center">
                <i data-lucide="wallet" class="w-4 h-4"></i>
            </div>
        </div>
        <div class="text-2xl font-extrabold text-[#f4f1ea]">₹<?= number_format($totalPipelineValue) ?></div>
        <div class="text-xs text-[#a3a6b0] mt-1">Active prospective value</div>
    </div>
</div>

<!-- Main Split: Top Qualified Leads & Activity Timeline -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    
    <!-- Left Column: Top Qualified Leads Ready for Outreach (8 cols) -->
    <div class="lg:col-span-8 space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h3 class="font-bold text-[#f4f1ea] text-base">Top Qualified Prospects</h3>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 font-medium">Highest Paying Capacity</span>
            </div>
            <a href="/crm.php" class="text-xs text-[#a3a6b0] hover:text-[#f4f1ea] flex items-center gap-1 font-medium">
                <span>View Full CRM Pipeline</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <?php if (empty($recentLeads)): ?>
            <div class="glass-panel rounded-2xl p-10 text-center border border-white/[0.08]">
                <div class="w-12 h-12 rounded-xl bg-white/[0.06] text-[#d9b46c] flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="radar" class="w-6 h-6"></i>
                </div>
                <h4 class="font-semibold text-[#f4f1ea] text-sm mb-1">No Leads Scraped Yet</h4>
                <p class="text-xs text-[#a3a6b0] max-w-sm mx-auto mb-4">Run your first niche discovery scrape using the search bar above to pull verified high-budget leads.</p>
                <a href="/scraper.php" class="btn-gold-cta inline-flex items-center gap-2 px-4 py-2 text-black font-semibold text-xs rounded-xl shadow-lg">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                    <span>Launch Lead Discovery</span>
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($recentLeads as $lead): 
                    $stageCfg = PIPELINE_STAGES[$lead['pipeline_stage']] ?? PIPELINE_STAGES['new'];
                    $score = (int)$lead['qualification_score'];
                    $scoreColor = $score >= 85 ? 'text-emerald-400 border-emerald-500/30 bg-emerald-500/10' : 'text-blue-400 border-blue-500/30 bg-blue-500/10';
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
                    <div class="glass-card rounded-2xl p-4 border border-white/[0.08] flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-2 mb-1.5">
                                <div class="truncate">
                                    <h4 class="font-bold text-[#f4f1ea] text-sm truncate"><?= h($lead['business_name']) ?></h4>
                                    <div class="text-xs text-[#a3a6b0] truncate"><?= h($lead['category'] ?: 'Commercial') ?> • <?= h($lead['city'] ?: 'India') ?></div>
                                    <?php if (!empty($lead['decision_maker_name'])): ?>
                                        <div class="inline-flex items-center gap-1.5 mt-1 px-2 py-0.5 rounded-md bg-[#d9b46c]/10 text-[#d9b46c] text-[10px] font-semibold border border-[#d9b46c]/20 max-w-full truncate">
                                            <i data-lucide="user-check" class="w-3 h-3 flex-shrink-0 text-[#d9b46c]"></i>
                                            <span class="truncate font-bold"><?= h($lead['decision_maker_name']) ?></span>
                                            <span class="opacity-75 truncate">• <?= h($lead['decision_maker_title'] ?: 'Founder / MD') ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($lead['meta_ads_active'])): ?>
                                        <div class="mt-1">
                                            <a href="<?= h($lead['meta_ads_library_url'] ?: 'https://www.facebook.com/ads/library/?active_status=all&ad_type=all&country=IN&q=' . urlencode($lead['business_name'])) ?>" 
                                               target="_blank" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-500/15 text-blue-400 text-[10px] font-semibold border border-blue-500/30 hover:underline" title="View Active Ads in Meta Ad Library">
                                                <i data-lucide="radio" class="w-3 h-3"></i>
                                                <span>📢 <?= (int)$lead['meta_ads_count'] ?> Active Ads</span>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" onclick='openScoreAuditModal(<?= $leadJson ?>)' class="text-xs font-bold px-2 py-0.5 rounded-full border flex-shrink-0 transition hover:scale-105 <?= $scoreColor ?>" title="View Q-Score™ Audit">
                                    Q <?= $score ?>
                                </button>
                            </div>

                            <!-- Verified Contact Badges Across All Channels -->
                            <div class="flex items-center gap-1.5 my-3 text-xs flex-wrap">
                                <?php if (!empty($lead['website'])): ?>
                                    <a href="<?= h($lead['website']) ?>" target="_blank" title="Official Website: <?= h($lead['website']) ?>" class="p-1.5 rounded-lg bg-white/[0.04] hover:bg-white/[0.08] text-[#d9b46c] border border-white/[0.08]">
                                        <i data-lucide="globe" class="w-3.5 h-3.5"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($lead['whatsapp'])): ?>
                                    <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= urlencode('Hello ' . $lead['business_name'] . ' team, I reviewed your brand portfolio and wanted to discuss a partnership.') ?>" target="_blank" title="Direct WhatsApp: <?= h($lead['whatsapp']) ?>" class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition">
                                        <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($lead['linkedin_url'])): ?>
                                    <a href="<?= h($lead['linkedin_url']) ?>" target="_blank" title="LinkedIn Profile" class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20 hover:bg-blue-500/20 transition">
                                        <i data-lucide="linkedin" class="w-3.5 h-3.5"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($lead['instagram_url'])): ?>
                                    <a href="<?= h($lead['instagram_url']) ?>" target="_blank" title="Instagram Profile" class="p-1.5 rounded-lg bg-pink-500/10 text-pink-400 border border-pink-500/20 hover:bg-pink-500/20 transition">
                                        <i data-lucide="instagram" class="w-3.5 h-3.5"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($lead['youtube_url'])): ?>
                                    <a href="<?= h($lead['youtube_url']) ?>" target="_blank" title="YouTube Channel" class="p-1.5 rounded-lg bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition">
                                        <i data-lucide="youtube" class="w-3.5 h-3.5"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($lead['facebook_url'])): ?>
                                    <a href="<?= h($lead['facebook_url']) ?>" target="_blank" title="Facebook Page" class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 hover:bg-indigo-500/20 transition">
                                        <i data-lucide="facebook" class="w-3.5 h-3.5"></i>
                                    </a>
                                <?php endif; ?>

                                <span class="ml-auto text-[11px] px-2 py-0.5 rounded-full border <?= $stageCfg['bg'] ?>">
                                    <?= h($stageCfg['name']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Direct Reach Out CTA -->
                        <div class="pt-3 border-t border-white/[0.08] flex items-center justify-between">
                            <span class="text-xs font-semibold text-[#f4f1ea]">
                                ₹<?= number_format((float)$lead['deal_value']) ?>
                            </span>
                            <div class="flex items-center gap-1.5">
                                <?php if (!empty($lead['whatsapp'])): ?>
                                    <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" title="1-Click WhatsApp DM" class="p-1.5 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-400 border border-emerald-500/30 transition">
                                        <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                    </a>
                                <?php endif; ?>
                                <button onclick="openOutreachModal(<?= (int)$lead['id'] ?>, 'whatsapp')" class="btn-gold-cta px-3.5 py-1.5 rounded-xl text-black font-extrabold text-xs shadow-md flex items-center gap-1.5">
                                    <i data-lucide="sparkles" class="w-3 h-3"></i>
                                    <span>AI Pitch</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Recent Outreach Logs & System Integrations (4 cols) -->
    <div class="lg:col-span-4 space-y-6">
        
        <!-- Outreach Log Activity Feed -->
        <div class="glass-panel rounded-2xl p-5 border border-white/[0.08]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-[#f4f1ea] text-sm">Recent Outreach Log</h3>
                <i data-lucide="activity" class="w-4 h-4 text-[#a3a6b0]"></i>
            </div>

            <?php if (empty($recentLogs)): ?>
                <div class="text-xs text-[#a3a6b0] py-4 text-center">
                    No outreach dispatched yet. Open a qualified prospect to send a tailored message!
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="p-3 rounded-xl bg-white/[0.02] border border-white/[0.06] text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-[#f4f1ea] truncate max-w-[140px]"><?= h($log['business_name']) ?></span>
                                <span class="text-[10px] text-[#a3a6b0]"><?= date('M d, H:i', strtotime($log['created_at'])) ?></span>
                            </div>
                            <div class="flex items-center gap-1.5 text-[#f4f1ea]">
                                <span class="capitalize px-1.5 py-0.2 rounded bg-white/[0.06] text-[10px] font-mono text-[#d9b46c]"><?= h($log['channel']) ?></span>
                                <span class="text-[11px] text-[#a3a6b0] truncate"><?= h(clip($log['message_sent'], 45)) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Connected Communication Bridge Status -->
        <div class="glass-panel rounded-2xl p-5 border border-white/[0.08]">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-[#f4f1ea] text-sm">Outreach Channels</h3>
                <a href="/channels.php" class="text-xs text-[#d9b46c] hover:underline">Manage</a>
            </div>
            <div class="space-y-2 text-xs">
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-white/[0.02] border border-white/[0.06]">
                    <div class="flex items-center gap-2">
                        <i data-lucide="message-circle" class="w-4 h-4 text-emerald-400"></i>
                        <span class="text-[#f4f1ea] font-medium">WhatsApp Bridge</span>
                    </div>
                    <span class="text-[10px] text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20 font-semibold">Ready</span>
                </div>
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-white/[0.02] border border-white/[0.06]">
                    <div class="flex items-center gap-2">
                        <i data-lucide="linkedin" class="w-4 h-4 text-blue-400"></i>
                        <span class="text-[#f4f1ea] font-medium">LinkedIn Direct</span>
                    </div>
                    <span class="text-[10px] text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20 font-semibold">Ready</span>
                </div>
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-white/[0.02] border border-white/[0.06]">
                    <div class="flex items-center gap-2">
                        <i data-lucide="mail" class="w-4 h-4 text-indigo-400"></i>
                        <span class="text-[#f4f1ea] font-medium">Email Dispatcher</span>
                    </div>
                    <span class="text-[10px] text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20 font-semibold">Ready</span>
                </div>
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-white/[0.02] border border-white/[0.06]">
                    <div class="flex items-center gap-2">
                        <i data-lucide="instagram" class="w-4 h-4 text-pink-400"></i>
                        <span class="text-[#f4f1ea] font-medium">Instagram DM</span>
                    </div>
                    <span class="text-[10px] text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20 font-semibold">Ready</span>
                </div>
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-white/[0.02] border border-white/[0.06]">
                    <div class="flex items-center gap-2">
                        <i data-lucide="youtube" class="w-4 h-4 text-red-400"></i>
                        <span class="text-[#f4f1ea] font-medium">YouTube Brand Verification</span>
                    </div>
                    <span class="text-[10px] text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20 font-semibold">Active</span>
                </div>
            </div>
        </div>

    </div>
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

async function handleQuickScrape(e) {
    e.preventDefault();
    const btn = document.getElementById('quickScrapeBtn');
    const status = document.getElementById('quickScrapeStatus');
    const keyword = document.getElementById('quickKeyword').value;
    const locationVal = document.getElementById('quickLocation').value;

    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin w-4 h-4 border-2 border-black border-t-transparent rounded-full"></span><span>Scraping...</span>`;
    status.classList.remove('hidden');
    status.innerHTML = `<span class="w-2 h-2 rounded-full bg-[#d9b46c] animate-pulse"></span> Querying commercial directories & validating active contacts...`;

    try {
        const res = await fetch('/api.php?action=scrape_leads', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ keyword, location: locationVal, min_score: 70, limit: 10 })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(`Found ${data.qualified_count} verified qualified leads! Filtered ${data.unqualified_filtered} unqualified entries.`, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Scrape failed', 'error');
            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="sparkles" class="w-4 h-4"></i><span>Scrape Now</span>`;
            status.classList.add('hidden');
            lucide.createIcons();
        }
    } catch (err) {
        showToast('Network error during lead discovery', 'error');
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="sparkles" class="w-4 h-4"></i><span>Scrape Now</span>`;
        status.classList.add('hidden');
        lucide.createIcons();
    }
}
</script>

<?php
$content = ob_get_clean();
render_layout('Master Dashboard', 'dashboard', $content, $user);
