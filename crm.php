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
    <div class="glass-panel rounded-2xl p-4 border border-slate-800 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        
        <!-- Search & Filter Options -->
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <form method="GET" class="relative w-full sm:w-64">
                <input type="hidden" name="view" value="<?= h($viewMode) ?>">
                <?php if ($filterDue): ?><input type="hidden" name="filter" value="due"><?php endif; ?>
                <i data-lucide="search" class="w-4 h-4 text-slate-500 absolute left-3 top-2.5"></i>
                <input type="text" name="q" value="<?= h($searchQuery) ?>" placeholder="Search prospects by name, city..." 
                       class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
            </form>

            <a href="/crm.php?view=<?= $viewMode ?><?= $filterDue ? '' : '&filter=due' ?>" 
               class="px-3 py-2 rounded-xl text-xs font-semibold border transition flex items-center gap-1.5 <?= $filterDue ? 'bg-rose-500/20 text-rose-300 border-rose-500/40' : 'bg-slate-800 text-slate-400 border-slate-700 hover:text-white' ?>">
                <i data-lucide="bell" class="w-3.5 h-3.5 <?= $filterDue ? 'text-rose-400 animate-bounce' : '' ?>"></i>
                <span>Due Follow-ups</span>
            </a>

            <?php if (!empty($searchQuery) || $filterDue): ?>
                <a href="/crm.php?view=<?= h($viewMode) ?>" class="text-xs text-slate-400 hover:text-white flex items-center gap-1">
                    <i data-lucide="x" class="w-3 h-3"></i>
                    <span>Clear Filter</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- View Switcher & Export -->
        <div class="flex items-center gap-3 w-full md:w-auto justify-end">
            
            <!-- Kanban / Table View Toggle -->
            <div class="p-1 rounded-xl bg-slate-900 border border-slate-800 flex items-center">
                <a href="/crm.php?view=kanban<?= $filterDue ? '&filter=due' : '' ?>" title="Kanban Board View" 
                   class="p-1.5 rounded-lg text-xs font-medium transition <?= $viewMode === 'kanban' ? 'bg-emerald-500 text-black shadow' : 'text-slate-400 hover:text-white' ?>">
                    <i data-lucide="kanban" class="w-4 h-4"></i>
                </a>
                <a href="/crm.php?view=table<?= $filterDue ? '&filter=due' : '' ?>" title="Table View" 
                   class="p-1.5 rounded-lg text-xs font-medium transition <?= $viewMode === 'table' ? 'bg-emerald-500 text-black shadow' : 'text-slate-400 hover:text-white' ?>">
                    <i data-lucide="table" class="w-4 h-4"></i>
                </a>
            </div>

            <a href="/api.php?action=export_leads_csv" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium border border-slate-700 flex items-center gap-1.5 transition">
                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                <span>Export CSV</span>
            </a>

            <a href="/scraper.php" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-bold shadow-lg shadow-emerald-500/20 flex items-center gap-1.5 transition">
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
                <div class="kanban-col flex flex-col glass-panel rounded-2xl border border-slate-800/80 overflow-hidden">
                    
                    <!-- Column Header -->
                    <div class="p-3.5 border-b border-slate-800/80 bg-slate-900/70 flex items-center justify-between">
                        <div class="flex items-center gap-2 truncate">
                            <span class="w-2 h-2 rounded-full <?= $stageKey === 'closed_won' ? 'bg-emerald-400 animate-pulse' : ($stageKey === 'hot' ? 'bg-rose-400' : 'bg-slate-400') ?>"></span>
                            <h3 class="font-bold text-white text-xs truncate"><?= h($stage['name']) ?></h3>
                        </div>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full <?= $stage['badge'] ?>">
                            <?= count($stageLeads) ?>
                        </span>
                    </div>

                    <?php if ($stageValue > 0): ?>
                        <div class="px-3.5 py-1 bg-slate-950/40 text-[10px] text-slate-400 font-mono border-b border-slate-800/40">
                            Est. Value: ₹<?= number_format($stageValue) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Drop Target Container -->
                    <div class="kanban-col-cards p-3 space-y-3 flex-1 overflow-y-auto max-h-[75vh] custom-scroll" data-stage="<?= h($stageKey) ?>">
                        
                        <?php if (empty($stageLeads)): ?>
                            <div class="py-8 text-center text-[11px] text-slate-600 italic pointer-events-none">
                                Drag prospects here
                            </div>
                        <?php else: ?>
                            <?php foreach ($stageLeads as $lead): 
                                $isDue = !empty($lead['next_followup_at']) && $lead['next_followup_at'] <= today() && !in_array($lead['pipeline_stage'], ['closed_won', 'closed_lost']);
                            ?>
                                <div class="kanban-card glass-card rounded-xl p-3 border border-slate-700/60 cursor-grab active:cursor-grabbing space-y-2 text-xs" 
                                     data-lead-id="<?= (int)$lead['id'] ?>">
                                    
                                    <div class="flex items-start justify-between gap-1">
                                        <div class="font-bold text-white truncate max-w-[190px] leading-snug">
                                            <?= h($lead['business_name']) ?>
                                        </div>
                                        <span class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded bg-slate-800 text-emerald-400 flex-shrink-0">
                                            Q <?= (int)$lead['qualification_score'] ?>
                                        </span>
                                    </div>

                                    <div class="text-[11px] text-slate-400 truncate">
                                        <?= h($lead['category']) ?> • <?= h($lead['city'] ?: 'India') ?>
                                    </div>

                                    <!-- Value & Follow-up Badges -->
                                    <div class="flex items-center justify-between text-[11px] pt-1">
                                        <span class="font-semibold text-slate-300">
                                            ₹<?= number_format((float)$lead['deal_value']) ?>
                                        </span>
                                        <?php if ($isDue): ?>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30 flex items-center gap-1">
                                                <i data-lucide="bell" class="w-3 h-3 text-rose-400"></i>
                                                <span>Due</span>
                                            </span>
                                        <?php elseif (!empty($lead['next_followup_at'])): ?>
                                            <span class="text-[10px] text-slate-500 font-mono">
                                                <?= date('M d', strtotime($lead['next_followup_at'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Quick Contact Channels & AI Trigger -->
                                    <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between">
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
                                            <?php if (!empty($lead['email'])): ?>
                                                <button onclick="openOutreachModal(<?= (int)$lead['id'] ?>, 'email')" title="Email Pitch" class="p-1 rounded bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500/20">
                                                    <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <button onclick="openOutreachModal(<?= (int)$lead['id'] ?>)" class="text-[11px] font-bold text-emerald-400 hover:text-emerald-300 flex items-center gap-1">
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
        <div class="glass-panel rounded-2xl border border-slate-800 overflow-hidden">
            <div class="overflow-x-auto custom-scroll">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-800/80 bg-slate-900/60 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="py-3 px-4">Business</th>
                            <th class="py-3 px-4">Stage</th>
                            <th class="py-3 px-4">Q-Score</th>
                            <th class="py-3 px-4">Deal Value</th>
                            <th class="py-3 px-4">Direct WhatsApp</th>
                            <th class="py-3 px-4">Next Follow-up</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php foreach ($allLeads as $lead): 
                            $stageCfg = PIPELINE_STAGES[$lead['pipeline_stage']] ?? PIPELINE_STAGES['new'];
                        ?>
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-white"><?= h($lead['business_name']) ?></div>
                                    <div class="text-[11px] text-slate-400"><?= h($lead['category']) ?> • <?= h($lead['city']) ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <select onchange="updateLeadStage(<?= (int)$lead['id'] ?>, this.value)" class="bg-slate-900 border border-slate-700 rounded-lg px-2 py-1 text-xs text-white focus:outline-none focus:border-emerald-500">
                                        <?php foreach (PIPELINE_STAGES as $k => $cfg): ?>
                                            <option value="<?= h($k) ?>" <?= $lead['pipeline_stage'] === $k ? 'selected' : '' ?>><?= h($cfg['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-mono font-bold text-emerald-400"><?= (int)$lead['qualification_score'] ?></span>/100
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-200">
                                    ₹<?= number_format((float)$lead['deal_value']) ?>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-300">
                                    <?= h($lead['whatsapp'] ?: $lead['phone']) ?>
                                </td>
                                <td class="py-3 px-4 text-[11px] text-slate-400">
                                    <?= !empty($lead['next_followup_at']) ? date('M d, Y', strtotime($lead['next_followup_at'])) : '—' ?>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <button onclick="openOutreachModal(<?= (int)$lead['id'] ?>)" class="px-3 py-1 rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/30 font-semibold text-xs">
                                        AI Outreach
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
render_layout('CRM Pipeline', 'crm', $content, $user);
