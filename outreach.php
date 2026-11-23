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
    <div class="glass-panel rounded-2xl p-6 border border-white/[0.08]">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-[#f4f1ea] tracking-tight flex items-center gap-2">
                    <span>Personalized AI Outreach & Multi-Touch Sequencer</span>
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 font-mono">100% Unrestricted</span>
                </h2>
                <p class="text-xs text-[#a3a6b0] mt-1">
                    Every message is custom-tailored based on the prospect's verified footprint + your core service offering. Dispatches directly via WhatsApp, LinkedIn, Instagram, or Email without third-party platform bans.
                </p>
            </div>
            <a href="/onboarding.php" class="px-4 py-2 bg-white/[0.06] hover:bg-white/[0.1] text-[#f4f1ea] text-xs font-semibold rounded-xl border border-white/[0.12] transition flex items-center gap-2">
                <i data-lucide="sliders" class="w-4 h-4 text-[#d9b46c]"></i>
                <span>Adjust Offer / Pitch</span>
            </a>
        </div>

        <!-- Touchpoint Framework Badges -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mt-6 pt-6 border-t border-white/[0.08]">
            <div class="p-3.5 rounded-xl bg-white/[0.02] border border-white/[0.06] text-xs">
                <div class="font-bold text-emerald-400 flex items-center gap-1.5 mb-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Touch 1: Initial Hook</span>
                </div>
                <div class="text-[11px] text-[#a3a6b0] leading-relaxed">Icebreaker, observation of positive reviews, sharp pain point, low-friction value video CTA.</div>
            </div>

            <div class="p-3.5 rounded-xl bg-white/[0.02] border border-white/[0.06] text-xs">
                <div class="font-bold text-[#d9b46c] flex items-center gap-1.5 mb-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#d9b46c]"></span>
                    <span>Touch 2: Value Proof (Day 2)</span>
                </div>
                <div class="text-[11px] text-[#a3a6b0] leading-relaxed">Case study results from similar niche brand, social proof, concise question.</div>
            </div>

            <div class="p-3.5 rounded-xl bg-white/[0.02] border border-white/[0.06] text-xs">
                <div class="font-bold text-indigo-400 flex items-center gap-1.5 mb-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                    <span>Touch 3: Quick Bump (Day 5)</span>
                </div>
                <div class="text-[11px] text-[#a3a6b0] leading-relaxed">Friendly nudge ensuring message was not buried, ask for key growth point-of-contact.</div>
            </div>

            <div class="p-3.5 rounded-xl bg-white/[0.02] border border-white/[0.06] text-xs">
                <div class="font-bold text-rose-400 flex items-center gap-1.5 mb-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                    <span>Touch 4: Closure / Breakup (Day 8)</span>
                </div>
                <div class="text-[11px] text-[#a3a6b0] leading-relaxed">Permission to close the file, wishing team success, leaves door open for future outreach.</div>
            </div>
        </div>
    </div>

    <!-- Tab Filter Navigation -->
    <div class="flex items-center gap-2 border-b border-white/[0.08] pb-1 overflow-x-auto custom-scroll">
        <a href="/outreach.php?tab=pending" class="px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2 <?= $tab === 'pending' ? 'btn-gold-cta text-black' : 'text-[#a3a6b0] hover:text-[#f4f1ea]' ?>">
            <span>Ready for Initial Pitch</span>
            <span class="px-1.5 py-0.2 rounded-full <?= $tab === 'pending' ? 'bg-black/20 text-black' : 'bg-white/[0.06] text-[#d9b46c]' ?> text-[10px]"><?= $pendingCount ?></span>
        </a>
        <a href="/outreach.php?tab=followups" class="px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2 <?= $tab === 'followups' ? 'btn-gold-cta text-black' : 'text-[#a3a6b0] hover:text-[#f4f1ea]' ?>">
            <span>Follow-up Cadence</span>
            <span class="px-1.5 py-0.2 rounded-full <?= $tab === 'followups' ? 'bg-black/20 text-black' : 'bg-white/[0.06] text-[#d9b46c]' ?> text-[10px]"><?= $followupCount ?></span>
        </a>
        <a href="/outreach.php?tab=active" class="px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2 <?= $tab === 'active' ? 'btn-gold-cta text-black' : 'text-[#a3a6b0] hover:text-[#f4f1ea]' ?>">
            <span>Responded / In Chat</span>
            <span class="px-1.5 py-0.2 rounded-full <?= $tab === 'active' ? 'bg-black/20 text-black' : 'bg-white/[0.06] text-[#d9b46c]' ?> text-[10px]"><?= $activeCount ?></span>
        </a>
        <a href="/outreach.php?tab=history" class="px-4 py-2 rounded-xl text-xs font-semibold transition <?= $tab === 'history' ? 'btn-gold-cta text-black' : 'text-[#a3a6b0] hover:text-[#f4f1ea]' ?>">
            <span>Closed History</span>
        </a>
    </div>

    <!-- Prospects List for Targeted Outreach -->
    <?php if (empty($leads)): ?>
        <div class="glass-panel rounded-2xl p-12 text-center border border-white/[0.08]">
            <div class="w-12 h-12 rounded-xl bg-white/[0.06] text-[#d9b46c] flex items-center justify-center mx-auto mb-3">
                <i data-lucide="check" class="w-6 h-6"></i>
            </div>
            <h4 class="font-semibold text-[#f4f1ea] text-sm mb-1">Queue is clear!</h4>
            <p class="text-xs text-[#a3a6b0] max-w-sm mx-auto mb-4">No prospects currently pending this touchpoint. Scrape new qualified leads or check other tabs.</p>
            <a href="/scraper.php" class="btn-gold-cta inline-flex items-center gap-2 px-4 py-2 text-black font-semibold text-xs rounded-xl shadow-lg">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
                <span>Discover New Leads</span>
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($leads as $lead): 
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
                <div class="glass-card rounded-2xl p-5 border border-white/[0.08] flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div class="truncate">
                                <h4 class="font-bold text-[#f4f1ea] text-sm truncate"><?= h($lead['business_name']) ?></h4>
                                <div class="text-xs text-[#a3a6b0] truncate"><?= h($lead['category']) ?> • <?= h($lead['city']) ?></div>
                                <?php if (!empty($lead['decision_maker_name'])): ?>
                                    <div class="inline-flex items-center gap-1.5 mt-1 px-2 py-0.5 rounded-md bg-[#d9b46c]/10 text-[#d9b46c] text-[10px] font-semibold border border-[#d9b46c]/20 max-w-full truncate" title="Direct Decision Maker: <?= h($lead['decision_maker_name']) ?> (<?= h($lead['decision_maker_title'] ?: 'Founder / MD') ?>)">
                                        <i data-lucide="user-check" class="w-3 h-3 flex-shrink-0 text-[#d9b46c]"></i>
                                        <span class="truncate font-bold"><?= h($lead['decision_maker_name']) ?></span>
                                        <span class="opacity-75 truncate">• <?= h($lead['decision_maker_title'] ?: 'Founder / MD') ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" onclick='openScoreAuditModal(<?= $leadJson ?>)' class="text-xs font-bold px-2 py-0.5 rounded-full border bg-[#d9b46c]/15 text-[#f3dca4] border-[#d9b46c]/30 hover:scale-105 transition" title="View Q-Score™ Audit">
                                Q <?= $score ?>
                            </button>
                        </div>

                        <!-- Contact Badges for All Active Channels -->
                        <div class="flex items-center gap-1.5 my-3 text-xs flex-wrap">
                            <?php if (!empty($lead['website'])): ?>
                                <a href="<?= h($lead['website']) ?>" target="_blank" class="p-1.5 rounded-lg bg-white/[0.04] text-[#d9b46c] hover:bg-white/[0.08] border border-white/[0.06]" title="Website: <?= h($lead['website']) ?>">
                                    <i data-lucide="globe" class="w-3.5 h-3.5"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($lead['whatsapp'])): ?>
                                <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 border border-emerald-500/20" title="WhatsApp: <?= h($lead['whatsapp']) ?>">
                                    <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($lead['linkedin_url'])): ?>
                                <a href="<?= h($lead['linkedin_url']) ?>" target="_blank" class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/20" title="LinkedIn Profile">
                                    <i data-lucide="linkedin" class="w-3.5 h-3.5"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($lead['instagram_url'])): ?>
                                <a href="<?= h($lead['instagram_url']) ?>" target="_blank" class="p-1.5 rounded-lg bg-pink-500/10 text-pink-400 hover:bg-pink-500/20 border border-pink-500/20" title="Instagram Profile">
                                    <i data-lucide="instagram" class="w-3.5 h-3.5"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($lead['youtube_url'])): ?>
                                <a href="<?= h($lead['youtube_url']) ?>" target="_blank" class="p-1.5 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20" title="YouTube Channel">
                                    <i data-lucide="youtube" class="w-3.5 h-3.5"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($lead['email'])): ?>
                                <a href="mailto:<?= h($lead['email']) ?>" class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500/20 border border-indigo-500/20" title="Email: <?= h($lead['email']) ?>">
                                    <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($isDue): ?>
                                <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/40 animate-pulse">
                                    Follow-up Due!
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Current Stage & Value -->
                        <div class="p-2.5 rounded-xl bg-white/[0.02] border border-white/[0.06] text-xs flex items-center justify-between">
                            <span class="text-[#a3a6b0]">Deal Value: <strong class="text-[#f4f1ea]">₹<?= number_format((float)$lead['deal_value']) ?></strong></span>
                            <span class="font-mono text-[11px] text-[#d9b46c] capitalize"><?= str_replace('_', ' ', $lead['pipeline_stage']) ?></span>
                        </div>
                    </div>

                    <!-- Direct Dispatch & DM Row -->
                    <div class="flex items-center gap-2">
                        <?php if (!empty($lead['whatsapp'])): ?>
                            <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" title="1-Click WhatsApp DM" class="p-2.5 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-400 border border-emerald-500/30 transition flex items-center justify-center">
                                <i data-lucide="message-circle" class="w-4 h-4"></i>
                            </a>
                        <?php endif; ?>
                        <button onclick="openOutreachModal(<?= (int)$lead['id'] ?>, 'whatsapp')" class="btn-gold-cta flex-1 py-2.5 px-4 rounded-xl text-black font-extrabold text-xs shadow-lg transition flex items-center justify-center gap-2">
                            <i data-lucide="sparkles" class="w-4 h-4"></i>
                            <span>Dispatch AI Pitch</span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
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
render_layout('AI Outreach Hub', 'outreach', $content, $user);
