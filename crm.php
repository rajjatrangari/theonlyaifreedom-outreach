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

ob_start();
?>

<div class="space-y-6">
    
    <!-- Top Filter & Action Bar -->
    <div class="glass-panel rounded-2xl p-4 border border-white/[0.08] flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        
        <!-- Search & Filter Options -->
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <form method="GET" class="relative w-full sm:w-64">
                <input type="hidden" name="view" value="<?= h($viewMode) ?>">
                <?php if ($filterDue): ?><input type="hidden" name="filter" value="due"><?php endif; ?>
                <i data-lucide="search" class="w-4 h-4 text-[#a3a6b0] absolute left-3 top-2.5"></i>
                <input type="text" name="q" value="<?= h($searchQuery) ?>" placeholder="Search prospects by name, city..." 
                       class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl pl-9 pr-3 py-2 text-xs text-[#f4f1ea] placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
            </form>

            <a href="/crm.php?view=<?= $viewMode ?><?= $filterDue ? '' : '&filter=due' ?>" 
               class="px-3 py-2 rounded-xl text-xs font-semibold border transition flex items-center gap-1.5 <?= $filterDue ? 'bg-rose-500/20 text-rose-300 border-rose-500/40' : 'bg-white/[0.04] text-[#a3a6b0] border-white/[0.08] hover:text-[#f4f1ea]' ?>">
                <i data-lucide="bell" class="w-3.5 h-3.5 <?= $filterDue ? 'text-rose-400 animate-bounce' : '' ?>"></i>
                <span>Due Follow-ups</span>
            </a>

            <?php if (!empty($searchQuery) || $filterDue): ?>
                <a href="/crm.php?view=<?= h($viewMode) ?>" class="text-xs text-[#a3a6b0] hover:text-[#f4f1ea] flex items-center gap-1">
                    <i data-lucide="x" class="w-3 h-3"></i>
                    <span>Clear Filter</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- View Switcher & Export -->
        <div class="flex items-center gap-3 w-full md:w-auto justify-end">
            
            <!-- Kanban / Table View Toggle -->
            <div class="p-1 rounded-xl bg-white/[0.04] border border-white/[0.08] flex items-center">
                <a href="/crm.php?view=kanban<?= $filterDue ? '&filter=due' : '' ?>" title="Kanban Board View" 
                   class="p-1.5 rounded-lg text-xs font-medium transition <?= $viewMode === 'kanban' ? 'btn-gold-cta text-black shadow' : 'text-[#a3a6b0] hover:text-white' ?>">
                    <i data-lucide="kanban" class="w-4 h-4"></i>
                </a>
                <a href="/crm.php?view=table<?= $filterDue ? '&filter=due' : '' ?>" title="Table View" 
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
                                        <?php if (!empty($lead['linkedin_url'])): ?>
                                            <a href="<?= h($lead['linkedin_url']) ?>" target="_blank" title="LinkedIn Profile" class="p-1 rounded bg-blue-500/10 text-blue-400 hover:bg-blue-500/20">
                                                <i data-lucide="linkedin" class="w-3 h-3"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($lead['instagram_url'])): ?>
                                            <a href="<?= h($lead['instagram_url']) ?>" target="_blank" title="Instagram Profile" class="p-1 rounded bg-pink-500/10 text-pink-400 hover:bg-pink-500/20">
                                                <i data-lucide="instagram" class="w-3 h-3"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($lead['youtube_url'])): ?>
                                            <a href="<?= h($lead['youtube_url']) ?>" target="_blank" title="YouTube Channel" class="p-1 rounded bg-red-500/10 text-red-400 hover:bg-red-500/20">
                                                <i data-lucide="youtube" class="w-3 h-3"></i>
                                            </a>
                                        <?php endif; ?>
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
                                        <?php if (!empty($lead['linkedin_url'])): ?>
                                            <a href="<?= h($lead['linkedin_url']) ?>" target="_blank" title="LinkedIn" class="p-1 rounded bg-blue-500/10 text-blue-400 hover:bg-blue-500/20">
                                                <i data-lucide="linkedin" class="w-3.5 h-3.5"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($lead['instagram_url'])): ?>
                                            <a href="<?= h($lead['instagram_url']) ?>" target="_blank" title="Instagram" class="p-1 rounded bg-pink-500/10 text-pink-400 hover:bg-pink-500/20">
                                                <i data-lucide="instagram" class="w-3.5 h-3.5"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($lead['youtube_url'])): ?>
                                            <a href="<?= h($lead['youtube_url']) ?>" target="_blank" title="YouTube" class="p-1 rounded bg-red-500/10 text-red-400 hover:bg-red-500/20">
                                                <i data-lucide="youtube" class="w-3.5 h-3.5"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($lead['facebook_url'])): ?>
                                            <a href="<?= h($lead['facebook_url']) ?>" target="_blank" title="Facebook" class="p-1 rounded bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500/20">
                                                <i data-lucide="facebook" class="w-3.5 h-3.5"></i>
                                            </a>
                                        <?php endif; ?>
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
