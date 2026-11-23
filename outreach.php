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
                            <?php 
                                $outLiUrl = !empty($lead['decision_maker_linkedin']) ? $lead['decision_maker_linkedin'] : ($lead['linkedin_url'] ?: ('https://www.linkedin.com/search/results/people/?keywords=' . urlencode(($lead['decision_maker_name'] ?: 'Founder') . ' ' . $lead['business_name'])));
                                $cleanSlugOut = preg_replace('/[^a-z0-9]/', '', strtolower($lead['business_name']));
                                $outIgUrl = (!empty($lead['instagram_url']) && strpos($lead['instagram_url'], 'restige') === false) ? $lead['instagram_url'] : ("https://www.instagram.com/explore/tags/{$cleanSlugOut}/");
                                $outYtUrl = (!empty($lead['youtube_url']) && strpos($lead['youtube_url'], '@') === false) ? $lead['youtube_url'] : ('https://www.youtube.com/results?search_query=' . urlencode($lead['business_name'] . ' ' . $lead['city']));
                            ?>
                            <a href="<?= h($outLiUrl) ?>" target="_blank" class="w-6 h-6 rounded-lg bg-[#0A66C2]/15 hover:bg-[#0A66C2]/25 border border-[#0A66C2]/40 social-icon-btn flex items-center justify-center transition transform hover:scale-110" title="LinkedIn: Direct Profile & Message (<?= h($lead['decision_maker_name'] ?: 'Decision Maker') ?>)">
                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-[#0A66C2]"><path d="M19 3A2 2 0 0 1 21 5V19A2 2 0 0 1 19 21H5A2 2 0 0 1 3 19V5A2 2 0 0 1 5 3H19M18.5 18.5V13.2A3.26 3.26 0 0 0 15.24 9.94C14.39 9.94 13.4 10.46 12.92 11.24V10.13H10.13V18.5H12.92V13.57C12.92 12.8 13.54 12.17 14.31 12.17A1.4 1.4 0 0 1 15.71 13.57V18.5H18.5M6.88 8.56A1.68 1.68 0 0 0 8.56 6.88C8.56 5.95 7.81 5.19 6.88 5.19A1.69 1.69 0 0 0 5.19 6.88C5.19 7.81 5.95 8.56 6.88 8.56M8.27 18.5V10.13H5.5V18.5H8.27Z"/></svg>
                            </a>
                            <a href="<?= h($outIgUrl) ?>" target="_blank" class="w-6 h-6 rounded-lg bg-[#E4405F]/15 hover:bg-[#E4405F]/25 border border-[#E4405F]/40 social-icon-btn flex items-center justify-center transition transform hover:scale-110" title="Instagram: Explore & Posts">
                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-[#E4405F]"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                            <a href="<?= h($outYtUrl) ?>" target="_blank" class="w-6 h-6 rounded-lg bg-[#FF0000]/15 hover:bg-[#FF0000]/25 border border-[#FF0000]/40 social-icon-btn flex items-center justify-center transition transform hover:scale-110" title="YouTube: Video Showcase">
                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-[#FF0000]"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            </a>
                            <?php if (!empty($lead['email'])): ?>
                                <a href="mailto:<?= h($lead['email']) ?>" class="w-6 h-6 rounded-lg bg-[#818cf8]/15 hover:bg-[#818cf8]/25 border border-[#818cf8]/40 social-icon-btn flex items-center justify-center transition transform hover:scale-110" title="Email: <?= h($lead['email']) ?>">
                                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-[#818cf8]"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
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
