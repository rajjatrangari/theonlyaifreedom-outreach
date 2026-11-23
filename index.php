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
    <div class="mb-8 p-5 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-indigo-500/10 to-teal-500/10 border border-emerald-500/30 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-emerald-400 flex-shrink-0">
                <i data-lucide="sparkles" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-bold text-white text-base">Complete Your Business Offering & Target ICP</h3>
                <p class="text-xs text-slate-300 mt-0.5">Define what you do, your unique selling proposition, and target niche so our AI can craft 100% bespoke high-converting outreach pitches.</p>
            </div>
        </div>
        <a href="/onboarding.php" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black font-bold text-xs shadow-lg shadow-emerald-500/20 transition flex-shrink-0 flex items-center gap-2">
            <span>Set Up My Offering</span>
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </a>
    </div>
<?php endif; ?>

<!-- Quick Scrape Discovery Hero Box -->
<div class="mb-8 glass-panel rounded-2xl p-6 border border-slate-800">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-4">
        <div>
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <span>Find Qualified High-Paying Leads Instantly</span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-medium">Q-Score™ Engine</span>
            </h2>
            <p class="text-xs text-slate-400 mt-1">Scrapes commercial registries & official websites. Surfaces only active businesses with verified WhatsApp, working websites, and high paying capacity.</p>
        </div>
        <a href="/scraper.php" class="text-xs text-emerald-400 hover:text-emerald-300 font-semibold flex items-center gap-1">
            <span>Advanced Search & Filters</span>
            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
        </a>
    </div>

    <form onsubmit="handleQuickScrape(event)" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
        <div class="sm:col-span-6 relative">
            <i data-lucide="search" class="w-4 h-4 text-slate-500 absolute left-3.5 top-3"></i>
            <input type="text" id="quickKeyword" required placeholder="Target Niche or Industry (e.g. Luxury Interior Designers, Dental Clinics, B2B SaaS)..." 
                   class="w-full bg-slate-900/90 border border-slate-700/80 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
        </div>
        <div class="sm:col-span-4 relative">
            <i data-lucide="map-pin" class="w-4 h-4 text-slate-500 absolute left-3.5 top-3"></i>
            <input type="text" id="quickLocation" placeholder="Target City or Region (e.g. Mumbai, Dubai, London, New York)..." 
                   class="w-full bg-slate-900/90 border border-slate-700/80 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
        </div>
        <div class="sm:col-span-2">
            <button type="submit" id="quickScrapeBtn" class="w-full h-full flex items-center justify-center gap-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-black font-bold text-xs py-2.5 px-4 rounded-xl shadow-lg shadow-emerald-500/20 transition">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
                <span>Scrape Now</span>
            </button>
        </div>
    </form>
    <div id="quickScrapeStatus" class="hidden mt-3 text-xs text-slate-300 flex items-center gap-2"></div>
</div>

<!-- Primary Key Performance Metrics Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    
    <div class="glass-card rounded-2xl p-5 border border-slate-800">
        <div class="flex items-center justify-between text-slate-400 mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Total Scraped</span>
            <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center">
                <i data-lucide="database" class="w-4 h-4"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-white"><?= number_format($totalLeads) ?></div>
        <div class="text-xs text-slate-400 mt-1 flex items-center gap-1.5">
            <span class="text-emerald-400 font-semibold"><?= number_format($qualifiedLeads) ?> Qualified</span>
            <span>(<?= $totalLeads > 0 ? round(($qualifiedLeads / $totalLeads) * 100) : 0 ?>% rate)</span>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-5 border border-slate-800">
        <div class="flex items-center justify-between text-slate-400 mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Active Outreach</span>
            <div class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center">
                <i data-lucide="send" class="w-4 h-4"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-white"><?= number_format($outreachSent) ?></div>
        <div class="text-xs text-slate-400 mt-1 flex items-center gap-1.5">
            <span class="text-purple-400 font-semibold"><?= $dueFollowups ?> Follow-ups Due</span>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-5 border border-slate-800">
        <div class="flex items-center justify-between text-slate-400 mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Closed Deals Won</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                <i data-lucide="trophy" class="w-4 h-4"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-white"><?= number_format($closedWon) ?></div>
        <div class="text-xs text-emerald-400 mt-1 font-semibold flex items-center gap-1">
            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
            <span>₹<?= number_format($wonRevenue) ?> Won Revenue</span>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-5 border border-slate-800">
        <div class="flex items-center justify-between text-slate-400 mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Pipeline Value</span>
            <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center">
                <i data-lucide="wallet" class="w-4 h-4"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-white">₹<?= number_format($totalPipelineValue) ?></div>
        <div class="text-xs text-slate-400 mt-1">Active prospective value</div>
    </div>
