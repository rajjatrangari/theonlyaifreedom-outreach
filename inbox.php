<?php
// Outreach AI Freedom - Multi-Channel Unified Inbox & CRM Activity Tracker
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/layout.php';

$user = require_auth();
$userId = (int)$user['id'];

// Get all leads for user, prioritized by recent activity and active responses
$leads = all("
    SELECT l.*,
           (SELECT COUNT(*) FROM outreach_logs o WHERE o.lead_id = l.id) as log_count,
           (SELECT MAX(created_at) FROM outreach_logs o WHERE o.lead_id = l.id) as last_log_at
    FROM leads l
    WHERE l.user_id = ?
    ORDER BY 
        CASE WHEN l.last_reply_message IS NOT NULL AND l.last_reply_message != '' THEN 0 ELSE 1 END,
        CASE WHEN l.last_activity_at IS NOT NULL THEN l.last_activity_at ELSE l.created_at END DESC
", [$userId]);

// Channels summary statistics
$channelCounts = [
    'all' => count($leads),
    'whatsapp' => 0,
    'linkedin' => 0,
    'instagram' => 0,
    'facebook' => 0,
    'email' => 0,
];

$activeNowCount = 0;
$nowTs = time();

foreach ($leads as $l) {
    $ch = strtolower($l['active_platform'] ?: ($l['last_channel'] ?: 'whatsapp'));
    if (isset($channelCounts[$ch])) {
        $channelCounts[$ch]++;
    }
    if (!empty($l['last_activity_at'])) {
        $diffMins = ($nowTs - strtotime($l['last_activity_at'])) / 60;
        if ($diffMins <= 30) {
            $activeNowCount++;
        }
    }
}

// Initial selected lead
$selectedLeadId = (int)($_GET['lead_id'] ?? ($leads[0]['id'] ?? 0));
$selectedLead = null;
if ($selectedLeadId > 0) {
    foreach ($leads as $l) {
        if ((int)$l['id'] === $selectedLeadId) {
            $selectedLead = $l;
            break;
        }
    }
}
if (!$selectedLead && !empty($leads)) {
    $selectedLead = $leads[0];
    $selectedLeadId = (int)$selectedLead['id'];
}

// Outreach history for selected lead
$history = [];
if ($selectedLead) {
    $history = all("
        SELECT * FROM outreach_logs
        WHERE lead_id = ? AND user_id = ?
        ORDER BY id ASC
    ", [$selectedLead['id'], $userId]);
}

ob_start();
?>

<div class="h-[calc(100vh-5rem)] flex flex-col -m-6 p-6">
    
    <!-- Top Header Bar: Unified Inbox Metric & Multi-Channel Filters -->
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-4 pb-4 border-b border-white/[0.08]">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 text-[11px] font-bold uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Unified Omni-Channel Inbox
                </span>
                <span class="text-xs text-[#a3a6b0]">•</span>
                <span class="text-xs text-[#a3a6b0]"><?= count($leads) ?> active prospects tracked</span>
                <?php if ($activeNowCount > 0): ?>
                    <span class="text-xs text-[#a3a6b0]">•</span>
                    <span class="text-xs text-emerald-400 font-semibold flex items-center gap-1">
                        <i data-lucide="radio" class="w-3.5 h-3.5"></i>
                        <?= $activeNowCount ?> active right now
                    </span>
                <?php endif; ?>
            </div>
            <h2 class="text-xl font-extrabold text-[#f4f1ea] tracking-tight">Direct Client Conversations & Follow-Up Hub</h2>
        </div>

        <!-- Channel Filter Tabs with Crisp SVG Brand Logos -->
        <div class="flex items-center gap-1.5 overflow-x-auto max-w-full p-1 rounded-xl bg-white/[0.03] border border-white/[0.08] custom-scroll">
            <button type="button" onclick="filterChannel('all')" id="tab-all" class="channel-tab-btn active-tab px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 bg-[#d9b46c] text-black shadow-sm">
                <span>All Inboxes</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-black/20 text-black font-extrabold"><?= $channelCounts['all'] ?></span>
            </button>
            <button type="button" onclick="filterChannel('whatsapp')" id="tab-whatsapp" class="channel-tab-btn px-3 py-1.5 rounded-lg text-xs font-semibold text-[#a3a6b0] hover:text-white transition flex items-center gap-1.5">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-current text-[#25D366]">
                    <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2M12.05 3.67C14.25 3.67 16.31 4.53 17.87 6.09C19.42 7.65 20.28 9.72 20.28 11.92C20.28 16.46 16.58 20.15 12.04 20.15C10.56 20.15 9.11 19.76 7.85 19L7.55 18.83L4.43 19.65L5.26 16.61L5.06 16.29C4.24 14.99 3.8 13.47 3.8 11.91C3.81 7.37 7.5 3.67 12.05 3.67M9.53 7.35C9.37 7.35 9.1 7.41 8.88 7.65C8.65 7.89 8.02 8.49 8.02 9.71C8.02 10.93 8.91 12.11 9.03 12.27C9.15 12.43 10.74 14.96 13.25 15.96C15.35 16.79 15.77 16.62 16.23 16.58C16.69 16.54 17.71 15.98 17.92 15.39C18.13 14.79 18.13 14.28 18.07 14.18C18.01 14.07 17.85 14.01 17.61 13.89C17.37 13.77 16.19 13.19 15.97 13.11C15.75 13.03 15.59 12.99 15.43 13.23C15.27 13.47 14.81 14.01 14.67 14.18C14.53 14.34 14.39 14.36 14.15 14.24C13.91 14.12 12.9 13.79 11.7 12.72C10.77 11.89 10.14 10.86 10.02 10.62C9.9 10.38 10.01 10.25 10.13 10.13C10.24 10.02 10.38 9.84 10.5 9.7C10.62 9.56 10.66 9.46 10.74 9.3C10.82 9.14 10.78 9 10.72 8.88C10.66 8.76 10.2 7.63 10 7.17C9.82 6.72 9.63 6.78 9.53 6.78V7.35Z"/>
                </svg>
                <span>WhatsApp</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-white/[0.06] text-[#a3a6b0]"><?= $channelCounts['whatsapp'] ?></span>
            </button>
            <button type="button" onclick="filterChannel('linkedin')" id="tab-linkedin" class="channel-tab-btn px-3 py-1.5 rounded-lg text-xs font-semibold text-[#a3a6b0] hover:text-white transition flex items-center gap-1.5">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-current text-[#0A66C2]">
                    <path d="M19 3A2 2 0 0 1 21 5V19A2 2 0 0 1 19 21H5A2 2 0 0 1 3 19V5A2 2 0 0 1 5 3H19M18.5 18.5V13.2A3.26 3.26 0 0 0 15.24 9.94C14.39 9.94 13.4 10.46 12.92 11.24V10.13H10.13V18.5H12.92V13.57C12.92 12.8 13.54 12.17 14.31 12.17A1.4 1.4 0 0 1 15.71 13.57V18.5H18.5M6.88 8.56A1.68 1.68 0 0 0 8.56 6.88C8.56 5.95 7.81 5.19 6.88 5.19A1.69 1.69 0 0 0 5.19 6.88C5.19 7.81 5.95 8.56 6.88 8.56M8.27 18.5V10.13H5.5V18.5H8.27Z"/>
                </svg>
                <span>LinkedIn</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-white/[0.06] text-[#a3a6b0]"><?= $channelCounts['linkedin'] ?></span>
            </button>
            <button type="button" onclick="filterChannel('instagram')" id="tab-instagram" class="channel-tab-btn px-3 py-1.5 rounded-lg text-xs font-semibold text-[#a3a6b0] hover:text-white transition flex items-center gap-1.5">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-current text-[#E4405F]">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
                <span>Instagram</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-white/[0.06] text-[#a3a6b0]"><?= $channelCounts['instagram'] ?></span>
            </button>
            <button type="button" onclick="filterChannel('facebook')" id="tab-facebook" class="channel-tab-btn px-3 py-1.5 rounded-lg text-xs font-semibold text-[#a3a6b0] hover:text-white transition flex items-center gap-1.5">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-current text-[#1877F2]">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                <span>Facebook</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-white/[0.06] text-[#a3a6b0]"><?= $channelCounts['facebook'] ?></span>
            </button>
            <button type="button" onclick="filterChannel('email')" id="tab-email" class="channel-tab-btn px-3 py-1.5 rounded-lg text-xs font-semibold text-[#a3a6b0] hover:text-white transition flex items-center gap-1.5">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-current text-[#818cf8]">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
                <span>Email</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-white/[0.06] text-[#a3a6b0]"><?= $channelCounts['email'] ?></span>
            </button>
        </div>
    </div>

    <!-- Main Workspace Split Pane (Thread List + Active Conversation) -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-5 min-h-0 overflow-hidden">
        
        <!-- Left Column: Lead Conversation Threads (4 cols) -->
        <div class="lg:col-span-4 flex flex-col glass-panel rounded-2xl border border-white/[0.08] overflow-hidden">
            
            <!-- Thread Search & Filter Bar -->
            <div class="p-3.5 border-b border-white/[0.08] bg-white/[0.02] space-y-2.5">
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-[#a3a6b0] absolute left-3 top-2.5"></i>
                    <input type="text" id="threadSearch" placeholder="Search prospect, brand, city..." oninput="handleThreadSearch()"
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl pl-9 pr-3 py-2 text-xs text-[#f4f1ea] placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                </div>
                <div class="flex items-center gap-1.5">
                    <button type="button" onclick="filterPresence('all')" id="presence-all" class="presence-btn active-presence px-2.5 py-1 rounded-lg text-[11px] font-bold bg-[#d9b46c]/20 text-[#f3dca4] border border-[#d9b46c]/40">All</button>
                    <button type="button" onclick="filterPresence('active')" id="presence-active" class="presence-btn px-2.5 py-1 rounded-lg text-[11px] font-medium text-[#a3a6b0] hover:text-white bg-white/[0.03] border border-white/[0.06]">● Active Now</button>
                    <button type="button" onclick="filterPresence('replied')" id="presence-replied" class="presence-btn px-2.5 py-1 rounded-lg text-[11px] font-medium text-[#a3a6b0] hover:text-white bg-white/[0.03] border border-white/[0.06]">💬 Replies</button>
                </div>
            </div>

            <!-- Scrollable Thread Cards -->
            <div id="threadList" class="flex-1 overflow-y-auto divide-y divide-white/[0.06] custom-scroll">
                <?php if (empty($leads)): ?>
                    <div class="p-8 text-center text-[#a3a6b0] text-xs">
                        <i data-lucide="inbox" class="w-8 h-8 text-[#d9b46c] mx-auto mb-2 opacity-60"></i>
                        <p class="font-bold text-[#f4f1ea]">No conversations yet</p>
                        <p class="mt-1">Scrape high-paying leads and dispatch your first AI pitch to see live threads here.</p>
                        <a href="/scraper.php" class="inline-block mt-3 btn-gold-cta px-4 py-2 rounded-xl text-black font-extrabold text-xs">Find Qualified Leads</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($leads as $l): 
                        $isSelected = ((int)$l['id'] === $selectedLeadId);
                        $platform = strtolower($l['active_platform'] ?: ($l['last_channel'] ?: 'whatsapp'));
                        $hasReply = !empty($l['last_reply_message']);
                        
                        // Active presence calculation
                        $presenceText = 'Inactive';
                        $isHotActive = false;
                        if (!empty($l['last_activity_at'])) {
                            $diffM = (int)(($nowTs - strtotime($l['last_activity_at'])) / 60);
                            if ($diffM <= 15) {
                                $presenceText = 'Active now';
                                $isHotActive = true;
                            } elseif ($diffM <= 60) {
                                $presenceText = "Active {$diffM}m ago";
                                $isHotActive = true;
                            } elseif ($diffM <= 1440) {
                                $presenceText = "Active " . round($diffM / 60) . "h ago";
                            } else {
                                $presenceText = "Active " . round($diffM / 1440) . "d ago";
                            }
                        }

                        $previewMsg = $hasReply ? $l['last_reply_message'] : "Outreach cadence initialized. Click to view timeline.";
                        $dmDisplay = !empty($l['decision_maker_name']) ? $l['decision_maker_name'] : 'Decision Maker';
                    ?>
                        <div onclick="selectThread(<?= (int)$l['id'] ?>)" 
                             data-thread-id="<?= (int)$l['id'] ?>"
                             data-channel="<?= h($platform) ?>"
                             data-has-reply="<?= $hasReply ? '1' : '0' ?>"
                             data-active-now="<?= $isHotActive ? '1' : '0' ?>"
                             data-search-text="<?= strtolower(h($l['business_name'] . ' ' . $dmDisplay . ' ' . $l['city'])) ?>"
                             class="thread-card p-3.5 cursor-pointer transition relative hover:bg-white/[0.04] <?= $isSelected ? 'bg-white/[0.06] border-l-4 border-l-[#d9b46c]' : '' ?>">
                            
                            <div class="flex items-start gap-3">
                                <!-- Avatar with Channel Emblem -->
                                <div class="relative flex-shrink-0">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#11141c] to-[#1e2330] border border-white/[0.12] flex items-center justify-center font-bold text-[#f3dca4] text-xs">
                                        <?= strtoupper(substr($l['business_name'], 0, 1)) ?>
                                    </div>
                                    <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-[#0c0e14] border border-white/[0.15] flex items-center justify-center">
                                        <?php if ($platform === 'whatsapp'): ?>
                                            <i data-lucide="message-circle" class="w-3 h-3 text-emerald-400"></i>
                                        <?php elseif ($platform === 'linkedin'): ?>
                                            <i data-lucide="linkedin" class="w-3 h-3 text-blue-400"></i>
                                        <?php elseif ($platform === 'instagram'): ?>
                                            <i data-lucide="instagram" class="w-3 h-3 text-pink-400"></i>
                                        <?php elseif ($platform === 'facebook'): ?>
                                            <i data-lucide="facebook" class="w-3 h-3 text-blue-500"></i>
                                        <?php else: ?>
                                            <i data-lucide="mail" class="w-3 h-3 text-amber-400"></i>
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <!-- Thread Meta & Preview -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1 mb-0.5">
                                        <h4 class="font-bold text-[#f4f1ea] text-xs truncate"><?= h($l['business_name']) ?></h4>
                                        <span class="text-[10px] text-[#a3a6b0] flex-shrink-0"><?= h($presenceText) ?></span>
                                    </div>
                                    
                                    <div class="text-[11px] text-[#d9b46c] flex items-center gap-1 font-medium truncate mb-1">
                                        <i data-lucide="user-check" class="w-3 h-3 flex-shrink-0"></i>
                                        <span class="truncate"><?= h($dmDisplay) ?></span>
                                        <span class="text-[#a3a6b0] truncate">• <?= h($l['city'] ?: 'India') ?></span>
                                    </div>

                                    <p class="text-[11px] text-[#a3a6b0] truncate line-clamp-1 <?= $hasReply ? 'text-[#f4f1ea] font-medium' : '' ?>">
                                        <?= $hasReply ? '💬 ' : '' ?><?= h($previewMsg) ?>
                                    </p>

                                    <!-- Tags Row: Presence & Meta Ads -->
                                    <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                        <?php if ($isHotActive): ?>
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-md bg-emerald-500/15 text-emerald-400 text-[9px] font-bold border border-emerald-500/30">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                                Active on <?= ucfirst($platform) ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if (!empty($l['meta_ads_active'])): ?>
                                            <span class="px-1.5 py-0.2 rounded-md bg-blue-500/15 text-blue-400 text-[9px] font-semibold border border-blue-500/30">
                                                📢 <?= (int)$l['meta_ads_count'] ?> Ads
                                            </span>
                                        <?php endif; ?>

                                        <?php if (empty($l['has_website']) || empty($l['website'])): ?>
                                            <span class="px-1.5 py-0.2 rounded-md bg-amber-500/15 text-amber-400 text-[9px] font-semibold border border-amber-500/30">
                                                ⚠️ Social Only
                                            </span>
                                        <?php endif; ?>

                                        <span class="ml-auto text-[9px] font-mono font-bold text-[#d9b46c] bg-[#d9b46c]/10 px-1.5 py-0.2 rounded border border-[#d9b46c]/20">
                                            Q <?= (int)$l['qualification_score'] ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Active Conversation & 1-Click Dispatcher (8 cols) -->
        <div class="lg:col-span-8 flex flex-col glass-panel rounded-2xl border border-white/[0.08] overflow-hidden">
            
            <?php if (!$selectedLead): ?>
                <div class="flex-1 flex flex-col items-center justify-center p-12 text-center text-[#a3a6b0]">
                    <div class="w-16 h-16 rounded-2xl bg-white/[0.04] text-[#d9b46c] flex items-center justify-center mb-4">
                        <i data-lucide="messages-square" class="w-8 h-8"></i>
                    </div>
                    <h3 class="font-extrabold text-[#f4f1ea] text-lg">Select a Conversation</h3>
                    <p class="text-xs max-w-sm mt-1">Choose any lead from the left pane to view full multi-channel chat history, live activity presence, and fire instant replies.</p>
                </div>
            <?php else: 
                $selPlatform = strtolower($selectedLead['active_platform'] ?: ($selectedLead['last_channel'] ?: 'whatsapp'));
                $cleanPhone = preg_replace('/[^\d]/', '', $selectedLead['whatsapp'] ?: $selectedLead['phone']);
                $dmName = !empty($selectedLead['decision_maker_name']) ? $selectedLead['decision_maker_name'] : 'Decision Maker';
                $dmTitle = !empty($selectedLead['decision_maker_title']) ? $selectedLead['decision_maker_title'] : 'Founder & Managing Director';
                $isHotActive = false;
                if (!empty($selectedLead['last_activity_at'])) {
                    $diffM = ($nowTs - strtotime($selectedLead['last_activity_at'])) / 60;
                    if ($diffM <= 45) $isHotActive = true;
                }
            ?>

                <!-- Lead Profile Header & 1-Click External Actions -->
                <div class="p-4 border-b border-white/[0.08] bg-white/[0.02]">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-extrabold text-[#f4f1ea]"><?= h($selectedLead['business_name']) ?></h3>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold">
                                    Q-Score: <?= (int)$selectedLead['qualification_score'] ?>/100
                                </span>
                                <?php if (!empty($selectedLead['meta_ads_active'])): ?>
                                    <a href="<?= h($selectedLead['meta_ads_library_url'] ?: 'https://www.facebook.com/ads/library/?active_status=all&ad_type=all&country=IN&q=' . urlencode($selectedLead['business_name'])) ?>" 
                                       target="_blank" class="px-2 py-0.5 rounded-full bg-blue-500/15 text-blue-300 hover:text-white border border-blue-500/30 text-[10px] font-semibold flex items-center gap-1 transition" title="Open Official Meta Ad Library">
                                        <i data-lucide="radio" class="w-3 h-3 text-blue-400"></i>
                                        <span>📢 <?= (int)$selectedLead['meta_ads_count'] ?> Active Ads (<?= h($selectedLead['meta_ads_running_since'] ?: 'Active') ?>)</span>
                                        <i data-lucide="external-link" class="w-2.5 h-2.5"></i>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="text-xs text-[#a3a6b0] flex flex-wrap items-center gap-2 mt-1">
                                <span class="inline-flex items-center gap-1 text-[#d9b46c] font-bold">
                                    <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                                    <span><?= h($dmName) ?></span>
                                    <span class="text-[#a3a6b0] font-normal">(<?= h($dmTitle) ?>)</span>
                                </span>
                                <span>•</span>
                                <span><?= h($selectedLead['category']) ?></span>
                                <span>•</span>
                                <span><?= h($selectedLead['city'] ?: 'India') ?></span>
                                <?php if (!empty($selectedLead['rating']) && (float)$selectedLead['rating'] > 0): ?>
                                    <span>•</span>
                                    <span class="text-amber-400 font-semibold">★ <?= $selectedLead['rating'] ?> (<?= (int)$selectedLead['reviews_count'] ?> reviews)</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 1-Click External App Jump Buttons with Authentic SVG Brand Logos -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <?php if (!empty($cleanPhone)): ?>
                                <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="px-2.5 py-1.5 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-400 text-xs font-semibold border border-emerald-500/30 flex items-center gap-1.5 transition" title="Open Native WhatsApp Web">
                                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-current text-[#25D366]">
                                        <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2M12.05 3.67C14.25 3.67 16.31 4.53 17.87 6.09C19.42 7.65 20.28 9.72 20.28 11.92C20.28 16.46 16.58 20.15 12.04 20.15C10.56 20.15 9.11 19.76 7.85 19L7.55 18.83L4.43 19.65L5.26 16.61L5.06 16.29C4.24 14.99 3.8 13.47 3.8 11.91C3.81 7.37 7.5 3.67 12.05 3.67M9.53 7.35C9.37 7.35 9.1 7.41 8.88 7.65C8.65 7.89 8.02 8.49 8.02 9.71C8.02 10.93 8.91 12.11 9.03 12.27C9.15 12.43 10.74 14.96 13.25 15.96C15.35 16.79 15.77 16.62 16.23 16.58C16.69 16.54 17.71 15.98 17.92 15.39C18.13 14.79 18.13 14.28 18.07 14.18C18.01 14.07 17.85 14.01 17.61 13.89C17.37 13.77 16.19 13.19 15.97 13.11C15.75 13.03 15.59 12.99 15.43 13.23C15.27 13.47 14.81 14.01 14.67 14.18C14.53 14.34 14.39 14.36 14.15 14.24C13.91 14.12 12.9 13.79 11.7 12.72C10.77 11.89 10.14 10.86 10.02 10.62C9.9 10.38 10.01 10.25 10.13 10.13C10.24 10.02 10.38 9.84 10.5 9.7C10.62 9.56 10.66 9.46 10.74 9.3C10.82 9.14 10.78 9 10.72 8.88C10.66 8.76 10.2 7.63 10 7.17C9.82 6.72 9.63 6.78 9.53 6.78V7.35Z"/>
                                    </svg>
                                    <span>WhatsApp Web</span>
                                    <i data-lucide="external-link" class="w-3 h-3 opacity-60"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($selectedLead['linkedin_url'])): ?>
                                <a href="<?= h($selectedLead['linkedin_url']) ?>" target="_blank" class="px-2.5 py-1.5 rounded-xl bg-blue-500/15 hover:bg-blue-500/25 text-blue-400 text-xs font-semibold border border-blue-500/30 flex items-center gap-1.5 transition" title="Open LinkedIn Profile">
                                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-current text-[#0A66C2]">
                                        <path d="M19 3A2 2 0 0 1 21 5V19A2 2 0 0 1 19 21H5A2 2 0 0 1 3 19V5A2 2 0 0 1 5 3H19M18.5 18.5V13.2A3.26 3.26 0 0 0 15.24 9.94C14.39 9.94 13.4 10.46 12.92 11.24V10.13H10.13V18.5H12.92V13.57C12.92 12.8 13.54 12.17 14.31 12.17A1.4 1.4 0 0 1 15.71 13.57V18.5H18.5M6.88 8.56A1.68 1.68 0 0 0 8.56 6.88C8.56 5.95 7.81 5.19 6.88 5.19A1.69 1.69 0 0 0 5.19 6.88C5.19 7.81 5.95 8.56 6.88 8.56M8.27 18.5V10.13H5.5V18.5H8.27Z"/>
                                    </svg>
                                    <span>LinkedIn</span>
                                    <i data-lucide="external-link" class="w-3 h-3 opacity-60"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($selectedLead['instagram_url'])): ?>
                                <a href="<?= h($selectedLead['instagram_url']) ?>" target="_blank" class="px-2.5 py-1.5 rounded-xl bg-pink-500/15 hover:bg-pink-500/25 text-pink-400 text-xs font-semibold border border-pink-500/30 flex items-center gap-1.5 transition" title="Open Instagram DM">
                                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-current text-[#E4405F]">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                    <span>Instagram</span>
                                    <i data-lucide="external-link" class="w-3 h-3 opacity-60"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($selectedLead['website'])): ?>
                                <a href="<?= h($selectedLead['website']) ?>" target="_blank" class="px-2.5 py-1.5 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] text-[#f4f1ea] text-xs font-semibold border border-white/[0.1] flex items-center gap-1.5 transition" title="Visit Live Corporate Website">
                                    <i data-lucide="globe" class="w-3.5 h-3.5 text-[#d9b46c]"></i>
                                    <span>Website</span>
                                    <i data-lucide="external-link" class="w-3 h-3 opacity-60"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Social Audience Footprint Proof Bar -->
                    <div class="mt-2.5 pt-2.5 border-t border-white/[0.06] flex items-center justify-between text-[11px] text-[#a3a6b0] flex-wrap gap-2">
                        <div class="flex items-center gap-3">
                            <?php if (!empty($selectedLead['instagram_followers'])): ?>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="camera" class="w-3 h-3 text-pink-400"></i>
                                    <span><strong><?= number_format((int)$selectedLead['instagram_followers']) ?></strong> IG Followers (<?= h($selectedLead['instagram_growth'] ?: '+2.5K/mo') ?>)</span>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($selectedLead['linkedin_connections'])): ?>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="users" class="w-3 h-3 text-blue-400"></i>
                                    <span><strong><?= number_format((int)$selectedLead['linkedin_connections']) ?></strong> LI Connections</span>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($selectedLead['youtube_subscribers'])): ?>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="youtube" class="w-3 h-3 text-red-400"></i>
                                    <span><strong><?= number_format((int)$selectedLead['youtube_subscribers']) ?></strong> YT Subscribers</span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if (empty($selectedLead['has_website']) || empty($selectedLead['website'])): ?>
                                <span class="text-amber-400 font-semibold flex items-center gap-1">
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                                    <span>Social Commerce Only (No Website) — Prime Sales Pitch Opportunity!</span>
                                </span>
                            <?php else: ?>
                                <span class="text-emerald-400 flex items-center gap-1 font-mono">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                    <span>DNS & SSL Verified Domain</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Active Nudge Action Alert Banner -->
                <?php if ($isHotActive): ?>
                    <div class="p-3.5 bg-gradient-to-r from-emerald-500/15 via-[#d9b46c]/15 to-teal-500/15 border-b border-emerald-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                            <div>
                                <div class="font-extrabold text-emerald-400 text-xs flex items-center gap-1.5">
                                    <span>⚡ <?= h($dmName) ?> is active on <?= ucfirst($selPlatform) ?> right now!</span>
                                </div>
                                <div class="text-[11px] text-[#f4f1ea] mt-0.5">
                                    Seen <?= h(date('g:i A', strtotime($selectedLead['last_activity_at']))) ?>. Send a quick 2-sentence conversational bump while they are looking at messages!
                                </div>
                            </div>
                        </div>
                        <button type="button" onclick="insertQuickBump()" class="btn-gold-cta px-3 py-1.5 rounded-xl text-black font-extrabold text-xs shadow-md flex items-center gap-1.5 flex-shrink-0">
                            <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                            <span>Send Quick Bump Nudge</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Middle: Chronological Chat Timeline -->
                <div id="chatTimeline" class="chat-timeline-container flex-1 overflow-y-auto p-5 space-y-4 custom-scroll">
                    
                    <!-- Lead Scraped & Discovered System Anchor -->
                    <div class="text-center">
                        <span class="px-3 py-1 rounded-full bg-white/[0.04] border border-white/[0.08] text-[10px] text-[#a3a6b0] font-mono">
                            Client discovered via Q-Score™ engine • <?= date('M j, Y', strtotime($selectedLead['created_at'])) ?>
                        </span>
                    </div>

                    <?php if (empty($history) && empty($selectedLead['last_reply_message'])): ?>
                        <div class="inbox-ready-card p-6 rounded-2xl border text-center max-w-md mx-auto shadow-md">
                            <i data-lucide="sparkles" class="w-6 h-6 text-[#d9b46c] mx-auto mb-2"></i>
                            <h4 class="font-bold text-xs">Ready for Direct Outreach</h4>
                            <p class="text-[11px] mt-1">Select an objection playbook or choose your preferred language below to dispatch the initial high-converting pitch directly to <?= h($dmName) ?>.</p>
                            <button type="button" onclick="loadInitialPitch('en')" class="mt-3 btn-gold-cta px-3.5 py-1.5 rounded-xl text-black font-extrabold text-xs">
                                Pre-fill Initial Pitch
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Real Initial Outbound if recorded or simulated -->
                    <?php foreach ($history as $msg): 
                        $isOutbound = ($msg['direction'] ?? 'outbound') === 'outbound';
                    ?>
                        <div class="flex <?= $isOutbound ? 'justify-end' : 'justify-start' ?>">
                            <div class="max-w-xl rounded-2xl p-4 <?= $isOutbound ? 'chat-bubble-outbound bg-gradient-to-br from-[#161a24] to-[#1c2230] border border-[#d9b46c]/30 text-[#f4f1ea]' : 'chat-bubble-inbound bg-[#161a24] border border-white/[0.12] text-[#f4f1ea]' ?> shadow-md space-y-2">
                                <div class="flex items-center justify-between text-[10px] text-[#a3a6b0] pb-1 border-b border-white/[0.06] chat-bubble-meta">
                                    <span class="font-bold uppercase tracking-wider flex items-center gap-1 <?= $isOutbound ? 'text-[#d9b46c]' : 'text-emerald-400' ?>">
                                        <i data-lucide="<?= $isOutbound ? 'send' : 'message-square' ?>" class="w-3 h-3"></i>
                                        <span><?= $isOutbound ? 'Outbound (' . ucfirst($msg['channel']) . ')' : h($dmName) . ' (' . ucfirst($msg['channel']) . ')' ?></span>
                                    </span>
                                    <span class="font-mono"><?= date('M j, g:i A', strtotime($msg['created_at'])) ?></span>
                                </div>
                                <div class="text-xs leading-relaxed whitespace-pre-wrap font-sans chat-bubble-text">
                                    <?= nl2br(h($msg['message_sent'])) ?>
                                </div>
                                <div class="text-[10px] text-right text-[#a3a6b0] flex items-center justify-end gap-1 font-mono chat-bubble-footer">
                                    <span><?= $isOutbound ? '✓✓ Delivered' : 'Received' ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Inbound Prospect Reply Bubble (If present on lead record) -->
                    <?php if (!empty($selectedLead['last_reply_message'])): ?>
                        <div class="flex justify-start">
                            <div class="max-w-xl rounded-2xl p-4 chat-bubble-inbound bg-gradient-to-br from-emerald-950/40 to-[#11141c] border border-emerald-500/40 text-[#f4f1ea] shadow-lg space-y-2">
                                <div class="flex items-center justify-between text-[10px] pb-1 border-b border-emerald-500/20 chat-bubble-meta">
                                    <span class="font-bold uppercase tracking-wider flex items-center gap-1.5 text-emerald-400">
                                        <i data-lucide="message-square-reply" class="w-3.5 h-3.5"></i>
                                        <span>Incoming Reply via <?= ucfirst($selectedLead['last_reply_channel'] ?: $selPlatform) ?></span>
                                    </span>
                                    <span class="text-[#a3a6b0] font-mono"><?= date('g:i A', strtotime($selectedLead['last_activity_at'] ?: now())) ?></span>
                                </div>
                                <div class="text-xs text-[#f4f1ea] font-medium leading-relaxed chat-bubble-text">
                                    "<?= h($selectedLead['last_reply_message']) ?>"
                                </div>
                                <div class="pt-1 flex items-center justify-between text-[10px] text-emerald-400 chat-bubble-footer">
                                    <span>● High-Intent Prospect Response</span>
                                    <button type="button" onclick="insertPlaybookReply('reply_interested')" class="text-[#d9b46c] hover:underline font-bold">1-Click Auto Reply ↗</button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- Bottom: Playbook Response Chips & Message Composer -->
                <div id="inboxComposerContainer" class="inbox-composer-card p-4 border-t space-y-3">
                    
                    <!-- Objection Handling Quick Chips -->
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#d9b46c] flex items-center gap-1">
                            <i data-lucide="zap" class="w-3 h-3"></i>
                            <span>Quick Response Playbooks</span>
                        </span>
                        
                        <!-- 4-Language Switcher for Replies -->
                        <div class="flex items-center gap-1 text-[10px]">
                            <button type="button" onclick="setReplyLanguage('en')" id="inbox-lang-en" class="inbox-lang-btn active-lang px-2 py-0.5 rounded font-bold bg-[#d9b46c]/20 text-[#f3dca4] border border-[#d9b46c]/40">🇬🇧 English</button>
                            <button type="button" onclick="setReplyLanguage('hinglish')" id="inbox-lang-hinglish" class="inbox-lang-btn px-2 py-0.5 rounded font-medium text-[#a3a6b0] hover:text-white bg-white/[0.04]">🇮🇳 Hinglish</button>
                            <button type="button" onclick="setReplyLanguage('hi')" id="inbox-lang-hi" class="inbox-lang-btn px-2 py-0.5 rounded font-medium text-[#a3a6b0] hover:text-white bg-white/[0.04]">🇮🇳 हिंदी</button>
                            <button type="button" onclick="setReplyLanguage('mr')" id="inbox-lang-mr" class="inbox-lang-btn px-2 py-0.5 rounded font-medium text-[#a3a6b0] hover:text-white bg-white/[0.04]">🚩 मराठी</button>
                        </div>
                    </div>

                    <!-- Playbook Action Chips -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 custom-scroll">
                        <button type="button" onclick="insertPlaybookReply('reply_interested')" class="inbox-playbook-chip px-2.5 py-1 rounded-lg text-[11px] font-semibold flex items-center gap-1.5 transition flex-shrink-0">
                            <i data-lucide="file-text" class="w-3 h-3 text-emerald-400"></i>
                            <span>Send Case Proof & Video</span>
                        </button>
                        <button type="button" onclick="insertPlaybookReply('reply_pricing')" class="inbox-playbook-chip px-2.5 py-1 rounded-lg text-[11px] font-semibold flex items-center gap-1.5 transition flex-shrink-0">
                            <i data-lucide="tag" class="w-3 h-3 text-blue-400"></i>
                            <span>Quote Pricing & ROI Guarantee</span>
                        </button>
                        <button type="button" onclick="insertPlaybookReply('reply_have_agency')" class="inbox-playbook-chip px-2.5 py-1 rounded-lg text-[11px] font-semibold flex items-center gap-1.5 transition flex-shrink-0">
                            <i data-lucide="users" class="w-3 h-3 text-amber-400"></i>
                            <span>"We Have Agency" Co-Pilot Angle</span>
                        </button>
                        <button type="button" onclick="insertPlaybookReply('reply_book_call')" class="inbox-playbook-chip px-2.5 py-1 rounded-lg text-[11px] font-semibold flex items-center gap-1.5 transition flex-shrink-0">
                            <i data-lucide="calendar" class="w-3 h-3 text-purple-400"></i>
                            <span>Book 10-Min Intro Call</span>
                        </button>
                    </div>

                    <!-- Message Textarea Composer -->
                    <div class="relative">
                        <textarea id="inboxComposer" rows="3" placeholder="Type direct reply or select a playbook chip above..." 
                                  class="inbox-composer-textarea w-full rounded-xl p-3 text-xs font-sans leading-relaxed custom-scroll"></textarea>
                    </div>

                    <!-- Dispatch Controls Toolbar -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <label class="text-[11px] text-[#a3a6b0] font-medium">Send Via:</label>
                            <select id="inboxChannelSelect" class="inbox-channel-select rounded-lg px-2.5 py-1 text-xs focus:outline-none focus:border-[#d9b46c]">
                                <option value="whatsapp" <?= $selPlatform === 'whatsapp' ? 'selected' : '' ?>>WhatsApp Direct</option>
                                <option value="linkedin" <?= $selPlatform === 'linkedin' ? 'selected' : '' ?>>LinkedIn InMail / DM</option>
                                <option value="instagram" <?= $selPlatform === 'instagram' ? 'selected' : '' ?>>Instagram DM</option>
                                <option value="facebook" <?= $selPlatform === 'facebook' ? 'selected' : '' ?>>Facebook Messenger</option>
                                <option value="email" <?= $selPlatform === 'email' ? 'selected' : '' ?>>Corporate Email</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <button type="button" onclick="openInExternalApp()" class="inbox-draft-btn px-3 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition" title="Open Native Web App with Draft Pre-filled">
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                <span>Open App Draft</span>
                            </button>
                            <button type="button" id="inboxSendBtn" onclick="sendDirectInboxMessage()" class="btn-gold-cta flex-1 sm:flex-initial px-5 py-2 rounded-xl text-black font-extrabold text-xs shadow-lg flex items-center justify-center gap-2 transition">
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                <span>Send Direct Now</span>
                            </button>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script>
window.__activeLead = <?= $selectedLead ? json_encode([
    'id' => (int)$selectedLead['id'],
    'name' => $selectedLead['business_name'],
    'dm_name' => $selectedLead['decision_maker_name'] ?? 'Decision Maker',
    'dm_first_name' => explode(' ', trim($selectedLead['decision_maker_name'] ?? 'Sir/Ma\'am'))[0],
    'dm_title' => $selectedLead['decision_maker_title'] ?? 'Founder',
    'city' => $selectedLead['city'] ?: 'India',
    'whatsapp' => $selectedLead['whatsapp'] ?: $selectedLead['phone'],
    'phone' => $selectedLead['phone'],
    'email' => $selectedLead['email'],
    'linkedin' => $selectedLead['linkedin_url'],
    'instagram' => $selectedLead['instagram_url'],
    'meta_ads_count' => (int)($selectedLead['meta_ads_count'] ?? 0),
    'has_website' => (int)($selectedLead['has_website'] ?? 1)
]) : 'null' ?>;

let activeLang = 'en';

function setReplyLanguage(lang) {
    activeLang = lang;
    document.querySelectorAll('.inbox-lang-btn').forEach(b => {
        b.classList.remove('active-lang', 'bg-[#d9b46c]/20', 'text-[#f3dca4]', 'border-[#d9b46c]/40', 'font-bold');
        b.classList.add('text-[#a3a6b0]', 'bg-white/[0.04]');
    });
    const el = document.getElementById('inbox-lang-' + lang);
    if (el) {
        el.classList.add('active-lang', 'bg-[#d9b46c]/20', 'text-[#f3dca4]', 'border', 'border-[#d9b46c]/40', 'font-bold');
        el.classList.remove('text-[#a3a6b0]', 'bg-white/[0.04]');
    }
}

function insertQuickBump() {
    if (!window.__activeLead) return;
    const l = window.__activeLead;
    let msg = '';
    if (activeLang === 'hinglish') {
        msg = `Hi ${l.dm_first_name} ji! Dekha aap abhi active hain — quick check kar raha tha if you had 2 minutes to review my previous note on automating ${l.name}'s client inquiries?`;
    } else if (activeLang === 'hi') {
        msg = `नमस्ते ${l.dm_first_name} जी! त्वरित संपर्क कर रहा हूँ — क्या आपने ${l.name} के नए क्लाइंट अधिग्रहण पर मेरा पिछला संदेश देखने का समय पाया?`;
    } else if (activeLang === 'mr') {
        msg = `नमस्कार ${l.dm_first_name} जी! एक त्वरित पाठपुरावा — ${l.name} साठी पाठवलेला क्लायंट वाढीचा प्रस्ताव पाहण्याची संधी मिळाली का?`;
    } else {
        msg = `Hi ${l.dm_first_name}, saw you're active right now! Quick bump to see if you had 2 minutes to review my previous note regarding client acquisition for ${l.name}?`;
    }
    const composer = document.getElementById('inboxComposer');
    if (composer) {
        composer.value = msg;
        composer.focus();
    }
}

function insertPlaybookReply(type) {
    if (!window.__activeLead) return;
    const l = window.__activeLead;
    const composer = document.getElementById('inboxComposer');
    if (!composer) return;

    const playbooks = {
        'reply_interested': {
            'en': `Great ${l.dm_first_name}! Here is a private 2-minute video teardown showing our conversion workflow for peer brands: [Demo Link]. Do you have 10 minutes this Thursday at 4 PM or Friday at 11 AM for a quick intro call?`,
            'hinglish': `Super ${l.dm_first_name} ji! Yeh raha hamara private 2-minute workflow breakdown link: [Demo Link]. Is Thursday 4 PM ya Friday 11 AM par 10-minute ki quick call par connect kar sakte hain?`,
            'hi': `बहुत बढ़िया ${l.dm_first_name} जी! यह रहा हमारा 2 मिनट का वीडियो वॉकथ्रू लिंक: [डेमो लिंक]। क्या इस गुरुवार शाम 4 बजे या शुक्रवार सुबह 11 बजे 10 मिनट की परिचयात्मक कॉल पर बात कर सकते हैं?`,
            'mr': `उत्तम ${l.dm_first_name} जी! आमचा २ मिनिटांचा सविस्तर व्हिडिओ डेमो लिंक येथे आहे: [डेमो लिंक]. या गुरुवारी दुपारी ४ वाजता किंवा शुक्रवारी सकाळी ११ वाजता १० मिनिटांच्या परिचयात्मक कॉलसाठी वेळ देऊ शकाल का?`
        },
        'reply_pricing': {
            'en': `Our tailored client engines typically range from ₹49,999 to ₹1,50,000, fully linked to ROI and sales volume. We back every deployment with a 7-day live conversion guarantee. Would you be open to a 10-minute discovery call to see if the ROI aligns for ${l.name}?`,
            'hinglish': `Hamara custom growth setup ₹49,999 se ₹1,50,000 ke beech rehta hai, directly linked to ROI. Hum 7-day live pilot conversion guarantee bhi dete hain. Kya 10-minute ki call par evaluate kar sakte hain?`,
            'hi': `हमारा अनुकूलित समाधान ₹49,999 से ₹1,50,000 के दायरे में आता है, जो पूरी तरह आरओआई पर केंद्रित है। हम 7 दिनों की लाइव गारंटी प्रदान करते हैं। क्या 10 मिनट की कॉल पर बात संभव है?`,
            'mr': `आमची सानुकूल प्रणाली ₹४९,९९९ ते ₹१,५०,००० दरम्यान असते, जी थेट व्यवसायाच्या नफ्याशी जोडलेली आहे. आम्ही ७ दिवसांची हमी देतो. १० मिनिटांच्या कॉलवर चर्चा करायला आवडेल का?`
        },
        'reply_have_agency': {
            'en': `Completely respect that ${l.dm_first_name}. We don't replace your in-house team or current agency — our proprietary system acts as their lead capture co-pilot, handing them pre-qualified warm buyers. Open to seeing a 90-second integration teardown?`,
            'hinglish': `Bilkul samajh sakta hoon ${l.dm_first_name} ji. Hum aapki current agency ko replace nahi karte — balki hamara system unke liye 24/7 warm lead capture co-pilot ban jata hai. Kya 90-second ka setup dekhna chahenge?`,
            'hi': `बिलकुल समझ सकता हूँ ${l.dm_first_name} जी। हम आपकी मौजूदा एजेंसी की जगह नहीं लेते, बल्कि उनके लिए 24/7 योग्य लीड कैप्चर को-पायलट के रूप में काम करते हैं। क्या 90 सेकंड का फ्रेमवर्क देखना चाहेंगे?`,
            'mr': `पूर्णपणे समजतो ${l.dm_first_name} जी. आम्ही आपल्या सध्याच्या एजन्सीची जागा घेत नाही, तर त्यांच्यासाठी २४/७ थेट ग्राहकांची नोंदणी करणारा सहाय्यक म्हणून काम करतो. ९० सेकंदांचा डेमो पाहण्यास तयार आहात का?`
        },
        'reply_book_call': {
            'en': `Perfect! You can select a convenient 10-minute slot on my direct calendar here: [Your Calendar Link]. Looking forward to speaking with you, ${l.dm_first_name}!`,
            'hinglish': `Zabardast! Aap yahan direct meri calendar link se 10-minute ka convenient slot choose kar sakte hain: [Calendar Link]. Baat karne ke liye excited hoon, ${l.dm_first_name} ji!`,
            'hi': `उत्तम! आप मेरे सीधे कैलेंडर लिंक से अपनी सुविधा अनुसार 10 मिनट का समय चुन सकते हैं: [कैलेंडर लिंक]। आपसे बात करने के लिए उत्सुक हूँ!`,
            'mr': `नक्कीच! आपण थेट माझ्या कॅलेंडर लिंकवरून आपल्या सोयीनुसार १० मिनिटांची वेळ निवडू शकता: [कॅलेंडर लिंक]. आपल्याशी बोलण्यासाठी उत्सुक आहे!`
        }
    };

    const chosen = playbooks[type] && playbooks[type][activeLang] ? playbooks[type][activeLang] : playbooks[type]['en'];
    composer.value = chosen;
    composer.focus();
}

function loadInitialPitch(lang) {
    if (!window.__activeLead) return;
    const l = window.__activeLead;
    const composer = document.getElementById('inboxComposer');
    if (!composer) return;

    let text = `Hi ${l.dm_first_name},\n\nSaw your active presence scaling ${l.name} in ${l.city}.\n\nQuick question: Are you looking to onboard 15-20 more qualified high-ticket clients this quarter, or is your team already at max capacity?\n\nWe recently deployed a dedicated lead conversion workflow for a peer brand that delivered 18 qualified buyers last month with zero ad waste.\n\nWorth a 60-second video walkthrough, or completely off base?`;
    composer.value = text;
    composer.focus();
}

async function sendDirectInboxMessage() {
    if (!window.__activeLead) return;
    const composer = document.getElementById('inboxComposer');
    const msg = (composer ? composer.value : '').trim();
    if (!msg) {
        showToast('Please type a message to send.', 'error');
        return;
    }

    const channel = document.getElementById('inboxChannelSelect').value;
    const btn = document.getElementById('inboxSendBtn');
    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin w-3.5 h-3.5 border-2 border-black border-t-transparent rounded-full"></span><span>Sending In-Platform...</span>`;

    try {
        const res = await fetch('/api.php?action=send_inbox_message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                lead_id: window.__activeLead.id,
                channel: channel,
                message: msg,
                touch_type: 'inbox_direct'
            })
        });
        const data = await res.json();
        if (data.status === 'success') {
            const successNote = data.cloud_sent 
                ? 'Delivered directly via Meta WhatsApp Cloud API!' 
                : 'Message sent and logged directly in Unified Inbox!';
            showToast(successNote, 'success');
            
            // Append message bubble to timeline
            const timeline = document.getElementById('chatTimeline');
            if (timeline) {
                const nowStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const bubble = document.createElement('div');
                bubble.className = 'flex justify-end';
                const deliveryBadge = data.cloud_sent ? '✓✓ Delivered via WhatsApp Cloud API' : '✓ Delivered (In-Platform)';
                bubble.innerHTML = `
                    <div class="max-w-xl rounded-2xl p-4 chat-bubble-outbound bg-gradient-to-br from-[#161a24] to-[#1c2230] border border-[#d9b46c]/30 text-[#f4f1ea] shadow-md space-y-2">
                        <div class="flex items-center justify-between text-[10px] text-[#a3a6b0] pb-1 border-b border-white/[0.06] chat-bubble-meta">
                            <span class="font-bold uppercase tracking-wider flex items-center gap-1 text-[#d9b46c]">
                                <i data-lucide="send" class="w-3 h-3"></i>
                                <span>Outbound (${channel.toUpperCase()})</span>
                            </span>
                            <span class="font-mono">${nowStr}</span>
                        </div>
                        <div class="text-xs leading-relaxed whitespace-pre-wrap font-sans chat-bubble-text">${escapeHtml(msg).replace(/\n/g, '<br>')}</div>
                        <div class="text-[10px] text-right text-emerald-400 flex items-center justify-end gap-1 font-mono chat-bubble-footer">
                            <span>${deliveryBadge}</span>
                        </div>
                    </div>
                `;
                timeline.appendChild(bubble);
                timeline.scrollTop = timeline.scrollHeight;
                lucide.createIcons();
            }
            composer.value = '';
        } else {
            showToast(data.message || 'Dispatch failed.', 'error');
        }
    } catch (e) {
        showToast('Network error while dispatching message.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="send" class="w-3.5 h-3.5"></i><span>Send Direct Now</span>`;
        lucide.createIcons();
    }
}

async function refreshTimeline() {
    if (!window.__activeLead) return;
    try {
        const res = await fetch(`/api.php?action=get_inbox_messages&lead_id=${window.__activeLead.id}`);
        const data = await res.json();
        if (data.status === 'success' && data.messages) {
            renderTimelineMessages(data.messages);
            showToast('Chat timeline synchronized!', 'success');
        }
    } catch(e) {}
}

function renderTimelineMessages(msgs) {
    const timeline = document.getElementById('chatTimeline');
    if (!timeline) return;
    timeline.innerHTML = msgs.map(m => {
        const isInbound = (m.direction === 'inbound');
        const timeStr = m.created_at ? new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
        if (isInbound) {
            return `
                <div class="flex justify-start">
                    <div class="max-w-xl rounded-2xl p-4 bg-white/[0.05] border border-white/[0.1] text-[#f4f1ea] space-y-2">
                        <div class="flex items-center justify-between text-[10px] text-[#a3a6b0] pb-1 border-b border-white/[0.06]">
                            <span class="font-bold text-emerald-400 flex items-center gap-1">
                                <i data-lucide="inbox" class="w-3 h-3"></i>
                                <span>Inbound Client (${(m.channel || 'whatsapp').toUpperCase()})</span>
                            </span>
                            <span class="font-mono">${timeStr}</span>
                        </div>
                        <div class="text-xs leading-relaxed whitespace-pre-wrap">${escapeHtml(m.message_sent)}</div>
                    </div>
                </div>
            `;
        } else {
            return `
                <div class="flex justify-end">
                    <div class="max-w-xl rounded-2xl p-4 chat-bubble-outbound bg-gradient-to-br from-[#161a24] to-[#1c2230] border border-[#d9b46c]/30 text-[#f4f1ea] shadow-md space-y-2">
                        <div class="flex items-center justify-between text-[10px] text-[#a3a6b0] pb-1 border-b border-white/[0.06] chat-bubble-meta">
                            <span class="font-bold uppercase tracking-wider flex items-center gap-1 text-[#d9b46c]">
                                <i data-lucide="send" class="w-3 h-3"></i>
                                <span>Outbound (${(m.channel || 'whatsapp').toUpperCase()})</span>
                            </span>
                            <span class="font-mono">${timeStr}</span>
                        </div>
                        <div class="text-xs leading-relaxed whitespace-pre-wrap font-sans chat-bubble-text">${escapeHtml(m.message_sent).replace(/\n/g, '<br>')}</div>
                        <div class="text-[10px] text-right text-emerald-400 flex items-center justify-end gap-1 font-mono chat-bubble-footer">
                            <span>✓✓ Delivered</span>
                        </div>
                    </div>
                </div>
            `;
        }
    }).join('');
    timeline.scrollTop = timeline.scrollHeight;
    lucide.createIcons();
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
    });
}

function openInExternalApp() {
    if (!window.__activeLead) return;
    const l = window.__activeLead;
    const channel = document.getElementById('inboxChannelSelect').value;
    const composer = document.getElementById('inboxComposer');
    const msg = encodeURIComponent(composer ? composer.value : '');
    const cleanPhone = (l.whatsapp || l.phone || '').replace(/[^\d]/g, '');

    if (channel === 'whatsapp' && cleanPhone) {
        window.open(`https://wa.me/${cleanPhone}?text=${msg}`, '_blank');
    } else if (channel === 'linkedin' && l.linkedin) {
        window.open(l.linkedin, '_blank');
    } else if (channel === 'instagram' && l.instagram) {
        window.open(l.instagram, '_blank');
    } else if (channel === 'email' && l.email) {
        window.open(`mailto:${l.email}?subject=Collaboration%20with%20${encodeURIComponent(l.name)}&body=${msg}`, '_blank');
    } else {
        showToast('Direct channel URL not configured for this lead.', 'error');
    }
}

function selectThread(leadId) {
    window.location.href = `/inbox.php?lead_id=${leadId}`;
}

function filterChannel(channel) {
    document.querySelectorAll('.channel-tab-btn').forEach(btn => {
        btn.classList.remove('active-tab', 'bg-[#d9b46c]', 'text-black', 'shadow-sm', 'font-bold');
        btn.classList.add('text-[#a3a6b0]', 'font-semibold');
    });
    const activeBtn = document.getElementById('tab-' + channel);
    if (activeBtn) {
        activeBtn.classList.add('active-tab', 'bg-[#d9b46c]', 'text-black', 'shadow-sm', 'font-bold');
        activeBtn.classList.remove('text-[#a3a6b0]', 'font-semibold');
    }

    const cards = document.querySelectorAll('.thread-card');
    cards.forEach(card => {
        const c = card.getAttribute('data-channel');
        if (channel === 'all' || c === channel) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
}

function filterPresence(filter) {
    document.querySelectorAll('.presence-btn').forEach(b => {
        b.classList.remove('active-presence', 'bg-[#d9b46c]/20', 'text-[#f3dca4]', 'border-[#d9b46c]/40', 'font-bold');
        b.classList.add('text-[#a3a6b0]', 'bg-white/[0.03]');
    });
    const el = document.getElementById('presence-' + filter);
    if (el) {
        el.classList.add('active-presence', 'bg-[#d9b46c]/20', 'text-[#f3dca4]', 'border-[#d9b46c]/40', 'font-bold');
        el.classList.remove('text-[#a3a6b0]', 'bg-white/[0.03]');
    }

    const cards = document.querySelectorAll('.thread-card');
    cards.forEach(card => {
        const isHot = card.getAttribute('data-active-now') === '1';
        const hasReply = card.getAttribute('data-has-reply') === '1';

        if (filter === 'all') {
            card.classList.remove('hidden');
        } else if (filter === 'active') {
            card.classList.toggle('hidden', !isHot);
        } else if (filter === 'replied') {
            card.classList.toggle('hidden', !hasReply);
        }
    });
}

function handleThreadSearch() {
    const q = (document.getElementById('threadSearch').value || '').toLowerCase().trim();
    const cards = document.querySelectorAll('.thread-card');
    cards.forEach(card => {
        const txt = card.getAttribute('data-search-text') || '';
        card.classList.toggle('hidden', q.length > 0 && !txt.includes(q));
    });
}
</script>

<?php
$content = ob_get_clean();
render_layout('Unified Inbox', 'inbox', $content, $user);
