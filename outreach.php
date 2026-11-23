<?php
// Outreach AI Freedom - AI Outreach Sequencer & Follow-up Tracker
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/layout.php';

$user = require_auth();
$userId = (int)$user['id'];

$tab = $_GET['tab'] ?? 'pending';

// Query leads based on active outreach state
if ($tab === 'followups') {
    $leads = all("SELECT * FROM leads WHERE user_id = ? AND pipeline_stage IN ('outreach_sent', 'followup_1') ORDER BY next_followup_at ASC, id DESC", [$userId]);
} elseif ($tab === 'active') {
    $leads = all("SELECT * FROM leads WHERE user_id = ? AND pipeline_stage IN ('responded', 'meeting_booked') ORDER BY id DESC", [$userId]);
} elseif ($tab === 'history') {
    $leads = all("SELECT * FROM leads WHERE user_id = ? AND pipeline_stage IN ('closed_won', 'closed_lost') ORDER BY id DESC", [$userId]);
} else {
    // Pending initial outreach
    $leads = all("SELECT * FROM leads WHERE user_id = ? AND pipeline_stage IN ('new', 'qualified', 'cold', 'warm', 'hot') ORDER BY qualification_score DESC, id DESC", [$userId]);
}

$pendingCount = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ? AND pipeline_stage IN ('new', 'qualified', 'cold', 'warm', 'hot')", [$userId]);
$followupCount = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ? AND pipeline_stage IN ('outreach_sent', 'followup_1')", [$userId]);
$activeCount = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ? AND pipeline_stage IN ('responded', 'meeting_booked')", [$userId]);

ob_start();
?>

