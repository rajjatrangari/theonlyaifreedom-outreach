<?php
// Outreach AI Freedom - Connected Communication Channels Hub
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/layout.php';

$user = require_auth();
$userId = (int)$user['id'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senderWhatsapp = trim($_POST['sender_whatsapp'] ?? '');
    $linkedinUrl = trim($_POST['linkedin_url'] ?? '');
    $senderEmail = trim($_POST['sender_email'] ?? '');
    $instagramHandle = trim($_POST['instagram_handle'] ?? '');
    $twitterHandle = trim($_POST['twitter_handle'] ?? '');

    q("UPDATE users SET
        sender_whatsapp = ?,
        linkedin_url = ?,
        sender_email = ?,
        instagram_handle = ?,
        twitter_handle = ?,
        updated_at = ?
       WHERE id = ?", [
        $senderWhatsapp,
        $linkedinUrl,
        $senderEmail,
        $instagramHandle,
        $twitterHandle,
        now(),
        $userId
    ]);

    $user = one("SELECT * FROM users WHERE id = ?", [$userId]);
    $message = 'Connected channels and dispatch credentials updated successfully!';
    $messageType = 'success';
}

ob_start();
?>

<div class="max-w-4xl mx-auto space-y-8">
    
    <div>
        <h2 class="text-xl font-bold text-[#f4f1ea] tracking-tight flex items-center gap-2">
            <span>Connected Communication Channels</span>
            <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 font-mono">Zero Restrictions</span>
        </h2>
        <p class="text-xs text-[#a3a6b0] mt-1">
            Link your personal and business communication channels. Reach out directly from your dashboard using seamless direct app bridges without getting blocked or needing developer app approvals.
        </p>
    </div>

    <?php if ($message): ?>
        <div class="p-4 rounded-xl border flex items-center gap-3 text-xs font-semibold <?= $messageType === 'success' ? 'bg-emerald-950/80 text-emerald-200 border-emerald-500/40' : 'bg-rose-950/80 text-rose-200 border-rose-500/40' ?>">
            <i data-lucide="<?= $messageType === 'success' ? 'check-circle' : 'alert-triangle' ?>" class="w-4 h-4"></i>
            <span><?= h($message) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        
        <!-- WhatsApp Channel Card -->
        <div class="glass-panel rounded-2xl p-6 border border-white/[0.08] space-y-4">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
                        <i data-lucide="message-circle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#f4f1ea] text-sm">WhatsApp Direct Bridge</h3>
                        <p class="text-xs text-[#a3a6b0]">1-Click instant chat dispatch via wa.me protocol (desktop & mobile compatible).</p>
                    </div>
                </div>
                <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-bold flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Ready</span>
                </span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Your WhatsApp Phone Number (+ Country Code)</label>
                <input type="text" name="sender_whatsapp" value="<?= h($user['sender_whatsapp'] ?: $user['sender_phone']) ?>" 
                       placeholder="+917620654814" 
                       class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                <p class="text-[11px] text-[#a3a6b0] mt-1">Used to establish session origin and signature for click-to-chat links.</p>
            </div>
        </div>

        <!-- LinkedIn Channel Card -->
        <div class="glass-panel rounded-2xl p-6 border border-white/[0.08] space-y-4">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 border border-blue-500/30 flex items-center justify-center">
                        <i data-lucide="linkedin" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#f4f1ea] text-sm">LinkedIn Direct Message Bridge</h3>
                        <p class="text-xs text-[#a3a6b0]">Seamlessly connects your profile for 1-click InMail / profile outreach.</p>
                    </div>
                </div>
                <span class="text-xs px-2.5 py-1 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/40 font-bold flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                    <span>Ready</span>
                </span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Your LinkedIn Profile / Company URL</label>
                <input type="url" name="linkedin_url" value="<?= h($user['linkedin_url']) ?>" 
                       placeholder="https://linkedin.com/in/yourname" 
                       class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
            </div>
        </div>

        <!-- Email Channel Card -->
        <div class="glass-panel rounded-2xl p-6 border border-white/[0.08] space-y-4">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center">
                        <i data-lucide="mail" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#f4f1ea] text-sm">Email Dispatcher</h3>
                        <p class="text-xs text-[#a3a6b0]">Direct mailto / webmail dispatcher with pre-filled subject and tailored value proposition.</p>
                    </div>
                </div>
                <span class="text-xs px-2.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 font-bold flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                    <span>Ready</span>
                </span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Your Sender / Outreach Email</label>
                <input type="email" name="sender_email" value="<?= h($user['sender_email'] ?: $user['email']) ?>" 
                       placeholder="outreach@theonlyaifreedom.in" 
                       class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
            </div>
        </div>

        <!-- Instagram Channel Card -->
        <div class="glass-panel rounded-2xl p-6 border border-white/[0.08] space-y-4">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-pink-500/20 text-pink-400 border border-pink-500/30 flex items-center justify-center">
                        <i data-lucide="instagram" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#f4f1ea] text-sm">Instagram Direct Message</h3>
                        <p class="text-xs text-[#a3a6b0]">Deep link bridge to directly initiate DM conversations on Instagram.</p>
                    </div>
                </div>
                <span class="text-xs px-2.5 py-1 rounded-full bg-pink-500/20 text-pink-300 border border-pink-500/40 font-bold flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-pink-400"></span>
                    <span>Ready</span>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Instagram Business Handle</label>
                    <input type="text" name="instagram_handle" value="<?= h($user['instagram_handle']) ?>" 
                           placeholder="@yourbrand" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Twitter / X Handle</label>
                    <input type="text" name="twitter_handle" value="<?= h($user['twitter_handle']) ?>" 
                           placeholder="@yourhandle" 
                           class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <button type="submit" class="btn-gold-cta px-6 py-2.5 rounded-xl text-black font-extrabold text-xs shadow-lg transition flex items-center gap-2">
                <i data-lucide="check" class="w-4 h-4"></i>
                <span>Save Channel Connections</span>
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
render_layout('Connected Channels', 'channels', $content, $user);
