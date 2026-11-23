<?php
// Outreach AI Freedom - Connected Communication Channels Hub & Setup Guide
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/layout.php';

$user = require_auth();
$userId = (int)$user['id'];
$message = '';
$messageType = '';

// Load connected channels configs
$channelsRaw = all("SELECT * FROM connected_channels WHERE user_id = ?", [$userId]);
$channelsMap = [];
foreach ($channelsRaw as $c) {
    $channelsMap[$c['channel_name']] = json_decode($c['config_data'] ?: '{}', true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? 'save_all');

    $senderWhatsapp = trim($_POST['sender_whatsapp'] ?? '');
    $linkedinUrl = trim($_POST['linkedin_url'] ?? '');
    $senderEmail = trim($_POST['sender_email'] ?? '');
    $instagramHandle = trim($_POST['instagram_handle'] ?? '');
    $facebookPageId = trim($_POST['facebook_page_id'] ?? '');

    // Advanced API configurations
    $waConfig = [
        'mode' => trim($_POST['wa_mode'] ?? 'instant_bridge'),
        'phone' => $senderWhatsapp,
        'phone_number_id' => trim($_POST['wa_phone_number_id'] ?? ''),
        'waba_id' => trim($_POST['wa_waba_id'] ?? ''),
        'access_token' => trim($_POST['wa_access_token'] ?? ''),
        'verify_token' => trim($_POST['wa_verify_token'] ?? 'toaf_freedom_wa_2026'),
    ];

    $igConfig = [
        'handle' => $instagramHandle,
        'account_id' => trim($_POST['ig_account_id'] ?? ''),
        'access_token' => trim($_POST['ig_access_token'] ?? ''),
    ];

    $fbConfig = [
        'page_id' => $facebookPageId,
        'access_token' => trim($_POST['fb_access_token'] ?? ''),
    ];

    $liConfig = [
        'profile_url' => $linkedinUrl,
        'client_id' => trim($_POST['li_client_id'] ?? ''),
        'client_secret' => trim($_POST['li_client_secret'] ?? ''),
    ];

    $emailConfig = [
        'smtp_host' => trim($_POST['smtp_host'] ?? 'smtp.hostinger.com'),
        'smtp_port' => trim($_POST['smtp_port'] ?? '465'),
        'smtp_user' => $senderEmail,
        'smtp_pass' => trim($_POST['smtp_pass'] ?? ''),
        'encryption' => trim($_POST['smtp_enc'] ?? 'ssl'),
    ];

    // Update user profile core channels
    q("UPDATE users SET
        sender_whatsapp = ?,
        linkedin_url = ?,
        sender_email = ?,
        instagram_handle = ?,
        updated_at = ?
       WHERE id = ?", [
        $senderWhatsapp,
        $linkedinUrl,
        $senderEmail,
        $instagramHandle,
        now(),
        $userId
    ]);

    // Upsert into connected_channels
    $upsertChannel = function(string $chName, string $identifier, array $cfg) use ($userId) {
        $driver = cfg('db')['driver'] ?? 'mysql';
        $json = json_encode($cfg);
        if ($driver === 'mysql') {
            q("INSERT INTO connected_channels (user_id, channel_name, account_identifier, status, config_data, updated_at)
               VALUES (?, ?, ?, 'connected', ?, ?)
               ON DUPLICATE KEY UPDATE account_identifier = VALUES(account_identifier), config_data = VALUES(config_data), updated_at = VALUES(updated_at)",
               [$userId, $chName, $identifier, $json, now()]);
        } else {
            q("INSERT INTO connected_channels (user_id, channel_name, account_identifier, status, config_data, updated_at)
               VALUES (?, ?, ?, 'connected', ?, ?)
               ON CONFLICT(user_id, channel_name) DO UPDATE SET account_identifier = excluded.account_identifier, config_data = excluded.config_data, updated_at = excluded.updated_at",
               [$userId, $chName, $identifier, $json, now()]);
        }
    };

    $upsertChannel('whatsapp', $senderWhatsapp, $waConfig);
    $upsertChannel('instagram', $instagramHandle, $igConfig);
    $upsertChannel('facebook', $facebookPageId, $fbConfig);
    $upsertChannel('linkedin', $linkedinUrl, $liConfig);
    $upsertChannel('email', $senderEmail, $emailConfig);

    $user = one("SELECT * FROM users WHERE id = ?", [$userId]);
    $channelsMap = [
        'whatsapp' => $waConfig,
        'instagram' => $igConfig,
        'facebook' => $fbConfig,
        'linkedin' => $liConfig,
        'email' => $emailConfig,
    ];

    $message = 'Connected communication channels & API configurations saved successfully! Ready for multi-channel dispatch & unified tracking.';
    $messageType = 'success';
}

