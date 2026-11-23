<?php
// Outreach AI Freedom - Analytics & Conversion Tracking
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/layout.php';

$user = require_auth();
$userId = (int)$user['id'];

// Counts per stage
$stageCounts = [];
foreach (array_keys(PIPELINE_STAGES) as $k) {
    $stageCounts[$k] = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ? AND pipeline_stage = ?", [$userId, $k]);
}

$totalScraped = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ?", [$userId]);
$totalQualified = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ? AND is_qualified = 1", [$userId]);
$totalDispatched = (int)val("SELECT COUNT(*) FROM outreach_logs WHERE user_id = ?", [$userId]);
$totalWon = $stageCounts['closed_won'];
$wonRevenue = (float)val("SELECT COALESCE(SUM(deal_value), 0) FROM leads WHERE user_id = ? AND pipeline_stage = 'closed_won'", [$userId]);
$pipelineValue = (float)val("SELECT COALESCE(SUM(deal_value), 0) FROM leads WHERE user_id = ? AND pipeline_stage NOT IN ('closed_lost')", [$userId]);

// Qualification Tiers
$tierHigh = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ? AND qualification_score >= 85", [$userId]);
$tierMid = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ? AND qualification_score >= 70 AND qualification_score < 85", [$userId]);
$tierStarter = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ? AND qualification_score < 70", [$userId]);

// Channel stats
$channels = ['whatsapp', 'linkedin', 'email', 'instagram'];
$channelCounts = [];
foreach ($channels as $ch) {
    $channelCounts[$ch] = (int)val("SELECT COUNT(*) FROM outreach_logs WHERE user_id = ? AND channel = ?", [$userId, $ch]);
}

ob_start();
?>

