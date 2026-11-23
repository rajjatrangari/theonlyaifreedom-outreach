<?php
// Outreach AI Freedom - Business Offering, ICP & Onboarding Setup
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/layout.php';

$user = require_auth();
$userId = (int)$user['id'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $businessName = trim($_POST['business_name'] ?? '');
    $businessNiche = trim($_POST['business_niche'] ?? '');
    $businessOffering = trim($_POST['business_offering'] ?? '');
    $businessPricing = trim($_POST['business_pricing'] ?? '');
    $targetIcp = trim($_POST['target_icp'] ?? '');
    $uspHook = trim($_POST['usp_hook'] ?? '');
    $senderName = trim($_POST['sender_name'] ?? '');
    $senderTitle = trim($_POST['sender_title'] ?? '');
    $senderEmail = trim($_POST['sender_email'] ?? '');
    $senderPhone = trim($_POST['sender_phone'] ?? '');
    $senderWhatsapp = trim($_POST['sender_whatsapp'] ?? $senderPhone);
    $linkedinUrl = trim($_POST['linkedin_url'] ?? '');
    $instagramHandle = trim($_POST['instagram_handle'] ?? '');
    $defaultChannel = trim($_POST['default_channel'] ?? 'whatsapp');
    $minScore = max(50, min(95, (int)($_POST['min_score_filter'] ?? 70)));

    q("UPDATE users SET
        business_name = ?,
        business_niche = ?,
        business_offering = ?,
        business_pricing = ?,
        target_icp = ?,
        usp_hook = ?,
        sender_name = ?,
        sender_title = ?,
        sender_email = ?,
        sender_phone = ?,
        sender_whatsapp = ?,
        linkedin_url = ?,
        instagram_handle = ?,
        default_channel = ?,
        min_score_filter = ?,
        updated_at = ?
       WHERE id = ?", [
        $businessName,
        $businessNiche,
        $businessOffering,
        $businessPricing,
        $targetIcp,
        $uspHook,
        $senderName,
        $senderTitle,
        $senderEmail,
        $senderPhone,
        $senderWhatsapp,
        $linkedinUrl,
        $instagramHandle,
        $defaultChannel,
        $minScore,
        now(),
        $userId
    ]);

    $user = one("SELECT * FROM users WHERE id = ?", [$userId]);
    $message = 'Business offering & outreach profile updated successfully! AI generator is now synced.';
    $messageType = 'success';
}

ob_start();
?>