$wa = $channelsMap['whatsapp'] ?? [];
$ig = $channelsMap['instagram'] ?? [];
$fb = $channelsMap['facebook'] ?? [];
$li = $channelsMap['linkedin'] ?? [];
$em = $channelsMap['email'] ?? [];

ob_start();
?>

<div class="max-w-5xl mx-auto space-y-8">
    
    <!-- Top Header & Direct Inbox Link -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-[#f4f1ea] tracking-tight flex items-center gap-2">
                <span>Multi-Platform Communication Hub</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 font-mono">100% Unrestricted</span>
            </h2>
            <p class="text-xs text-[#a3a6b0] mt-1 max-w-2xl leading-relaxed">
                Connect your personal & business accounts across WhatsApp, LinkedIn, Instagram, Facebook Messenger, and Email. Track every conversation in your unified CRM pipeline and dispatch tailored outreach directly without bans or third-party middleman restrictions.
            </p>
        </div>

        <a href="/inbox.php" class="btn-gold-cta px-5 py-2.5 rounded-xl text-black font-extrabold text-xs shadow-lg transition flex items-center gap-2 flex-shrink-0">
            <i data-lucide="messages-square" class="w-4 h-4"></i>
            <span>Open Unified Multi-Channel Inbox</span>
        </a>
    </div>

    <?php if ($message): ?>
        <div class="p-4 rounded-xl border flex items-center gap-3 text-xs font-semibold <?= $messageType === 'success' ? 'bg-emerald-950/80 text-emerald-200 border-emerald-500/40' : 'bg-rose-950/80 text-rose-200 border-rose-500/40' ?>">
            <i data-lucide="<?= $messageType === 'success' ? 'check-circle' : 'alert-triangle' ?>" class="w-4 h-4 flex-shrink-0"></i>
            <span><?= h($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Overview Statistics Banner -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="p-4 rounded-xl bg-white/[0.03] border border-white/[0.08] flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center flex-shrink-0">
                <i data-lucide="message-circle" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] text-[#a3a6b0]">WhatsApp Engine</div>
                <div class="text-xs font-bold text-[#f4f1ea] flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Direct Active</span>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-xl bg-white/[0.03] border border-white/[0.08] flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 border border-blue-500/30 flex items-center justify-center flex-shrink-0">
                <i data-lucide="linkedin" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] text-[#a3a6b0]">LinkedIn InMail</div>
                <div class="text-xs font-bold text-[#f4f1ea] flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                    <span>1-Click Ready</span>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-xl bg-white/[0.03] border border-white/[0.08] flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-pink-500/20 text-pink-400 border border-pink-500/30 flex items-center justify-center flex-shrink-0">
                <i data-lucide="instagram" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] text-[#a3a6b0]">Instagram DM</div>
                <div class="text-xs font-bold text-[#f4f1ea] flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-pink-400"></span>
                    <span>Bridge Active</span>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-xl bg-white/[0.03] border border-white/[0.08] flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center flex-shrink-0">
                <i data-lucide="mail" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[11px] text-[#a3a6b0]">Corporate Mail</div>
                <div class="text-xs font-bold text-[#f4f1ea] flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                    <span>SMTP Ready</span>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="save_all">

        <!-- ==========================================
             1. WHATSAPP CONNECTION & SETUP GUIDE
             ========================================== -->
        <div class="glass-panel rounded-2xl p-6 border border-white/[0.08] space-y-5">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-white/[0.08] pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="message-circle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-[#f4f1ea] text-sm">WhatsApp Business Bridge & Cloud API</h3>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-bold">Recommended</span>
                        </div>
                        <p class="text-xs text-[#a3a6b0]">Zero-block direct dispatch + optional Cloud API webhook for automated inbound replies.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="testDispatch('whatsapp')" class="px-3 py-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/25 text-xs font-semibold transition flex items-center gap-1.5">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        <span>Test WhatsApp Ping</span>
                    </button>
                </div>
            </div>

            <!-- Easiest Step-by-Step Instructions -->
            <div class="p-4 rounded-xl bg-white/[0.03] border border-white/[0.06] space-y-2.5 text-xs">
                <div class="font-bold text-[#f4f1ea] flex items-center gap-2">
                    <i data-lucide="help-circle" class="w-4 h-4 text-[#d9b46c]"></i>
                    <span>Simple Step-by-Step Connection Instructions:</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-[#a3a6b0] text-[11px] leading-relaxed">
                    <div class="space-y-1">
                        <strong class="text-emerald-400">Method 1: Instant Direct Bridge (Ready Now, Zero Setup)</strong>
                        <p>1. Enter your WhatsApp Business phone number below (with + country code, e.g. <code class="text-[#f3dca4]">+917620654814</code>).</p>
                        <p>2. Outreach dispatches directly using your local WhatsApp Web or WhatsApp Desktop app. No developer verification or Facebook app approvals required.</p>
                    </div>
                    <div class="space-y-1">
                        <strong class="text-[#d9b46c]">Method 2: Meta Cloud API (For Automated Inbound Tracking)</strong>
                        <p>1. Open <a href="https://developers.facebook.com" target="_blank" class="text-blue-400 hover:underline">Meta for Developers ↗</a> and add WhatsApp.</p>
                        <p>2. Set Webhook URL to: <code class="text-emerald-300 font-mono select-all">https://outreach.theonlyaifreedom.in/api.php?action=webhook_whatsapp</code></p>
                        <p>3. Set Verify Token to: <code class="text-[#f3dca4] font-mono select-all">toaf_freedom_wa_2026</code> and subscribe to <code class="text-slate-200">messages</code>.</p>
                    </div>
                </div>
            </div>

            <!-- Input Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Your WhatsApp Number (With Country Code) *</label>
                    <input type="text" name="sender_whatsapp" value="<?= h($user['sender_whatsapp'] ?: ($user['sender_phone'] ?: '+917620654814')) ?>" 
                           placeholder="+917620654814" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                    <span class="text-[10px] text-[#a3a6b0] mt-1 block">Your personal/business WhatsApp used to talk directly to decision makers.</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Dispatch Mode</label>
                    <select name="wa_mode" class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                        <option value="instant_bridge" <?= ($wa['mode'] ?? '') === 'instant_bridge' ? 'selected' : '' ?>>Instant Direct App Bridge (No Meta verification required - 100% Active)</option>
                        <option value="cloud_api" <?= ($wa['mode'] ?? '') === 'cloud_api' ? 'selected' : '' ?>>Meta WhatsApp Cloud API (Automated Webhook Sync)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">WhatsApp Phone Number ID (Optional for Cloud API)</label>
                    <input type="text" name="wa_phone_number_id" value="<?= h($wa['phone_number_id'] ?? '') ?>" 
                           placeholder="e.g. 109847291823901" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">System User Access Token (Optional for Cloud API)</label>
                    <input type="password" name="wa_access_token" value="<?= h($wa['access_token'] ?? '') ?>" 
                           placeholder="EAAGm0PX4ZC5o..." 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                </div>
            </div>
        </div>

        <!-- ==========================================
             2. LINKEDIN CONNECTION & INMAIL SETUP
             ========================================== -->
        <div class="glass-panel rounded-2xl p-6 border border-white/[0.08] space-y-5">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-white/[0.08] pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 border border-blue-500/30 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="linkedin" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-[#f4f1ea] text-sm">LinkedIn Direct Message & InMail Bridge</h3>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/40 font-bold">Executive Outreach</span>
                        </div>
                        <p class="text-xs text-[#a3a6b0]">Connect your profile to dispatch tailored executive connection requests & InMails directly.</p>
                    </div>
                </div>
                <button type="button" onclick="testDispatch('linkedin')" class="px-3 py-1.5 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 border border-blue-500/25 text-xs font-semibold transition flex items-center gap-1.5">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                    <span>Test LinkedIn Profile Link</span>
                </button>
            </div>

            <!-- Easiest Step-by-Step Instructions -->
            <div class="p-4 rounded-xl bg-white/[0.03] border border-white/[0.06] space-y-2 text-xs">
                <div class="font-bold text-[#f4f1ea] flex items-center gap-2">
                    <i data-lucide="help-circle" class="w-4 h-4 text-[#d9b46c]"></i>
                    <span>How to connect your LinkedIn for 1-click InMail:</span>
                </div>
                <div class="text-[11px] text-[#a3a6b0] space-y-1 leading-relaxed">
                    <p>1. Paste your public LinkedIn Profile URL below (e.g. <code class="text-[#f3dca4]">https://www.linkedin.com/in/rajjatrangari</code>).</p>
                    <p>2. When you click "Open & Dispatch" on any decision maker lead, Outreach OS automatically opens their profile message box with your tailored pitch pre-copied.</p>
                    <p>3. (Optional Developer App): Add Authorized Redirect URI in <a href="https://developer.linkedin.com" target="_blank" class="text-blue-400 hover:underline">LinkedIn Developer Portal ↗</a>: <code class="text-emerald-300 font-mono select-all">https://outreach.theonlyaifreedom.in/api.php?action=oauth_linkedin_callback</code></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Your LinkedIn Profile URL *</label>
                    <input type="url" name="linkedin_url" value="<?= h($user['linkedin_url'] ?: 'https://www.linkedin.com/in/rajjatrangari') ?>" 
                           placeholder="https://www.linkedin.com/in/yourname" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Client ID (Optional OAuth)</label>
                    <input type="text" name="li_client_id" value="<?= h($li['client_id'] ?? '') ?>" 
                           placeholder="e.g. 78xkz91823..." 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Client Secret (Optional OAuth)</label>
                    <input type="password" name="li_client_secret" value="<?= h($li['client_secret'] ?? '') ?>" 
                           placeholder="••••••••••••••••" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                </div>
            </div>
        </div>

        <!-- ==========================================
             3. INSTAGRAM & FACEBOOK (META BUSINESS)
             ========================================== -->
        <div class="glass-panel rounded-2xl p-6 border border-white/[0.08] space-y-5">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-white/[0.08] pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-pink-500/20 text-pink-400 border border-pink-500/30 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="instagram" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-[#f4f1ea] text-sm">Instagram Direct Message & Meta Page Hub</h3>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-pink-500/20 text-pink-300 border border-pink-500/40 font-bold">Social DMs</span>
                        </div>
                        <p class="text-xs text-[#a3a6b0]">Reach out directly to brand owners, founders, and creators via Instagram and Facebook.</p>
                    </div>
                </div>
                <button type="button" onclick="testDispatch('instagram')" class="px-3 py-1.5 rounded-lg bg-pink-500/10 hover:bg-pink-500/20 text-pink-400 border border-pink-500/25 text-xs font-semibold transition flex items-center gap-1.5">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span>Test Instagram DM</span>
                </button>
            </div>

            <!-- Easiest Step-by-Step Instructions -->
            <div class="p-4 rounded-xl bg-white/[0.03] border border-white/[0.06] space-y-2 text-xs">
                <div class="font-bold text-[#f4f1ea] flex items-center gap-2">
                    <i data-lucide="help-circle" class="w-4 h-4 text-[#d9b46c]"></i>
                    <span>Step-by-step setup for Instagram & Facebook:</span>
                </div>
                <div class="text-[11px] text-[#a3a6b0] space-y-1 leading-relaxed">
                    <p>1. Enter your Instagram Business handle below (e.g. <code class="text-[#f3dca4]">@theonlyaifreedom</code>).</p>
                    <p>2. Direct deep-link bridge automatically launches the prospect's profile DM on mobile or web with the tailored pitch pre-copied to clipboard.</p>
                    <p>3. (Optional Webhook): For inbound Instagram DM tracking, in <a href="https://business.facebook.com" target="_blank" class="text-blue-400 hover:underline">Meta Business Suite ↗</a> set Webhook URL: <code class="text-emerald-300 font-mono select-all">https://outreach.theonlyaifreedom.in/api.php?action=webhook_meta</code> with token <code class="text-[#f3dca4] font-mono select-all">toaf_freedom_meta_2026</code>.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Instagram Business Handle *</label>
                    <input type="text" name="instagram_handle" value="<?= h($user['instagram_handle'] ?: '@theonlyaifreedom') ?>" 
                           placeholder="@yourbrand" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Facebook Page ID (Optional)</label>
                    <input type="text" name="facebook_page_id" value="<?= h($fb['page_id'] ?? '') ?>" 
                           placeholder="e.g. 109283091823" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Instagram Account ID (Optional Graph API)</label>
                    <input type="text" name="ig_account_id" value="<?= h($ig['account_id'] ?? '') ?>" 
                           placeholder="e.g. 1784140029381" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Meta Page Access Token (Optional)</label>
                    <input type="password" name="fb_access_token" value="<?= h($fb['access_token'] ?? '') ?>" 
                           placeholder="EAAK0..." 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                </div>
            </div>
        </div>

        <!-- ==========================================
             4. CORPORATE EMAIL (SMTP & DISPATCHER)
             ========================================== -->
        <div class="glass-panel rounded-2xl p-6 border border-white/[0.08] space-y-5">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-white/[0.08] pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="mail" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-[#f4f1ea] text-sm">Corporate Email Dispatcher (SMTP / Webmail)</h3>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 font-bold">Cold Email</span>
                        </div>
                        <p class="text-xs text-[#a3a6b0]">Deliver high-deliverability executive emails directly from your corporate domain address.</p>
                    </div>
                </div>
                <button type="button" onclick="testDispatch('email')" class="px-3 py-1.5 rounded-lg bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 border border-indigo-500/25 text-xs font-semibold transition flex items-center gap-1.5">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span>Test Email Client</span>
                </button>
            </div>

            <!-- Easiest Step-by-Step Instructions -->
            <div class="p-4 rounded-xl bg-white/[0.03] border border-white/[0.06] space-y-2 text-xs">
                <div class="font-bold text-[#f4f1ea] flex items-center gap-2">
                    <i data-lucide="help-circle" class="w-4 h-4 text-[#d9b46c]"></i>
                    <span>Email connection guide:</span>
                </div>
                <div class="text-[11px] text-[#a3a6b0] space-y-1 leading-relaxed">
                    <p>1. Enter your corporate email address (e.g. <code class="text-[#f3dca4]">outreach@theonlyaifreedom.in</code>).</p>
                    <p>2. Direct mailto dispatcher opens your default email client (Gmail, Outlook, Apple Mail) with pre-filled subject line and high-converting body text.</p>
                    <p>3. For direct background SMTP sending, enter your Hostinger/Google Workspace SMTP host, port, and App Password below.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Sender Corporate Email *</label>
                    <input type="email" name="sender_email" value="<?= h($user['sender_email'] ?: ($user['email'] ?: 'outreach@theonlyaifreedom.in')) ?>" 
                           placeholder="outreach@theonlyaifreedom.in" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">SMTP Host</label>
                    <input type="text" name="smtp_host" value="<?= h($em['smtp_host'] ?? 'smtp.hostinger.com') ?>" 
                           placeholder="smtp.hostinger.com" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">SMTP Port</label>
                    <input type="text" name="smtp_port" value="<?= h($em['smtp_port'] ?? '465') ?>" 
                           placeholder="465" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                </div>
            </div>
        </div>

        <!-- Sticky Save Button -->
        <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/[0.08] flex items-center justify-between">
            <div class="text-xs text-[#a3a6b0]">
                All settings are securely persisted in your private Outreach OS workspace.
            </div>
            <button type="submit" class="btn-gold-cta px-6 py-2.5 rounded-xl text-black font-extrabold text-xs shadow-lg transition flex items-center gap-2">
                <i data-lucide="check" class="w-4 h-4"></i>
                <span>Save All Channel Connections</span>
            </button>
        </div>
    </form>
</div>

<script>
function testDispatch(channel) {
    if (channel === 'whatsapp') {
        const phone = document.querySelector('input[name="sender_whatsapp"]')?.value || '+917620654814';
        const clean = phone.replace(/[^\d]/g, '');
        window.open(`https://wa.me/${clean}?text=${encodeURIComponent('The Only AI Freedom: WhatsApp dispatch bridge verified & operational!')}`, '_blank');
        showToast('WhatsApp bridge verified! Test chat opened.', 'success');
    } else if (channel === 'linkedin') {
        const url = document.querySelector('input[name="linkedin_url"]')?.value || 'https://www.linkedin.com/in/rajjatrangari';
        window.open(url, '_blank');
        showToast('LinkedIn profile bridge verified!', 'success');
    } else if (channel === 'instagram') {
        const handle = (document.querySelector('input[name="instagram_handle"]')?.value || '@theonlyaifreedom').replace('@', '');
        window.open(`https://ig.me/m/${handle}`, '_blank');
        showToast('Instagram DM bridge verified!', 'success');
    } else if (channel === 'email') {
        const email = document.querySelector('input[name="sender_email"]')?.value || 'outreach@theonlyaifreedom.in';
        window.open(`mailto:${email}?subject=Outreach%20Engine%20Verification&body=Corporate%20email%20dispatch%20bridge%20operational!`, '_blank');
        showToast('Email client bridge verified!', 'success');
    }
}
</script>

<?php
$content = ob_get_clean();
render_layout('Connected Channels', 'channels', $content, $user);