<div class="space-y-8">
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                <span>Conversion Funnel & Outreach ROI Analytics</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-mono">Performance Engine</span>
            </h2>
            <p class="text-xs text-slate-400 mt-1">Real-time metrics tracking scraped prospects, qualification conversion, channel responses, and won revenue.</p>
        </div>
        <a href="/api.php?action=export_leads_csv" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700 flex items-center gap-2 transition">
            <i data-lucide="download" class="w-4 h-4 text-emerald-400"></i>
            <span>Export Analytics CSV</span>
        </a>
    </div>

    <!-- Revenue & Core KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card rounded-2xl p-5 border border-slate-800">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Closed Won Revenue</div>
            <div class="text-3xl font-extrabold text-emerald-400">₹<?= number_format($wonRevenue) ?></div>
            <div class="text-xs text-slate-400 mt-2"><?= $totalWon ?> deals converted successfully</div>
        </div>

        <div class="glass-card rounded-2xl p-5 border border-slate-800">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Active Pipeline Value</div>
            <div class="text-3xl font-extrabold text-white">₹<?= number_format($pipelineValue) ?></div>
            <div class="text-xs text-slate-400 mt-2">Prospective value in flight</div>
        </div>

        <div class="glass-card rounded-2xl p-5 border border-slate-800">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Total Pitches Dispatched</div>
            <div class="text-3xl font-extrabold text-indigo-400"><?= number_format($totalDispatched) ?></div>
            <div class="text-xs text-slate-400 mt-2">Across all 4 integrated channels</div>
        </div>

        <div class="glass-card rounded-2xl p-5 border border-slate-800">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Qualification Accuracy</div>
            <div class="text-3xl font-extrabold text-blue-400">
                <?= $totalScraped > 0 ? round(($totalQualified / $totalScraped) * 100) : 0 ?>%
            </div>
            <div class="text-xs text-slate-400 mt-2"><?= $totalQualified ?> / <?= $totalScraped ?> leads verified</div>
        </div>
    </div>

    <!-- Funnel Visualization -->
    <div class="glass-panel rounded-2xl p-6 border border-slate-800 space-y-4">
        <h3 class="font-bold text-white text-base">Pipeline Conversion Funnel</h3>
        
        <div class="space-y-3">
            
            <!-- Step 1: Scraped -->
            <div>
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="text-slate-300 font-semibold">1. Scraped & Enriched Prospects</span>
                    <span class="font-bold text-white"><?= $totalScraped ?></span>
                </div>
                <div class="w-full bg-slate-800 h-2.5 rounded-full overflow-hidden">
                    <div class="bg-slate-400 h-full rounded-full" style="width: 100%"></div>
                </div>
            </div>

            <!-- Step 2: Qualified -->
            <div>
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="text-blue-300 font-semibold">2. Qualified (High Paying Capacity Score ≥ 70)</span>
                    <span class="font-bold text-white"><?= $totalQualified ?> (<?= $totalScraped > 0 ? round(($totalQualified / $totalScraped) * 100) : 0 ?>%)</span>
                </div>
                <div class="w-full bg-slate-800 h-2.5 rounded-full overflow-hidden">
                    <div class="bg-blue-500 h-full rounded-full" style="width: <?= $totalScraped > 0 ? round(($totalQualified / $totalScraped) * 100) : 0 ?>%"></div>
                </div>
            </div>

            <!-- Step 3: Outreach Dispatched -->
            <?php 
                $activeOutreachTotal = $stageCounts['outreach_sent'] + $stageCounts['followup_1'] + $stageCounts['followup_2'] + $stageCounts['responded'] + $stageCounts['meeting_booked'] + $stageCounts['closed_won'];
                $outreachPct = $totalQualified > 0 ? round(($activeOutreachTotal / $totalQualified) * 100) : 0;
            ?>
            <div>
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="text-purple-300 font-semibold">3. Outreach Pitches Sent</span>
                    <span class="font-bold text-white"><?= $activeOutreachTotal ?> (<?= $outreachPct ?>%)</span>
                </div>
                <div class="w-full bg-slate-800 h-2.5 rounded-full overflow-hidden">
                    <div class="bg-purple-500 h-full rounded-full" style="width: <?= $outreachPct ?>%"></div>
                </div>
            </div>

            <!-- Step 4: Responded -->
            <?php 
                $engagedTotal = $stageCounts['responded'] + $stageCounts['meeting_booked'] + $stageCounts['closed_won'];
                $engagedPct = $activeOutreachTotal > 0 ? round(($engagedTotal / $activeOutreachTotal) * 100) : 0;
            ?>
            <div>
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="text-teal-300 font-semibold">4. Responded & Active Conversations</span>
                    <span class="font-bold text-white"><?= $engagedTotal ?> (<?= $engagedPct ?>% response rate)</span>
                </div>
                <div class="w-full bg-slate-800 h-2.5 rounded-full overflow-hidden">
                    <div class="bg-teal-500 h-full rounded-full" style="width: <?= $engagedPct ?>%"></div>
                </div>
            </div>

            <!-- Step 5: Closed Won -->
            <?php 
                $winPct = $activeOutreachTotal > 0 ? round(($totalWon / $activeOutreachTotal) * 100) : 0;
            ?>
            <div>
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="text-emerald-300 font-semibold">5. Deals Closed Won 🎉</span>
                    <span class="font-bold text-emerald-400"><?= $totalWon ?> (<?= $winPct ?>% win rate)</span>
                </div>
                <div class="w-full bg-slate-800 h-2.5 rounded-full overflow-hidden">
                    <div class="bg-emerald-400 h-full rounded-full" style="width: <?= max(5, $winPct) ?>%"></div>
                </div>
            </div>

        </div>
    </div>

    <!-- Side-by-Side: Channel Distribution & Paying Capacity Tiers -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Channel Dispatches -->
        <div class="glass-panel rounded-2xl p-6 border border-slate-800 space-y-4">
            <h3 class="font-bold text-white text-base">Touches Dispatched by Channel</h3>
            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                    <div class="flex items-center gap-2 text-slate-200">
                        <i data-lucide="message-circle" class="w-4 h-4 text-emerald-400"></i>
                        <span>WhatsApp Direct</span>
                    </div>
                    <span class="font-bold text-white font-mono"><?= $channelCounts['whatsapp'] ?> pitches</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                    <div class="flex items-center gap-2 text-slate-200">
                        <i data-lucide="linkedin" class="w-4 h-4 text-blue-400"></i>
                        <span>LinkedIn InMail / DM</span>
                    </div>
                    <span class="font-bold text-white font-mono"><?= $channelCounts['linkedin'] ?> pitches</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                    <div class="flex items-center gap-2 text-slate-200">
                        <i data-lucide="mail" class="w-4 h-4 text-indigo-400"></i>
                        <span>Email Outbound</span>
                    </div>
                    <span class="font-bold text-white font-mono"><?= $channelCounts['email'] ?> pitches</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                    <div class="flex items-center gap-2 text-slate-200">
                        <i data-lucide="instagram" class="w-4 h-4 text-pink-400"></i>
                        <span>Instagram DM</span>
                    </div>
                    <span class="font-bold text-white font-mono"><?= $channelCounts['instagram'] ?> pitches</span>
                </div>
            </div>
        </div>

        <!-- Paying Capacity Distribution -->
        <div class="glass-panel rounded-2xl p-6 border border-slate-800 space-y-4">
            <h3 class="font-bold text-white text-base">Paying Capacity Tier Distribution</h3>
            <div class="space-y-3 text-xs">
                <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-emerald-400 font-bold">⭐ High / Enterprise (Q-Score 85+)</span>
                        <span class="font-mono text-white"><?= $tierHigh ?> leads</span>
                    </div>
                    <p class="text-[11px] text-slate-400">Established multi-location brands, active social media, large review volumes.</p>
                </div>
                <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-blue-400 font-bold">⚡ Mid-Market / Premium (Q-Score 70-84)</span>
                        <span class="font-mono text-white"><?= $tierMid ?> leads</span>
                    </div>
                    <p class="text-[11px] text-slate-400">Solid verified commercial businesses with active website and direct WhatsApp.</p>
                </div>
                <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-slate-400 font-bold">🌱 Boutique / Starter (< 70)</span>
                        <span class="font-mono text-white"><?= $tierStarter ?> leads</span>
                    </div>
                    <p class="text-[11px] text-slate-400">Smaller operations or missing channels. Automatically filtered out by strict mode.</p>
                </div>
            </div>
        </div>

    </div>

</div>

<?php
$content = ob_get_clean();
render_layout('Analytics & Conversion ROI', 'analytics', $content, $user);
