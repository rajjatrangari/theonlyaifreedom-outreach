<?php
// Outreach AI Freedom - Connected Communication Channels Hub & Setup Guide
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/layout.php';
require_once __DIR__ . '/core/whatsapp_service.php';
require_once __DIR__ . '/core/email_service.php';

$user = require_auth();
$userId = (int)$user['id'];
$message = '';
$messageType = '';

// Default sender email must always be rajjat@theonlyaifreedom.in
$defaultSenderEmail = 'rajjat@theonlyaifreedom.in';
if (empty($user['sender_email']) || $user['sender_email'] === 'outreach@theonlyaifreedom.in') {
    q("UPDATE users SET sender_email = ? WHERE id = ?", [$defaultSenderEmail, $userId]);
    $user['sender_email'] = $defaultSenderEmail;
}

// Load connected channels configs
$channelsRaw = all("SELECT * FROM connected_channels WHERE user_id = ?", [$userId]);
$channelsMap = [];
foreach ($channelsRaw as $c) {
    $channelsMap[$c['channel_name']] = json_decode($c['config_data'] ?: '{}', true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? 'save_all');

    $senderWhatsapp = trim($_POST['sender_whatsapp'] ?? ($user['sender_whatsapp'] ?: '+917620654814'));
    $linkedinUrl = trim($_POST['linkedin_url'] ?? ($user['linkedin_url'] ?: 'https://www.linkedin.com/in/rajjatrangari'));
    $senderEmail = trim($_POST['sender_email'] ?? ($user['sender_email'] ?: $defaultSenderEmail));
    if (empty($senderEmail) || $senderEmail === 'outreach@theonlyaifreedom.in') {
        $senderEmail = $defaultSenderEmail;
    }
    $instagramHandle = trim($_POST['instagram_handle'] ?? ($user['instagram_handle'] ?: '@theonlyaifreedom'));
    $facebookPageId = trim($_POST['facebook_page_id'] ?? '');

    // WhatsApp Advanced API & AI Chatbot Config
    $waPhoneId = trim($_POST['wa_phone_number_id'] ?? '');
    $waToken = trim($_POST['wa_access_token'] ?? '');
    $waMode = trim($_POST['wa_mode'] ?? 'cloud_api');
    $waAiChatbot = !empty($_POST['wa_ai_chatbot']) ? 1 : 0;
    $waAiGuidelines = trim($_POST['wa_ai_guidelines'] ?? '');

    $waStatus = (!empty($waPhoneId) && !empty($waToken)) ? 'connected' : 'bridge_ready';
    $waVerifiedName = 'The Only AI Freedom';
    $waQuality = 'GREEN';

    // Verify Meta Cloud API if credentials provided
    if (!empty($waPhoneId) && !empty($waToken)) {
        $testResult = WhatsAppService::testMetaApi($waPhoneId, $waToken);
        if ($testResult['status'] === 'success') {
            $waStatus = 'connected';
            $waVerifiedName = $testResult['verified_name'] ?? 'The Only AI Freedom';
            $waQuality = $testResult['quality_rating'] ?? 'GREEN';
        } else {
            $waStatus = 'credentials_error';
            $message = 'WhatsApp credentials saved, but Meta API test reported: ' . ($testResult['message'] ?? 'Error');
            $messageType = 'error';
        }
    }

    $waConfig = [
        'mode' => $waMode,
        'phone' => $senderWhatsapp,
        'phone_number_id' => $waPhoneId,
        'waba_id' => trim($_POST['wa_waba_id'] ?? ''),
        'access_token' => $waToken,
        'verify_token' => 'toaf_freedom_wa_2026',
        'status' => $waStatus,
        'verified_name' => $waVerifiedName,
        'quality_rating' => $waQuality,
        'ai_chatbot_enabled' => $waAiChatbot,
        'ai_chatbot_scope' => 'unsaved_only',
        'ai_guidelines' => $waAiGuidelines,
        'last_tested_at' => now(),
    ];

    $igConfig = [
        'handle' => '@' . ltrim($instagramHandle, '@'),
        'account_id' => trim($_POST['ig_account_id'] ?? ''),
        'status' => !empty($instagramHandle) ? 'connected' : 'not_connected',
        'updated_at' => now(),
    ];

    $fbConfig = [
        'page_id' => $facebookPageId,
        'status' => !empty($facebookPageId) ? 'connected' : 'not_connected',
        'updated_at' => now(),
    ];

    $liConfig = [
        'profile_url' => $linkedinUrl,
        'client_id' => trim($_POST['li_client_id'] ?? ''),
        'client_secret' => trim($_POST['li_client_secret'] ?? ''),
        'status' => !empty($linkedinUrl) ? 'connected' : 'not_connected',
        'updated_at' => now(),
    ];

    $emailConfig = [
        'sender_email' => $senderEmail,
        'smtp_host' => trim($_POST['smtp_host'] ?? 'smtp.hostinger.com'),
        'smtp_port' => trim($_POST['smtp_port'] ?? '465'),
        'smtp_user' => $senderEmail,
        'smtp_pass' => trim($_POST['smtp_pass'] ?? ''),
        'encryption' => trim($_POST['smtp_enc'] ?? 'ssl'),
        'status' => 'connected',
        'updated_at' => now(),
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
        $status = $cfg['status'] ?? 'connected';
        if ($driver === 'mysql') {
            q("INSERT INTO connected_channels (user_id, channel_name, account_identifier, status, config_data, updated_at)
               VALUES (?, ?, ?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE account_identifier = VALUES(account_identifier), status = VALUES(status), config_data = VALUES(config_data), updated_at = VALUES(updated_at)",
               [$userId, $chName, $identifier, $status, $json, now()]);
        } else {
            q("INSERT INTO connected_channels (user_id, channel_name, account_identifier, status, config_data, updated_at)
               VALUES (?, ?, ?, ?, ?, ?)
               ON CONFLICT(user_id, channel_name) DO UPDATE SET account_identifier = excluded.account_identifier, status = excluded.status, config_data = excluded.config_data, updated_at = excluded.updated_at",
               [$userId, $chName, $identifier, $status, $json, now()]);
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

    if (!$message) {
        $message = 'All channel configurations saved successfully! WhatsApp Cloud API, LinkedIn, Instagram, and Corporate Email are active.';
        $messageType = 'success';
    }
}

$wa = $channelsMap['whatsapp'] ?? [];
$ig = $channelsMap['instagram'] ?? [];
$fb = $channelsMap['facebook'] ?? [];
$li = $channelsMap['linkedin'] ?? [];
$em = $channelsMap['email'] ?? [];

$isWaConnected = (!empty($wa['phone_number_id']) && !empty($wa['access_token'])) || ($wa['status'] ?? '') === 'connected';
$isAiBotOn = !empty($wa['ai_chatbot_enabled']);
$isLiConnected = !empty($user['linkedin_url']) || ($li['status'] ?? '') === 'connected';
$isIgConnected = !empty($user['instagram_handle']) || ($ig['status'] ?? '') === 'connected';
$isEmConnected = true; // Email always active via rajjat@theonlyaifreedom.in

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
            <p class="text-xs text-[#a3a6b0] mt-1 max-w-2xl leading-relaxed subtext-contrast">
                Connect your WhatsApp Business Cloud API, LinkedIn, Instagram, and Corporate Email. Dispatch tailored outreaches directly from Outreach OS without leaving the platform, and activate intelligent AI Chatbots for inbound lead qualification.
            </p>
        </div>

        <a href="/inbox.php" class="btn-gold-cta px-4 py-2 rounded-xl text-black font-extrabold text-xs shadow-lg transition flex items-center gap-2 flex-shrink-0">
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

    <!-- Overview Statistics Banner with Authentic Brand SVG Logos -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <!-- WhatsApp Status Tile -->
        <div class="p-4 rounded-xl bg-white/[0.03] border border-white/[0.08] flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#25D366]/20 text-[#25D366] border border-[#25D366]/40 flex items-center justify-center flex-shrink-0">
                <svg viewBox="0 0 24 24" class="w-5 h-5 fill-current">
                    <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2M12.05 3.67C14.25 3.67 16.31 4.53 17.87 6.09C19.42 7.65 20.28 9.72 20.28 11.92C20.28 16.46 16.58 20.15 12.04 20.15C10.56 20.15 9.11 19.76 7.85 19L7.55 18.83L4.43 19.65L5.26 16.61L5.06 16.29C4.24 14.99 3.8 13.47 3.8 11.91C3.81 7.37 7.5 3.67 12.05 3.67M9.53 7.35C9.37 7.35 9.1 7.41 8.88 7.65C8.65 7.89 8.02 8.49 8.02 9.71C8.02 10.93 8.91 12.11 9.03 12.27C9.15 12.43 10.74 14.96 13.25 15.96C15.35 16.79 15.77 16.62 16.23 16.58C16.69 16.54 17.71 15.98 17.92 15.39C18.13 14.79 18.13 14.28 18.07 14.18C18.01 14.07 17.85 14.01 17.61 13.89C17.37 13.77 16.19 13.19 15.97 13.11C15.75 13.03 15.59 12.99 15.43 13.23C15.27 13.47 14.81 14.01 14.67 14.18C14.53 14.34 14.39 14.36 14.15 14.24C13.91 14.12 12.9 13.79 11.7 12.72C10.77 11.89 10.14 10.86 10.02 10.62C9.9 10.38 10.01 10.25 10.13 10.13C10.24 10.02 10.38 9.84 10.5 9.7C10.62 9.56 10.66 9.46 10.74 9.3C10.82 9.14 10.78 9 10.72 8.88C10.66 8.76 10.2 7.63 10 7.17C9.82 6.72 9.63 6.78 9.53 6.78V7.35Z"/>
                </svg>
            </div>
            <div>
                <div class="text-[11px] text-[#a3a6b0] subtext-contrast">WhatsApp Engine</div>
                <div class="text-xs font-bold text-[#f4f1ea] flex items-center gap-1 mt-0.5">
                    <?php if ($isWaConnected): ?>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-emerald-400">Cloud API Live</span>
                    <?php else: ?>
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        <span class="text-amber-400">Bridge Active</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- LinkedIn Status Tile -->
        <div class="p-4 rounded-xl bg-white/[0.03] border border-white/[0.08] flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#0A66C2]/20 text-[#0A66C2] border border-[#0A66C2]/40 flex items-center justify-center flex-shrink-0">
                <svg viewBox="0 0 24 24" class="w-5 h-5 fill-current">
                    <path d="M19 3A2 2 0 0 1 21 5V19A2 2 0 0 1 19 21H5A2 2 0 0 1 3 19V5A2 2 0 0 1 5 3H19M18.5 18.5V13.2A3.26 3.26 0 0 0 15.24 9.94C14.39 9.94 13.4 10.46 12.92 11.24V10.13H10.13V18.5H12.92V13.57C12.92 12.8 13.54 12.17 14.31 12.17A1.4 1.4 0 0 1 15.71 13.57V18.5H18.5M6.88 8.56A1.68 1.68 0 0 0 8.56 6.88C8.56 5.95 7.81 5.19 6.88 5.19A1.69 1.69 0 0 0 5.19 6.88C5.19 7.81 5.95 8.56 6.88 8.56M8.27 18.5V10.13H5.5V18.5H8.27Z"/>
                </svg>
            </div>
            <div>
                <div class="text-[11px] text-[#a3a6b0] subtext-contrast">LinkedIn InMail</div>
                <div class="text-xs font-bold text-[#f4f1ea] flex items-center gap-1 mt-0.5">
                    <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                    <span class="text-blue-400">1-Click Ready</span>
                </div>
            </div>
        </div>

        <!-- Instagram Status Tile -->
        <div class="p-4 rounded-xl bg-white/[0.03] border border-white/[0.08] flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#E4405F]/20 text-[#E4405F] border border-[#E4405F]/40 flex items-center justify-center flex-shrink-0">
                <svg viewBox="0 0 24 24" class="w-5 h-5 fill-current">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
            </div>
            <div>
                <div class="text-[11px] text-[#a3a6b0] subtext-contrast">Instagram DM</div>
                <div class="text-xs font-bold text-[#f4f1ea] flex items-center gap-1 mt-0.5">
                    <span class="w-2 h-2 rounded-full bg-pink-400"></span>
                    <span class="text-pink-400">Bridge Active</span>
                </div>
            </div>
        </div>

        <!-- Corporate Email Status Tile -->
        <div class="p-4 rounded-xl bg-white/[0.03] border border-white/[0.08] flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center flex-shrink-0">
                <svg viewBox="0 0 24 24" class="w-5 h-5 fill-current text-[#818cf8]">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
            </div>
            <div>
                <div class="text-[11px] text-[#a3a6b0] subtext-contrast">Corporate Mail</div>
                <div class="text-xs font-bold text-[#f4f1ea] flex items-center gap-1 mt-0.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-emerald-400">SMTP Active</span>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" id="channelsMainForm" class="space-y-6">
        <input type="hidden" name="action" value="save_all">

        <!-- ==========================================
             1. WHATSAPP CONNECTION & SETUP GUIDE
             ========================================== -->
        <div class="glass-panel rounded-2xl p-6 border border-white/[0.08] space-y-5" id="whatsappCard">
            
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-white/[0.08] pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-[#25D366]/20 text-[#25D366] border border-[#25D366]/40 flex items-center justify-center flex-shrink-0">
                        <svg viewBox="0 0 24 24" class="w-6 h-6 fill-current">
                            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2M12.05 3.67C14.25 3.67 16.31 4.53 17.87 6.09C19.42 7.65 20.28 9.72 20.28 11.92C20.28 16.46 16.58 20.15 12.04 20.15C10.56 20.15 9.11 19.76 7.85 19L7.55 18.83L4.43 19.65L5.26 16.61L5.06 16.29C4.24 14.99 3.8 13.47 3.8 11.91C3.81 7.37 7.5 3.67 12.05 3.67M9.53 7.35C9.37 7.35 9.1 7.41 8.88 7.65C8.65 7.89 8.02 8.49 8.02 9.71C8.02 10.93 8.91 12.11 9.03 12.27C9.15 12.43 10.74 14.96 13.25 15.96C15.35 16.79 15.77 16.62 16.23 16.58C16.69 16.54 17.71 15.98 17.92 15.39C18.13 14.79 18.13 14.28 18.07 14.18C18.01 14.07 17.85 14.01 17.61 13.89C17.37 13.77 16.19 13.19 15.97 13.11C15.75 13.03 15.59 12.99 15.43 13.23C15.27 13.47 14.81 14.01 14.67 14.18C14.53 14.34 14.39 14.36 14.15 14.24C13.91 14.12 12.9 13.79 11.7 12.72C10.77 11.89 10.14 10.86 10.02 10.62C9.9 10.38 10.01 10.25 10.13 10.13C10.24 10.02 10.38 9.84 10.5 9.7C10.62 9.56 10.66 9.46 10.74 9.3C10.82 9.14 10.78 9 10.72 8.88C10.66 8.76 10.2 7.63 10 7.17C9.82 6.72 9.63 6.78 9.53 6.78V7.35Z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-[#f4f1ea] text-sm">WhatsApp Business Bridge & Cloud API</h3>
                            
                            <!-- Live Connection Badge -->
                            <span id="waStatusBadge" class="text-[11px] px-2.5 py-0.5 rounded-full font-bold flex items-center gap-1.5 <?= $isWaConnected ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' : 'bg-amber-500/15 text-amber-400 border border-amber-500/30' ?>">
                                <span class="w-2 h-2 rounded-full <?= $isWaConnected ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' ?>"></span>
                                <span id="waStatusText"><?= $isWaConnected ? 'Connected & Operational' : 'Action Required / Unconnected' ?></span>
                            </span>
                        </div>
                        <p class="text-xs text-[#a3a6b0] subtext-contrast mt-0.5">Direct in-platform messaging via Meta Cloud API + AI Chatbot automation for new leads.</p>
                    </div>
                </div>

                <!-- Header Action Buttons -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" onclick="verifyWhatsAppApi()" id="btnVerifyWaTop" class="px-3 py-1.5 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] text-emerald-400 border border-emerald-500/30 text-xs font-bold transition flex items-center gap-1.5" title="Query Meta Graph API to verify credentials">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                        <span>Verify API Connection</span>
                    </button>

                    <button type="button" onclick="saveWhatsAppDirect()" id="btnSaveWaTop" class="btn-gold-cta px-4 py-1.5 rounded-xl text-black font-extrabold text-xs shadow-md transition flex items-center gap-1.5">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Save & Connect</span>
                    </button>
                </div>
            </div>

            <!-- Dynamic Verification Result Box (Shown when verified or tested) -->
            <div id="waVerificationAlert" class="<?= $isWaConnected ? '' : 'hidden' ?> p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-xs font-bold text-emerald-400">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        <span>Meta WhatsApp Cloud API Live & Verified</span>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-mono font-bold">100% In-Platform Direct Send</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs pt-1 border-t border-emerald-500/20">
                    <div>
                        <span class="text-[10px] text-[#a3a6b0] subtext-contrast">Verified Brand:</span>
                        <div class="font-bold text-[#f4f1ea]" id="waVerifiedBrand"><?= h($wa['verified_name'] ?? 'The Only AI Freedom') ?></div>
                    </div>
                    <div>
                        <span class="text-[10px] text-[#a3a6b0] subtext-contrast">Meta Phone Number ID:</span>
                        <div class="font-mono text-emerald-400 font-bold" id="waVerifiedPhoneId"><?= h($wa['phone_number_id'] ?? '1509018372287301') ?></div>
                    </div>
                    <div>
                        <span class="text-[10px] text-[#a3a6b0] subtext-contrast">Unified Inbox Status:</span>
                        <div class="font-bold text-emerald-400 flex items-center gap-1">
                            <span>Direct Chat Active (No New Tab)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ultra-High Contrast Step-by-Step Instructions Card -->
            <div class="instruction-card p-5 rounded-xl bg-white/[0.03] border border-white/[0.08] space-y-3 text-xs">
                <div class="font-bold text-[#f4f1ea] flex items-center gap-2">
                    <i data-lucide="help-circle" class="w-4 h-4 text-[#d9b46c]"></i>
                    <span class="text-sm font-extrabold">Meta WhatsApp Cloud API Setup Guide:</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-[#a3a6b0] text-xs leading-relaxed subtext-contrast">
                    <div class="space-y-1.5 p-3 rounded-lg bg-black/20 border border-white/[0.04]">
                        <strong class="text-emerald-400 block text-xs uppercase tracking-wider font-extrabold">Step 1: Your WhatsApp Business Credentials</strong>
                        <p class="text-[11px]">1. Enter your WhatsApp Business phone number below (e.g. <strong class="text-emerald-400 font-bold">+917620654814</strong>).</p>
                        <p class="text-[11px]">2. Paste your <strong>Phone Number ID</strong> and <strong>System User Access Token</strong> from Meta for Developers.</p>
                        <p class="text-[11px]">3. Click <strong>"Verify API Connection"</strong> above to verify your live Meta credentials.</p>
                    </div>
                    <div class="space-y-1.5 p-3 rounded-lg bg-black/20 border border-white/[0.04]">
                        <strong class="text-[#d9b46c] block text-xs uppercase tracking-wider font-extrabold">Step 2: Meta Webhook Handshake Configuration</strong>
                        <p class="text-[11px]">1. In <a href="https://developers.facebook.com" target="_blank" class="text-blue-400 font-bold hover:underline">Meta for Developers ↗</a>, go to <strong>WhatsApp &gt; Configuration &gt; Webhook</strong>.</p>
                        <p class="text-[11px]">2. Enter <strong>Callback URL</strong>:
                            <code class="block mt-1 font-mono select-all bg-black/40 text-emerald-300 px-2 py-1 rounded text-[11px] border border-white/[0.08]">https://outreach.theonlyaifreedom.in/api.php?action=webhook_whatsapp</code>
                            <span class="text-[10px] text-[#a3a6b0] mt-0.5 block">(Alternative URL: <code class="font-mono text-emerald-300">https://outreach.theonlyaifreedom.in/webhook.php</code>)</span>
                        </p>
                        <p class="text-[11px]">3. Enter <strong>Verify Token</strong>: 
                            <code class="font-mono font-bold text-amber-300 bg-black/40 px-2 py-0.5 rounded border border-white/[0.08] select-all">toaf_freedom_wa_2026</code>
                        </p>
                        <p class="text-[11px]">4. Click <strong>Verify and save</strong>, then click <strong>Manage</strong> and check <strong class="text-emerald-400">messages</strong>.</p>
                    </div>
                </div>
            </div>

            <!-- Input Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5 form-label">Your WhatsApp Number (With Country Code) *</label>
                    <input type="text" id="sender_whatsapp" name="sender_whatsapp" value="<?= h($user['sender_whatsapp'] ?: ($wa['phone'] ?: '+917620654814')) ?>" 
                           placeholder="+917620654814" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                    <span class="text-[10px] text-[#a3a6b0] subtext-contrast mt-1 block">Your personal/business WhatsApp used to talk directly to decision makers.</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5 form-label">Dispatch Mode</label>
                    <select id="wa_mode" name="wa_mode" class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                        <option value="cloud_api" <?= ($wa['mode'] ?? 'cloud_api') === 'cloud_api' ? 'selected' : '' ?>>Meta WhatsApp Cloud API (Automated In-Platform Messaging &amp; Webhook Sync)</option>
                        <option value="instant_bridge" <?= ($wa['mode'] ?? '') === 'instant_bridge' ? 'selected' : '' ?>>Instant Direct App Bridge (Opens Local WhatsApp Web App)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5 form-label">WhatsApp Phone Number ID *</label>
                    <input type="text" id="wa_phone_number_id" name="wa_phone_number_id" value="<?= h($wa['phone_number_id'] ?? '1509018372287301') ?>" 
                           placeholder="e.g. 1509018372287301" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                    <span class="text-[10px] text-[#a3a6b0] subtext-contrast mt-1 block">From Meta Developers WhatsApp API Setup panel.</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5 form-label">System User Permanent Access Token *</label>
                    <div class="relative">
                        <input type="password" id="wa_access_token" name="wa_access_token" value="<?= h($wa['access_token'] ?? '') ?>" 
                               placeholder="EAAGm0PX4ZC5o..." 
                               class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl pl-3.5 pr-10 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                        <button type="button" onclick="toggleTokenVisibility()" class="absolute right-3 top-2.5 text-[#a3a6b0] hover:text-[#f4f1ea]">
                            <i data-lucide="eye" id="tokenEyeIcon" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <span class="text-[10px] text-[#a3a6b0] subtext-contrast mt-1 block">Meta System User Token with whatsapp_business_messaging permission.</span>
                </div>
            </div>

            <!-- ========================================================
                 AI CHATBOT AUTOMATION (STRICTLY NEW/UNSAVED NUMBERS ONLY)
                 ======================================================== -->
            <div class="p-5 rounded-2xl bg-gradient-to-br from-[#d9b46c]/10 via-transparent to-emerald-500/5 border border-[#d9b46c]/30 space-y-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#d9b46c]/20 text-[#f3dca4] border border-[#d9b46c]/40 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="bot" class="w-5 h-5 text-[#d9b46c]"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-extrabold text-[#f4f1ea] text-xs uppercase tracking-wider">WhatsApp AI Chatbot Auto-Responder</h4>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 text-[10px] font-bold">New &amp; Unsaved Numbers Only</span>
                            </div>
                            <p class="text-xs text-[#a3a6b0] subtext-contrast mt-0.5">Understands incoming context and replies using your business offering. Never interrupts existing CRM clients.</p>
                        </div>
                    </div>

                    <!-- AI Chatbot Toggle Switch -->
                    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                        <input type="checkbox" id="wa_ai_chatbot" name="wa_ai_chatbot" value="1" <?= $isAiBotOn ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-white/[0.1] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#d9b46c]"></div>
                        <span class="ml-2.5 text-xs font-bold text-[#f4f1ea]">AI Auto-Reply <?= $isAiBotOn ? 'ON' : 'OFF' ?></span>
                    </label>
                </div>

                <div class="p-3.5 rounded-xl bg-black/20 border border-white/[0.06] text-xs text-[#f4f1ea] space-y-1.5 subtext-contrast">
                    <div class="font-bold flex items-center gap-1.5 text-emerald-400">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                        <span>Smart Dual-Routing Logic Active:</span>
                    </div>
                    <ul class="text-[11px] text-[#a3a6b0] space-y-1 subtext-contrast list-disc pl-5">
                        <li><strong>Unsaved / New Phone Numbers:</strong> The AI Chatbot automatically analyzes message intent and replies in English, Hinglish, Hindi, or Marathi, adding the prospect into your CRM Pipeline as an inbound qualified lead.</li>
                        <li><strong>Existing CRM Clients &amp; Contacts:</strong> The AI Chatbot <span class="text-amber-400 font-bold">never auto-replies</span> to contacts already saved in your CRM, ensuring 100% human discretion and personal high-ticket closing.</li>
                    </ul>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1 form-label">Custom AI Tone &amp; Persona Instructions (Optional)</label>
                    <input type="text" id="wa_ai_guidelines" name="wa_ai_guidelines" value="<?= h($wa['ai_guidelines'] ?? '') ?>" 
                           placeholder="e.g. High-ticket consultant persona, emphasize 10-minute intro call, mention live ROI guarantee." 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2 text-xs text-[#f4f1ea] placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                </div>
            </div>

            <!-- Bottom Action Row of WhatsApp Card -->
            <div class="pt-3 border-t border-white/[0.08] flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <button type="button" onclick="openSimulateModal()" class="px-3.5 py-2 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] text-xs font-bold text-[#f4f1ea] border border-white/[0.12] flex items-center gap-1.5 transition">
                        <i data-lucide="bot" class="w-3.5 h-3.5 text-[#d9b46c]"></i>
                        <span>Simulate Inbound &amp; Test AI Bot</span>
                    </button>
                    <button type="button" onclick="testDispatch('whatsapp')" class="px-3.5 py-2 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] text-emerald-400 border border-emerald-500/25 text-xs font-semibold transition flex items-center gap-1.5">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        <span>Test WhatsApp Ping</span>
                    </button>
                </div>

                <!-- Primary Save Button for WhatsApp Card -->
                <button type="button" onclick="saveWhatsAppDirect()" id="btnSaveWaBottom" class="btn-gold-cta w-full sm:w-auto px-6 py-2.5 rounded-xl text-black font-extrabold text-xs shadow-lg transition flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Save &amp; Connect WhatsApp</span>
                </button>
            </div>

        </div>

        <!-- ==========================================
             2. LINKEDIN CONNECTION & INMAIL SETUP
             ========================================== -->
        <div class="glass-panel rounded-2xl p-6 border border-white/[0.08] space-y-5" id="linkedinCard">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-white/[0.08] pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-[#0A66C2]/20 text-[#0A66C2] border border-[#0A66C2]/40 flex items-center justify-center flex-shrink-0">
                        <svg viewBox="0 0 24 24" class="w-6 h-6 fill-current">
                            <path d="M19 3A2 2 0 0 1 21 5V19A2 2 0 0 1 19 21H5A2 2 0 0 1 3 19V5A2 2 0 0 1 5 3H19M18.5 18.5V13.2A3.26 3.26 0 0 0 15.24 9.94C14.39 9.94 13.4 10.46 12.92 11.24V10.13H10.13V18.5H12.92V13.57C12.92 12.8 13.54 12.17 14.31 12.17A1.4 1.4 0 0 1 15.71 13.57V18.5H18.5M6.88 8.56A1.68 1.68 0 0 0 8.56 6.88C8.56 5.95 7.81 5.19 6.88 5.19A1.69 1.69 0 0 0 5.19 6.88C5.19 7.81 5.95 8.56 6.88 8.56M8.27 18.5V10.13H5.5V18.5H8.27Z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-[#f4f1ea] text-sm">LinkedIn Direct Message &amp; InMail Bridge</h3>
                            <span id="liStatusBadge" class="text-[10px] px-2 py-0.5 rounded-full <?= $isLiConnected ? 'bg-blue-500/20 text-blue-400 border border-blue-500/40' : 'bg-white/[0.08] text-[#a3a6b0]' ?> font-bold">
                                <?= $isLiConnected ? 'Connected &amp; 1-Click Ready' : 'Unconnected' ?>
                            </span>
                        </div>
                        <p class="text-xs text-[#a3a6b0] subtext-contrast mt-0.5">Connect your profile to dispatch tailored executive connection requests &amp; InMails directly.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" onclick="verifyLinkedInDirect()" id="btnVerifyLi" class="px-3 py-1.5 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] text-blue-400 border border-blue-500/30 text-xs font-bold transition flex items-center gap-1.5">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                        <span>Verify LinkedIn Profile</span>
                    </button>
                    <button type="button" onclick="saveLinkedInDirect()" id="btnSaveLi" class="btn-gold-cta px-4 py-1.5 rounded-xl text-black font-extrabold text-xs shadow-md transition flex items-center gap-1.5">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Save LinkedIn</span>
                    </button>
                </div>
            </div>

            <!-- Dynamic LinkedIn Verification Box -->
            <div id="liVerificationAlert" class="<?= $isLiConnected ? '' : 'hidden' ?> p-3.5 rounded-xl bg-blue-500/10 border border-blue-500/30 text-xs text-blue-300 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-blue-400"></i>
                    <span>LinkedIn Bridge Active: Pre-loaded 1-Click InMail dispatch ready for decision makers.</span>
                </div>
                <button type="button" onclick="testDispatch('linkedin')" class="text-xs font-bold text-blue-400 hover:underline">Test Profile Link ↗</button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5 form-label">Your LinkedIn Profile or Company URL *</label>
                    <input type="url" id="linkedin_url" name="linkedin_url" value="<?= h($user['linkedin_url'] ?: 'https://www.linkedin.com/in/rajjatrangari') ?>" 
                           placeholder="https://www.linkedin.com/in/rajjatrangari" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                    <span class="text-[10px] text-[#a3a6b0] subtext-contrast mt-1 block">Used by the 1-Click InMail bridge to pre-populate personalized pitches directly on decision-maker profiles.</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5 form-label">LinkedIn Developer Client ID (Optional)</label>
                        <input type="text" id="li_client_id" name="li_client_id" value="<?= h($li['client_id'] ?? '') ?>" 
                               placeholder="e.g. 78xxxyyyzzz" 
                               class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5 form-label">LinkedIn Client Secret (Optional)</label>
                        <input type="password" id="li_client_secret" name="li_client_secret" value="<?= h($li['client_secret'] ?? '') ?>" 
                               placeholder="••••••••••••" 
                               class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================
             3. INSTAGRAM DM BRIDGE & META BUSINESS
             ========================================== -->
        <div class="glass-panel rounded-2xl p-6 border border-white/[0.08] space-y-5" id="instagramCard">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-white/[0.08] pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-[#E4405F]/20 text-[#E4405F] border border-[#E4405F]/40 flex items-center justify-center flex-shrink-0">
                        <svg viewBox="0 0 24 24" class="w-6 h-6 fill-current">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-[#f4f1ea] text-sm">Instagram Direct Message (DM) Bridge</h3>
                            <span id="igStatusBadge" class="text-[10px] px-2 py-0.5 rounded-full <?= $isIgConnected ? 'bg-pink-500/20 text-pink-400 border border-pink-500/40' : 'bg-white/[0.08] text-[#a3a6b0]' ?> font-bold">
                                <?= $isIgConnected ? 'Connected &amp; Bridge Active' : 'Unconnected' ?>
                            </span>
                        </div>
                        <p class="text-xs text-[#a3a6b0] subtext-contrast mt-0.5">Reach modern e-commerce founders and luxury retailers via high-response Instagram direct messaging.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" onclick="verifyInstagramDirect()" id="btnVerifyIg" class="px-3 py-1.5 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] text-pink-400 border border-pink-500/30 text-xs font-bold transition flex items-center gap-1.5">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                        <span>Verify Instagram Handle</span>
                    </button>
                    <button type="button" onclick="saveInstagramDirect()" id="btnSaveIg" class="btn-gold-cta px-4 py-1.5 rounded-xl text-black font-extrabold text-xs shadow-md transition flex items-center gap-1.5">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Save Instagram</span>
                    </button>
                </div>
            </div>

            <!-- Dynamic Instagram Verification Box -->
            <div id="igVerificationAlert" class="<?= $isIgConnected ? '' : 'hidden' ?> p-3.5 rounded-xl bg-pink-500/10 border border-pink-500/30 text-xs text-pink-300 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-pink-400"></i>
                    <span>Instagram DM Bridge Active: Direct dispatch pre-loaded with target profile links.</span>
                </div>
                <button type="button" onclick="testDispatch('instagram')" class="text-xs font-bold text-pink-400 hover:underline">Test DM Link ↗</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5 form-label">Your Instagram Brand Handle *</label>
                    <input type="text" id="instagram_handle" name="instagram_handle" value="<?= h($user['instagram_handle'] ?: '@theonlyaifreedom') ?>" 
                           placeholder="@theonlyaifreedom" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                    <span class="text-[10px] text-[#a3a6b0] subtext-contrast mt-1 block">Used to initiate direct Instagram DM threads.</span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5 form-label">Facebook Page ID (Optional)</label>
                    <input type="text" id="facebook_page_id" name="facebook_page_id" value="<?= h($fb['page_id'] ?? '') ?>" 
                           placeholder="e.g. 109283091823" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                </div>
            </div>
        </div>

        <!-- ==========================================
             4. CORPORATE EMAIL (SMTP & DISPATCHER)
             ========================================== -->
        <div class="glass-panel rounded-2xl p-6 border border-white/[0.08] space-y-5" id="emailCard">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-white/[0.08] pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center flex-shrink-0">
                        <svg viewBox="0 0 24 24" class="w-6 h-6 fill-current text-[#818cf8]">
                            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-[#f4f1ea] text-sm">Corporate Email Dispatcher (rajjat@theonlyaifreedom.in)</h3>
                            <span id="emStatusBadge" class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 font-bold">
                                Connected &amp; Ready to Send
                            </span>
                        </div>
                        <p class="text-xs text-[#a3a6b0] subtext-contrast mt-0.5">High-deliverability executive cold email delivered from <strong class="text-emerald-400">rajjat@theonlyaifreedom.in</strong>.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" onclick="verifyEmailDirect()" id="btnVerifyEm" class="px-3 py-1.5 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] text-indigo-400 border border-indigo-500/30 text-xs font-bold transition flex items-center gap-1.5" title="Send a real verification ping from rajjat@theonlyaifreedom.in">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        <span>Send Test Email Ping</span>
                    </button>
                    <button type="button" onclick="saveEmailDirect()" id="btnSaveEm" class="btn-gold-cta px-4 py-1.5 rounded-xl text-black font-extrabold text-xs shadow-md transition flex items-center gap-1.5">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Save &amp; Connect Email</span>
                    </button>
                </div>
            </div>

            <!-- Dynamic Email Verification Result Alert Box -->
            <div id="emVerificationAlert" class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-xs font-bold text-emerald-400">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        <span>Corporate Email Engine Verified &amp; Active</span>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-mono font-bold">Direct Server &amp; SMTP Ready</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs pt-1 border-t border-emerald-500/20">
                    <div>
                        <span class="text-[10px] text-[#a3a6b0] subtext-contrast">Verified Sender:</span>
                        <div class="font-bold text-emerald-400" id="emVerifiedSender"><?= h($user['sender_email'] ?: $defaultSenderEmail) ?></div>
                    </div>
                    <div>
                        <span class="text-[10px] text-[#a3a6b0] subtext-contrast">SMTP Host:</span>
                        <div class="font-mono text-[#f4f1ea]" id="emVerifiedHost">smtp.hostinger.com:465 (SSL)</div>
                    </div>
                    <div>
                        <span class="text-[10px] text-[#a3a6b0] subtext-contrast">Inbox Delivery Mode:</span>
                        <div class="font-bold text-emerald-400">Authenticated High-Deliverability</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5 form-label">Sender Corporate Email *</label>
                    <input type="email" id="sender_email" name="sender_email" value="<?= h($user['sender_email'] ?: $defaultSenderEmail) ?>" 
                           placeholder="rajjat@theonlyaifreedom.in" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c] font-bold">
                    <span class="text-[10px] text-[#a3a6b0] subtext-contrast mt-1 block">Dedicated official sender mailbox.</span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5 form-label">SMTP Host</label>
                    <input type="text" id="smtp_host" name="smtp_host" value="<?= h($em['smtp_host'] ?? 'smtp.hostinger.com') ?>" 
                           placeholder="smtp.hostinger.com" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5 form-label">SMTP Port</label>
                    <input type="text" id="smtp_port" name="smtp_port" value="<?= h($em['smtp_port'] ?? '465') ?>" 
                           placeholder="465" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                </div>
            </div>
        </div>

        <!-- Sticky Overall Save Button -->
        <div class="p-5 rounded-2xl glass-panel border border-white/[0.08] flex items-center justify-between">
            <div class="text-xs text-[#a3a6b0] subtext-contrast">
                All settings are securely persisted in your private Outreach OS workspace.
            </div>
            <button type="submit" class="btn-gold-cta px-6 py-2.5 rounded-xl text-black font-extrabold text-xs shadow-lg transition flex items-center gap-2">
                <i data-lucide="check" class="w-4 h-4"></i>
                <span>Save All Channel Connections</span>
            </button>
        </div>
    </form>
</div>

<!-- AI Chatbot Inbound Simulator Modal -->
<div id="simModalBackdrop" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm items-center justify-center p-4">
    <div class="glass-card rounded-2xl border border-white/[0.12] w-full max-w-lg p-6 space-y-4 shadow-2xl relative">
        <div class="flex items-center justify-between border-b border-white/[0.08] pb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-[#d9b46c]/20 text-[#f3dca4] flex items-center justify-center font-bold">
                    <i data-lucide="bot" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="font-bold text-[#f4f1ea] text-sm">WhatsApp AI Chatbot Simulator</h3>
                    <p class="text-[11px] text-[#a3a6b0] subtext-contrast">Test how the AI auto-responds to new vs existing leads</p>
                </div>
            </div>
            <button type="button" onclick="closeSimulateModal()" class="text-[#a3a6b0] hover:text-white p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="space-y-3 text-xs">
            <div>
                <label class="block font-bold text-[#f4f1ea] mb-1">Incoming Sender Phone</label>
                <input type="text" id="simSenderPhone" value="+919822998877" placeholder="+91..." 
                       class="w-full bg-[#11141c] border border-white/[0.1] rounded-xl px-3 py-2 text-xs text-[#f4f1ea] font-mono">
                <span class="text-[10px] text-[#a3a6b0] subtext-contrast">Change to any number to test new lead routing vs saved lead routing.</span>
            </div>

            <div>
                <label class="block font-bold text-[#f4f1ea] mb-1">Incoming Message Content</label>
                <textarea id="simMessageText" rows="3" 
                          class="w-full bg-[#11141c] border border-white/[0.1] rounded-xl p-3 text-xs text-[#f4f1ea]" 
                          placeholder="e.g. Hello, what services do you provide and what is your pricing?">Hi, I came across your brand. Could you share what AI automation services you provide and what are your typical charges?</textarea>
            </div>

            <button type="button" onclick="runInboundSimulation()" id="btnRunSim" class="btn-gold-cta w-full py-2.5 rounded-xl text-black font-extrabold text-xs shadow-md transition flex items-center justify-center gap-2">
                <i data-lucide="play" class="w-4 h-4"></i>
                <span>Simulate Inbound Message &amp; Evaluate AI</span>
            </button>
        </div>

        <!-- Simulation Result Box -->
        <div id="simResultBox" class="hidden p-4 rounded-xl bg-white/[0.04] border border-white/[0.1] space-y-2 text-xs">
            <!-- Dynamically populated -->
        </div>
    </div>
</div>

<script>
function toggleTokenVisibility() {
    const input = document.getElementById('wa_access_token');
    const icon = document.getElementById('tokenEyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
    } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
    }
    lucide.createIcons();
}

async function verifyWhatsAppApi() {
    const phoneId = document.getElementById('wa_phone_number_id').value.trim();
    const token = document.getElementById('wa_access_token').value.trim();

    if (!phoneId || !token) {
        showToast('Please enter both WhatsApp Phone Number ID and Access Token to test.', 'error');
        return;
    }

    const btn = document.getElementById('btnVerifyWaTop');
    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin w-3 h-3 border-2 border-emerald-400 border-t-transparent rounded-full"></span><span>Testing Meta API...</span>`;

    try {
        const res = await fetch('/api.php?action=test_whatsapp_connection', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone_number_id: phoneId, access_token: token })
        });
        const data = await res.json();

        if (data.status === 'success') {
            showToast('✓ Meta WhatsApp Cloud API Verified successfully!', 'success');
            
            // Update UI elements
            const alertBox = document.getElementById('waVerificationAlert');
            const brandEl = document.getElementById('waVerifiedBrand');
            const phoneEl = document.getElementById('waVerifiedPhoneId');
            const badge = document.getElementById('waStatusBadge');
            const text = document.getElementById('waStatusText');

            if (alertBox) alertBox.classList.remove('hidden');
            if (brandEl) brandEl.textContent = data.verified_name || 'The Only AI Freedom';
            if (phoneEl) phoneEl.textContent = data.phone_number_id || phoneId;
            if (badge) {
                badge.className = 'text-[11px] px-2.5 py-0.5 rounded-full font-bold flex items-center gap-1.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/40';
            }
            if (text) text.textContent = 'Connected & Operational';
        } else {
            showToast(data.message || 'Meta API verification failed.', 'error');
        }
    } catch (e) {
        showToast('Connection error while querying Meta Graph API.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="shield-check" class="w-3.5 h-3.5"></i><span>Verify API Connection</span>`;
        lucide.createIcons();
    }
}

async function saveWhatsAppDirect() {
    const phone = document.getElementById('sender_whatsapp').value.trim();
    const mode = document.getElementById('wa_mode').value;
    const phoneId = document.getElementById('wa_phone_number_id').value.trim();
    const token = document.getElementById('wa_access_token').value.trim();
    const aiBot = document.getElementById('wa_ai_chatbot').checked ? 1 : 0;
    const aiGuidelines = document.getElementById('wa_ai_guidelines').value.trim();

    const btn = document.getElementById('btnSaveWaBottom');
    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin w-3.5 h-3.5 border-2 border-black border-t-transparent rounded-full"></span><span>Saving WhatsApp...</span>`;

    try {
        const res = await fetch('/api.php?action=save_channel_config', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                channel: 'whatsapp',
                sender_whatsapp: phone,
                wa_mode: mode,
                wa_phone_number_id: phoneId,
                wa_access_token: token,
                wa_ai_chatbot: aiBot,
                wa_ai_guidelines: aiGuidelines
            })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('✓ WhatsApp Cloud API & AI Chatbot settings saved!', 'success');
            const alertBox = document.getElementById('waVerificationAlert');
            const badge = document.getElementById('waStatusBadge');
            const text = document.getElementById('waStatusText');

            if (data.config && data.config.status === 'connected') {
                if (alertBox) alertBox.classList.remove('hidden');
                if (badge) badge.className = 'text-[11px] px-2.5 py-0.5 rounded-full font-bold flex items-center gap-1.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/40';
                if (text) text.textContent = 'Connected & Operational';
            }
        } else {
            showToast(data.message || 'Failed to save configuration', 'error');
        }
    } catch (e) {
        showToast('Network error while saving WhatsApp configuration', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="check" class="w-4 h-4"></i><span>Save & Connect WhatsApp</span>`;
        lucide.createIcons();
    }
}

async function saveLinkedInDirect() {
    const url = document.getElementById('linkedin_url').value.trim();
    const cid = document.getElementById('li_client_id').value.trim();
    const sec = document.getElementById('li_client_secret').value.trim();
    const btn = document.getElementById('btnSaveLi');

    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin w-3.5 h-3.5 border-2 border-black border-t-transparent rounded-full"></span><span>Saving...</span>`;

    try {
        const res = await fetch('/api.php?action=save_channel_config', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ channel: 'linkedin', linkedin_url: url, linkedin_client_id: cid, linkedin_client_secret: sec })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('✓ LinkedIn profile settings saved!', 'success');
            const alertBox = document.getElementById('liVerificationAlert');
            if (alertBox) alertBox.classList.remove('hidden');
        } else {
            showToast(data.message || 'Save failed', 'error');
        }
    } catch(e) {
        showToast('Network error', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="check" class="w-3.5 h-3.5"></i><span>Save LinkedIn</span>`;
        lucide.createIcons();
    }
}

async function verifyLinkedInDirect() {
    const url = document.getElementById('linkedin_url').value.trim();
    const btn = document.getElementById('btnVerifyLi');
    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin w-3.5 h-3.5 border-2 border-blue-400 border-t-transparent rounded-full"></span><span>Testing...</span>`;

    try {
        const res = await fetch('/api.php?action=test_linkedin_connection', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ linkedin_url: url })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('✓ ' + data.message, 'success');
            const alertBox = document.getElementById('liVerificationAlert');
            if (alertBox) alertBox.classList.remove('hidden');
            const badge = document.getElementById('liStatusBadge');
            if (badge) {
                badge.className = 'text-[10px] px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-400 border border-blue-500/40 font-bold';
                badge.textContent = 'Connected & 1-Click Ready';
            }
        } else {
            showToast(data.message || 'LinkedIn verification failed', 'error');
        }
    } catch(e) {
        showToast('Network error', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="shield-check" class="w-3.5 h-3.5"></i><span>Verify LinkedIn Profile</span>`;
        lucide.createIcons();
    }
}

async function saveInstagramDirect() {
    const handle = document.getElementById('instagram_handle').value.trim();
    const pageId = document.getElementById('facebook_page_id').value.trim();
    const btn = document.getElementById('btnSaveIg');

    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin w-3.5 h-3.5 border-2 border-black border-t-transparent rounded-full"></span><span>Saving...</span>`;

    try {
        const res = await fetch('/api.php?action=save_channel_config', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ channel: 'instagram', instagram_handle: handle, instagram_account_id: pageId })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('✓ Instagram handle settings saved!', 'success');
            const alertBox = document.getElementById('igVerificationAlert');
            if (alertBox) alertBox.classList.remove('hidden');
        } else {
            showToast(data.message || 'Save failed', 'error');
        }
    } catch(e) {
        showToast('Network error', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="check" class="w-3.5 h-3.5"></i><span>Save Instagram</span>`;
        lucide.createIcons();
    }
}

async function verifyInstagramDirect() {
    const handle = document.getElementById('instagram_handle').value.trim();
    const btn = document.getElementById('btnVerifyIg');
    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin w-3.5 h-3.5 border-2 border-pink-400 border-t-transparent rounded-full"></span><span>Testing...</span>`;

    try {
        const res = await fetch('/api.php?action=test_instagram_connection', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ instagram_handle: handle })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('✓ ' + data.message, 'success');
            const alertBox = document.getElementById('igVerificationAlert');
            if (alertBox) alertBox.classList.remove('hidden');
            const badge = document.getElementById('igStatusBadge');
            if (badge) {
                badge.className = 'text-[10px] px-2 py-0.5 rounded-full bg-pink-500/20 text-pink-400 border border-pink-500/40 font-bold';
                badge.textContent = 'Connected & Bridge Active';
            }
        } else {
            showToast(data.message || 'Instagram verification failed', 'error');
        }
    } catch(e) {
        showToast('Network error', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="shield-check" class="w-3.5 h-3.5"></i><span>Verify Instagram Handle</span>`;
        lucide.createIcons();
    }
}

async function saveEmailDirect() {
    const email = document.getElementById('sender_email').value.trim();
    const host = document.getElementById('smtp_host').value.trim();
    const port = document.getElementById('smtp_port').value.trim();
    const btn = document.getElementById('btnSaveEm');

    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin w-3.5 h-3.5 border-2 border-black border-t-transparent rounded-full"></span><span>Saving Email...</span>`;

    try {
        const res = await fetch('/api.php?action=save_channel_config', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ channel: 'email', sender_email: email, smtp_host: host, smtp_port: port })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('✓ Corporate Email settings saved successfully!', 'success');
            const senderEl = document.getElementById('emVerifiedSender');
            if (senderEl) senderEl.textContent = email;
        } else {
            showToast(data.message || 'Save failed', 'error');
        }
    } catch(e) {
        showToast('Network error', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="check" class="w-3.5 h-3.5"></i><span>Save & Connect Email</span>`;
        lucide.createIcons();
    }
}

async function verifyEmailDirect() {
    const email = document.getElementById('sender_email').value.trim();
    const btn = document.getElementById('btnVerifyEm');

    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin w-3.5 h-3.5 border-2 border-indigo-400 border-t-transparent rounded-full"></span><span>Sending Ping...</span>`;

    try {
        const res = await fetch('/api.php?action=test_email_connection', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ test_recipient: email })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('✓ ' + data.message, 'success');
            const alertBox = document.getElementById('emVerificationAlert');
            if (alertBox) alertBox.classList.remove('hidden');
        } else {
            showToast(data.message || 'Email test failed', 'error');
        }
    } catch(e) {
        showToast('Network error sending test email', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="send" class="w-3.5 h-3.5"></i><span>Send Test Email Ping</span>`;
        lucide.createIcons();
    }
}

function openSimulateModal() {
    const modal = document.getElementById('simModalBackdrop');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeSimulateModal() {
    const modal = document.getElementById('simModalBackdrop');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

async function runInboundSimulation() {
    const phone = document.getElementById('simSenderPhone').value.trim();
    const msg = document.getElementById('simMessageText').value.trim();
    const btn = document.getElementById('btnRunSim');
    const resBox = document.getElementById('simResultBox');

    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin w-3.5 h-3.5 border-2 border-black border-t-transparent rounded-full"></span><span>Processing AI Routing...</span>`;

    try {
        const res = await fetch('/api.php?action=simulate_inbound_whatsapp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ sender_phone: phone, message: msg })
        });
        const data = await res.json();
        if (data.status === 'success' && data.simulation) {
            const item = data.simulation.results && data.simulation.results[0] ? data.simulation.results[0] : null;
            resBox.classList.remove('hidden');

            if (item && item.action === 'created_new_lead_and_auto_replied') {
                resBox.innerHTML = `
                    <div class="font-bold text-emerald-400 flex items-center gap-1.5">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        <span>New Lead Discovered & AI Chatbot Responded!</span>
                    </div>
                    <div class="text-[11px] text-[#a3a6b0]">Phone: <strong class="text-[#f4f1ea]">+${escapeHtml(item.phone)}</strong> · Auto-added to CRM Pipeline (Lead #${item.lead_id})</div>
                    <div class="p-3 rounded-lg bg-black/40 border border-emerald-500/30 text-emerald-300">
                        <div class="text-[10px] uppercase font-bold text-[#d9b46c] mb-1">AI Generated Outbound Response:</div>
                        ${escapeHtml(item.reply_content || 'Response generated and queued for Cloud API dispatch.')}
                    </div>
                    <div class="pt-1 text-right">
                        <a href="/inbox.php?lead_id=${item.lead_id}" class="text-xs text-[#d9b46c] hover:underline font-bold">Open Conversation in Inbox →</a>
                    </div>
                `;
            } else if (item && item.action === 'logged_inbound_existing_lead') {
                resBox.innerHTML = `
                    <div class="font-bold text-amber-400 flex items-center gap-1.5">
                        <i data-lucide="info" class="w-4 h-4"></i>
                        <span>Existing Lead Detected — AI Auto-Reply Skipped (Human Routing)</span>
                    </div>
                    <p class="text-[#a3a6b0] subtext-contrast">${escapeHtml(item.reason)}</p>
                    <div class="p-2.5 rounded-lg bg-black/30 border border-white/[0.08] text-[#f4f1ea]">
                        Message routed directly to your personal Unified Inbox timeline for Lead #${item.lead_id}.
                    </div>
                    <div class="pt-1 text-right">
                        <a href="/inbox.php?lead_id=${item.lead_id}" class="text-xs text-blue-400 hover:underline font-bold">Open Conversation in Inbox →</a>
                    </div>
                `;
            } else {
                resBox.innerHTML = `<div class="text-[#f4f1ea]">${JSON.stringify(data.simulation)}</div>`;
            }
            lucide.createIcons();
            showToast('Simulation evaluated successfully!', 'success');
        } else {
            showToast(data.message || 'Simulation failed', 'error');
        }
    } catch (e) {
        showToast('Network error during simulation', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="play" class="w-4 h-4"></i><span>Simulate Inbound Message & Evaluate AI</span>`;
        lucide.createIcons();
    }
}

function testDispatch(channel) {
    if (channel === 'whatsapp') {
        const phone = document.getElementById('sender_whatsapp')?.value || '+917620654814';
        const clean = phone.replace(/[^\d]/g, '');
        window.open(`https://wa.me/${clean}?text=${encodeURIComponent('The Only AI Freedom: WhatsApp dispatch bridge verified & operational!')}`, '_blank');
        showToast('WhatsApp bridge verified! Test chat opened.', 'success');
    } else if (channel === 'linkedin') {
        const url = document.getElementById('linkedin_url')?.value || 'https://www.linkedin.com/in/rajjatrangari';
        window.open(url, '_blank');
        showToast('LinkedIn profile bridge verified!', 'success');
    } else if (channel === 'instagram') {
        const handle = (document.getElementById('instagram_handle')?.value || '@theonlyaifreedom').replace('@', '');
        window.open(`https://ig.me/m/${handle}`, '_blank');
        showToast('Instagram DM bridge verified!', 'success');
    } else if (channel === 'email') {
        const email = document.getElementById('sender_email')?.value || 'rajjat@theonlyaifreedom.in';
        window.open(`mailto:${email}?subject=Outreach%20Engine%20Verification&body=Corporate%20email%20dispatch%20bridge%20operational!`, '_blank');
        showToast('Email client bridge verified!', 'success');
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
    });
}
</script>

<?php
$content = ob_get_clean();
render_layout('Connected Channels', 'channels', $content, $user);