</div>

<!-- Main Split: Top Qualified Leads & Activity Timeline -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    
    <!-- Left Column: Top Qualified Leads Ready for Outreach (8 cols) -->
    <div class="lg:col-span-8 space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h3 class="font-bold text-white text-base">Top Qualified Prospects</h3>
                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/20 font-medium">Highest Paying Capacity</span>
            </div>
            <a href="/crm.php" class="text-xs text-slate-400 hover:text-white flex items-center gap-1 font-medium">
                <span>View Full CRM Pipeline</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <?php if (empty($recentLeads)): ?>
            <div class="glass-panel rounded-2xl p-10 text-center border border-slate-800">
                <div class="w-12 h-12 rounded-xl bg-slate-800/80 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="radar" class="w-6 h-6"></i>
                </div>
                <h4 class="font-semibold text-white text-sm mb-1">No Leads Scraped Yet</h4>
                <p class="text-xs text-slate-400 max-w-sm mx-auto mb-4">Run your first niche discovery scrape using the search bar above to pull verified high-budget leads.</p>
                <a href="/scraper.php" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 text-black font-semibold text-xs rounded-xl shadow-lg shadow-emerald-500/20">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                    <span>Launch Lead Discovery</span>
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($recentLeads as $lead): 
                    $stageCfg = PIPELINE_STAGES[$lead['pipeline_stage']] ?? PIPELINE_STAGES['new'];
                    $scoreColor = $lead['qualification_score'] >= 85 ? 'text-emerald-400 border-emerald-500/30 bg-emerald-500/10' : 'text-blue-400 border-blue-500/30 bg-blue-500/10';
                ?>
                    <div class="glass-card rounded-2xl p-4 border border-slate-800/80 flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="truncate">
                                    <h4 class="font-bold text-white text-sm truncate"><?= h($lead['business_name']) ?></h4>
                                    <div class="text-xs text-slate-400 truncate"><?= h($lead['category'] ?: 'Commercial') ?> • <?= h($lead['city'] ?: 'India') ?></div>
                                </div>
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full border flex-shrink-0 <?= $scoreColor ?>">
                                    Q <?= (int)$lead['qualification_score'] ?>
                                </span>
                            </div>

                            <!-- Verified Contact Badges -->
                            <div class="flex items-center gap-2 my-3 text-xs">
                                <?php if (!empty($lead['website'])): ?>
                                    <a href="<?= h($lead['website']) ?>" target="_blank" title="Official Website" class="p-1.5 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-blue-400 border border-slate-700/50">
                                        <i data-lucide="globe" class="w-3.5 h-3.5"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($lead['whatsapp'])): ?>
                                    <span title="Verified WhatsApp: <?= h($lead['whatsapp']) ?>" class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($lead['linkedin_url'])): ?>
                                    <a href="<?= h($lead['linkedin_url']) ?>" target="_blank" title="LinkedIn Profile" class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                        <i data-lucide="linkedin" class="w-3.5 h-3.5"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($lead['instagram_url'])): ?>
                                    <a href="<?= h($lead['instagram_url']) ?>" target="_blank" title="Instagram Profile" class="p-1.5 rounded-lg bg-pink-500/10 text-pink-400 border border-pink-500/20">
                                        <i data-lucide="instagram" class="w-3.5 h-3.5"></i>
                                    </a>
                                <?php endif; ?>

                                <span class="ml-auto text-[11px] px-2 py-0.5 rounded-full border <?= $stageCfg['bg'] ?>">
                                    <?= h($stageCfg['name']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Direct Reach Out CTA -->
                        <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-300">
                                ₹<?= number_format((float)$lead['deal_value']) ?>
                            </span>
                            <button onclick="openOutreachModal(<?= (int)$lead['id'] ?>, 'whatsapp')" class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-black font-bold text-xs shadow-md shadow-emerald-500/15 flex items-center gap-1.5">
                                <i data-lucide="send" class="w-3 h-3"></i>
                                <span>Reach Out</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Recent Outreach Logs & System Integrations (4 cols) -->
    <div class="lg:col-span-4 space-y-6">
        
        <!-- Outreach Log Activity Feed -->
        <div class="glass-panel rounded-2xl p-5 border border-slate-800">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-white text-sm">Recent Outreach Log</h3>
                <i data-lucide="activity" class="w-4 h-4 text-slate-400"></i>
            </div>

            <?php if (empty($recentLogs)): ?>
                <div class="text-xs text-slate-400 py-4 text-center">
                    No outreach dispatched yet. Open a qualified prospect to send a tailored message!
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-white truncate max-w-[140px]"><?= h($log['business_name']) ?></span>
                                <span class="text-[10px] text-slate-400"><?= date('M d, H:i', strtotime($log['created_at'])) ?></span>
                            </div>
                            <div class="flex items-center gap-1.5 text-slate-300">
                                <span class="capitalize px-1.5 py-0.2 rounded bg-slate-800 text-[10px] font-mono text-emerald-400"><?= h($log['channel']) ?></span>
                                <span class="text-[11px] text-slate-400 truncate"><?= h(clip($log['message_sent'], 45)) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Connected Communication Bridge Status -->
        <div class="glass-panel rounded-2xl p-5 border border-slate-800">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-white text-sm">Outreach Channels</h3>
                <a href="/channels.php" class="text-xs text-emerald-400 hover:underline">Manage</a>
            </div>
            <div class="space-y-2 text-xs">
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-900/50 border border-slate-800">
                    <div class="flex items-center gap-2">
                        <i data-lucide="message-circle" class="w-4 h-4 text-emerald-400"></i>
                        <span class="text-slate-200 font-medium">WhatsApp Bridge</span>
                    </div>
                    <span class="text-[10px] text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20 font-semibold">Ready</span>
                </div>
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-900/50 border border-slate-800">
                    <div class="flex items-center gap-2">
                        <i data-lucide="linkedin" class="w-4 h-4 text-blue-400"></i>
                        <span class="text-slate-200 font-medium">LinkedIn Direct</span>
                    </div>
                    <span class="text-[10px] text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20 font-semibold">Ready</span>
                </div>
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-900/50 border border-slate-800">
                    <div class="flex items-center gap-2">
                        <i data-lucide="mail" class="w-4 h-4 text-indigo-400"></i>
                        <span class="text-slate-200 font-medium">Email Dispatcher</span>
                    </div>
                    <span class="text-[10px] text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20 font-semibold">Ready</span>
                </div>
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-900/50 border border-slate-800">
                    <div class="flex items-center gap-2">
                        <i data-lucide="instagram" class="w-4 h-4 text-pink-400"></i>
                        <span class="text-slate-200 font-medium">Instagram DM</span>
                    </div>
                    <span class="text-[10px] text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20 font-semibold">Ready</span>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
async function handleQuickScrape(e) {
    e.preventDefault();
    const btn = document.getElementById('quickScrapeBtn');
    const status = document.getElementById('quickScrapeStatus');
    const keyword = document.getElementById('quickKeyword').value;
    const locationVal = document.getElementById('quickLocation').value;

    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin w-4 h-4 border-2 border-black border-t-transparent rounded-full"></span><span>Scraping...</span>`;
    status.classList.remove('hidden');
    status.innerHTML = `<span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Querying commercial directories & validating active contacts...`;

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