<div class="space-y-6">
    
    <!-- Top Outreach Header & Sequence Overview -->
    <div class="glass-panel rounded-2xl p-6 border border-slate-800">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                    <span>Personalized AI Outreach & Multi-Touch Sequencer</span>
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-mono">100% Unrestricted</span>
                </h2>
                <p class="text-xs text-slate-400 mt-1">
                    Every message is custom-tailored based on the prospect's verified footprint + your core service offering. Dispatches directly via WhatsApp, LinkedIn, or Email without third-party platform bans.
                </p>
            </div>
            <a href="/onboarding.php" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl border border-slate-700/80 transition flex items-center gap-2">
                <i data-lucide="sliders" class="w-4 h-4 text-emerald-400"></i>
                <span>Adjust Offer / Pitch</span>
            </a>
        </div>

        <!-- Touchpoint Framework Badges -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mt-6 pt-6 border-t border-slate-800/80">
            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 text-xs">
                <div class="font-bold text-emerald-400 flex items-center gap-1.5 mb-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Touch 1: Initial Hook</span>
                </div>
                <div class="text-[11px] text-slate-400 leading-relaxed">Icebreaker, observation of positive reviews, sharp pain point, low-friction value video CTA.</div>
            </div>

            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 text-xs">
                <div class="font-bold text-indigo-400 flex items-center gap-1.5 mb-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                    <span>Touch 2: Value Proof (Day 2)</span>
                </div>
                <div class="text-[11px] text-slate-400 leading-relaxed">Case study results from similar niche brand, social proof, concise question.</div>
            </div>

            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 text-xs">
                <div class="font-bold text-violet-400 flex items-center gap-1.5 mb-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-violet-400"></span>
                    <span>Touch 3: Quick Bump (Day 5)</span>
                </div>
                <div class="text-[11px] text-slate-400 leading-relaxed">Friendly nudge ensuring message was not buried, ask for key growth point-of-contact.</div>
            </div>

            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 text-xs">
                <div class="font-bold text-rose-400 flex items-center gap-1.5 mb-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                    <span>Touch 4: Closure / Breakup (Day 8)</span>
                </div>
                <div class="text-[11px] text-slate-400 leading-relaxed">Permission to close the file, wishing team success, leaves door open for future outreach.</div>
            </div>
        </div>
    </div>

    <!-- Tab Filter Navigation -->
    <div class="flex items-center gap-2 border-b border-slate-800 pb-1">
        <a href="/outreach.php?tab=pending" class="px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2 <?= $tab === 'pending' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'text-slate-400 hover:text-white' ?>">
            <span>Ready for Initial Pitch</span>
            <span class="px-1.5 py-0.2 rounded-full bg-slate-800 text-[10px]"><?= $pendingCount ?></span>
        </a>
        <a href="/outreach.php?tab=followups" class="px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2 <?= $tab === 'followups' ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/40' : 'text-slate-400 hover:text-white' ?>">
            <span>Follow-up Cadence</span>
            <span class="px-1.5 py-0.2 rounded-full bg-slate-800 text-[10px]"><?= $followupCount ?></span>
        </a>
        <a href="/outreach.php?tab=active" class="px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2 <?= $tab === 'active' ? 'bg-teal-500/20 text-teal-300 border border-teal-500/40' : 'text-slate-400 hover:text-white' ?>">
            <span>Responded / In Chat</span>
            <span class="px-1.5 py-0.2 rounded-full bg-slate-800 text-[10px]"><?= $activeCount ?></span>
        </a>
        <a href="/outreach.php?tab=history" class="px-4 py-2 rounded-xl text-xs font-semibold transition <?= $tab === 'history' ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white' ?>">
            <span>Closed History</span>
        </a>
    </div>

    <!-- Prospects List for Targeted Outreach -->
    <?php if (empty($leads)): ?>
        <div class="glass-panel rounded-2xl p-12 text-center border border-slate-800">
            <div class="w-12 h-12 rounded-xl bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="check" class="w-6 h-6 text-emerald-400"></i>
            </div>
            <h4 class="font-semibold text-white text-sm mb-1">Queue is clear!</h4>
            <p class="text-xs text-slate-400 max-w-sm mx-auto mb-4">No prospects currently pending this touchpoint. Scrape new qualified leads or check other tabs.</p>
            <a href="/scraper.php" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 text-black font-semibold text-xs rounded-xl shadow-lg shadow-emerald-500/20">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
                <span>Discover New Leads</span>
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($leads as $lead): 
                $isDue = !empty($lead['next_followup_at']) && $lead['next_followup_at'] <= today() && !in_array($lead['pipeline_stage'], ['closed_won', 'closed_lost']);
            ?>
                <div class="glass-card rounded-2xl p-5 border border-slate-800/80 flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div class="truncate">
                                <h4 class="font-bold text-white text-sm truncate"><?= h($lead['business_name']) ?></h4>
                                <div class="text-xs text-slate-400 truncate"><?= h($lead['category']) ?> • <?= h($lead['city']) ?></div>
                            </div>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">
                                Q <?= (int)$lead['qualification_score'] ?>
                            </span>
                        </div>

                        <!-- Contact Badges -->
                        <div class="flex items-center gap-2 my-3 text-xs">
                            <?php if (!empty($lead['whatsapp'])): ?>
                                <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20" title="WhatsApp: <?= h($lead['whatsapp']) ?>">
                                    <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($lead['linkedin_url'])): ?>
                                <a href="<?= h($lead['linkedin_url']) ?>" target="_blank" class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20" title="LinkedIn Profile">
                                    <i data-lucide="linkedin" class="w-3.5 h-3.5"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($lead['website'])): ?>
                                <a href="<?= h($lead['website']) ?>" target="_blank" class="p-1.5 rounded-lg bg-slate-800 text-blue-400 border border-slate-700" title="Website">
                                    <i data-lucide="globe" class="w-3.5 h-3.5"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($isDue): ?>
                                <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/40 animate-pulse">
                                    Follow-up Due!
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Current Stage & Value -->
                        <div class="p-2.5 rounded-xl bg-slate-900/60 border border-slate-800 text-xs flex items-center justify-between">
                            <span class="text-slate-400">Deal Value: <strong class="text-slate-200">₹<?= number_format((float)$lead['deal_value']) ?></strong></span>
                            <span class="font-mono text-[11px] text-emerald-400 capitalize"><?= str_replace('_', ' ', $lead['pipeline_stage']) ?></span>
                        </div>
                    </div>

                    <!-- Dispatch Button -->
                    <button onclick="openOutreachModal(<?= (int)$lead['id'] ?>, 'whatsapp')" class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-black font-bold text-xs shadow-lg shadow-emerald-500/20 transition flex items-center justify-center gap-2">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>Generate & Dispatch Pitch</span>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
render_layout('AI Outreach Hub', 'outreach', $content, $user);