<div class="max-w-4xl mx-auto space-y-8">
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                <span>Business Offering & ICP Profile</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-mono">Personalized AI Brain</span>
            </h2>
            <p class="text-xs text-slate-400 mt-1">Our AI analyzes your business model and target ICP to automatically craft bespoke, high-converting outreach hooks for every scraped lead.</p>
        </div>
        <a href="/scraper.php" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl transition flex items-center gap-2">
            <i data-lucide="radar" class="w-4 h-4 text-emerald-400"></i>
            <span>Test Scraper</span>
        </a>
    </div>

    <?php if ($message): ?>
        <div class="p-4 rounded-xl border flex items-center gap-3 text-xs font-semibold <?= $messageType === 'success' ? 'bg-emerald-950/80 text-emerald-200 border-emerald-500/40' : 'bg-rose-950/80 text-rose-200 border-rose-500/40' ?>">
            <i data-lucide="<?= $messageType === 'success' ? 'check-circle' : 'alert-triangle' ?>" class="w-4 h-4"></i>
            <span><?= h($message) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        
        <!-- Section 1: Business Identity & Offering -->
        <div class="glass-panel rounded-2xl p-6 border border-slate-800 space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-800/80">
                <i data-lucide="briefcase" class="w-4 h-4 text-emerald-400"></i>
                <h3 class="font-bold text-white text-sm">1. What You Do & Your Core Offering</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Business / Agency Name *</label>
                    <input type="text" name="business_name" required value="<?= h($user['business_name']) ?>" 
                           placeholder="e.g. Apex AI Freedom, Digital Growth Partners..." 
                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Your Industry / Niche *</label>
                    <input type="text" name="business_niche" required value="<?= h($user['business_niche']) ?>" 
                           placeholder="e.g. AI Automation Consulting, High-End Web Development, B2B Lead Gen..." 
                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Detailed Service / Solution Offering *</label>
                <textarea name="business_offering" rows="3" required
                          placeholder="Describe exactly what transformation, system or result you deliver (e.g. We implement custom WhatsApp CRM automations, lead qualification bots, and automated appointment setting that delivers 15-20 qualified high-ticket consultations every month)..." 
                          class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 custom-scroll leading-relaxed"><?= h($user['business_offering']) ?></textarea>
                <p class="text-[11px] text-slate-400 mt-1">The AI quotes this in outreach pitches so prospects immediately understand your exact value.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Pricing / Retainer Ticket Size</label>
                    <input type="text" name="business_pricing" value="<?= h($user['business_pricing']) ?>" 
                           placeholder="e.g. ₹49,999/mo, $2,500/project, 15% revenue share..." 
                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Unique Selling Proposition (USP) & Hook</label>
                    <input type="text" name="usp_hook" value="<?= h($user['usp_hook']) ?>" 
                           placeholder="e.g. Live pilot launched in 7 days or 100% money-back guarantee..." 
                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>
            </div>
        </div>

        <!-- Section 2: Target ICP & Scraping Criteria -->
        <div class="glass-panel rounded-2xl p-6 border border-slate-800 space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-800/80">
                <i data-lucide="target" class="w-4 h-4 text-blue-400"></i>
                <h3 class="font-bold text-white text-sm">2. Ideal Customer Profile (ICP) & Scraping Filters</h3>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Target Audience & Industry Criteria</label>
                <textarea name="target_icp" rows="2" 
                          placeholder="e.g. High-end Jewellery Showrooms, Luxury Real Estate Developers, Multi-speciality Dental Clinics, Boutique Law Firms with 10+ employees..." 
                          class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 custom-scroll leading-relaxed"><?= h($user['target_icp']) ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Lead Paying Capacity Filter (Min Q-Score)</label>
                    <select name="min_score_filter" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
                        <option value="70" <?= ($user['min_score_filter'] == 70) ? 'selected' : '' ?>>70+ (Recommended - Verified High-Growth Businesses)</option>
                        <option value="80" <?= ($user['min_score_filter'] == 80) ? 'selected' : '' ?>>80+ (Strict - Enterprise / High-Budget Brands Only)</option>
                        <option value="85" <?= ($user['min_score_filter'] == 85) ? 'selected' : '' ?>>85+ (Elite Tier - Multi-location / Flagships Only)</option>
                        <option value="60" <?= ($user['min_score_filter'] == 60) ? 'selected' : '' ?>>60+ (Broad - Includes smaller local businesses)</option>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">Filters out dead websites, broken WhatsApp links, and low-budget entries automatically.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Default Outreach Channel</label>
                    <select name="default_channel" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
                        <option value="whatsapp" <?= ($user['default_channel'] === 'whatsapp') ? 'selected' : '' ?>>WhatsApp (Highest Response Rate in India & Global)</option>
                        <option value="linkedin" <?= ($user['default_channel'] === 'linkedin') ? 'selected' : '' ?>>LinkedIn InMail / DM</option>
                        <option value="email" <?= ($user['default_channel'] === 'email') ? 'selected' : '' ?>>Email</option>
                        <option value="instagram" <?= ($user['default_channel'] === 'instagram') ? 'selected' : '' ?>>Instagram DM</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 3: Sender Contact & Social Handles -->
        <div class="glass-panel rounded-2xl p-6 border border-slate-800 space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-800/80">
                <i data-lucide="user-check" class="w-4 h-4 text-purple-400"></i>
                <h3 class="font-bold text-white text-sm">3. Your Direct Sender Details & Connected Profiles</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Sender Full Name *</label>
                    <input type="text" name="sender_name" required value="<?= h($user['sender_name'] ?: $user['name']) ?>" 
                           placeholder="e.g. Rajjat Rangari" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Sender Job Title</label>
                    <input type="text" name="sender_title" value="<?= h($user['sender_title'] ?: 'Founder & CEO') ?>" 
                           placeholder="e.g. Managing Director / Growth Partner" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Sender Email</label>
                    <input type="email" name="sender_email" value="<?= h($user['sender_email'] ?: $user['email']) ?>" 
                           placeholder="e.g. rajjat@theonlyaifreedom.in" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Sender WhatsApp (+ Country Code) *</label>
                    <input type="text" name="sender_whatsapp" required value="<?= h($user['sender_whatsapp'] ?: $user['sender_phone']) ?>" 
                           placeholder="e.g. +917620654814" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">LinkedIn Profile URL</label>
                    <input type="url" name="linkedin_url" value="<?= h($user['linkedin_url']) ?>" 
                           placeholder="https://linkedin.com/in/username" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Instagram Handle</label>
                    <input type="text" name="instagram_handle" value="<?= h($user['instagram_handle']) ?>" 
                           placeholder="@yourbrand" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="/index.php" class="px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 transition">
                Cancel
            </a>
            <button type="submit" class="btn-gold-cta px-6 py-2.5 rounded-xl text-black font-extrabold text-xs shadow-lg transition flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span>Save Offering & Sync AI</span>
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
render_layout('Offering & ICP Setup', 'onboarding', $content, $user);
