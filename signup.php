<?php
// Outreach AI Freedom - User Registration (The Only AI Freedom Theme)
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/scraper_engine.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $businessName = trim($_POST['business_name'] ?? '');
    $businessNiche = trim($_POST['business_niche'] ?? '');
    $businessOffering = trim($_POST['business_offering'] ?? '');
    $senderWhatsapp = trim($_POST['sender_whatsapp'] ?? '');
    $targetIcp = trim($_POST['target_icp'] ?? '');

    if (empty($name) || empty($email) || empty($password) || empty($businessName)) {
        $error = 'Please fill in all required fields to provision your workspace.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $exists = val("SELECT id FROM users WHERE LOWER(email) = ?", [$email]);
        if ($exists) {
            $error = 'An account with this email already exists. Please log in.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $cleanWhatsapp = QualificationEngine::cleanPhone($senderWhatsapp);

            q("INSERT INTO users (
                name, email, password_hash, role,
                business_name, business_niche, business_offering,
                business_pricing, target_icp, usp_hook,
                sender_name, sender_title, sender_email, sender_phone, sender_whatsapp,
                default_channel, min_score_filter, created_at, updated_at
            ) VALUES (
                ?, ?, ?, 'user',
                ?, ?, ?,
                '₹49,999/mo', ?, 'Direct verified outreach engine live in 7 days.',
                ?, 'Founder', ?, ?, ?,
                'whatsapp', 75, ?, ?
            )", [
                $name,
                $email,
                $hash,
                $businessName,
                $businessNiche,
                $businessOffering,
                $targetIcp,
                $name,
                $email,
                $cleanWhatsapp,
                $cleanWhatsapp,
                now(),
                now()
            ]);

            $newUserId = last_id();
            $_SESSION['outreach_user_id'] = $newUserId;

            // Automatically seed initial verified qualified prospects for this niche
            if (!empty($businessNiche)) {
                ScraperEngine::searchAndEnrich($newUserId, $businessNiche, 'Mumbai, India', 75, 5);
            }

            header('Location: /index.php');
            exit;
        }
    }
}
?><!DOCTYPE html>
<html lang="en" class="h-full antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Outreach — The Only AI Freedom</title>
    <link rel="icon" type="image/png" href="/assets/brand/logo-emblem.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/assets/css/app.css?v=10">
    <script src="/assets/js/theme.js?v=9"></script>
</head>
<body class="h-full flex items-center justify-center p-4 font-sans">
    
    <!-- Ambient Background -->
    <div class="ambient" aria-hidden="true"></div>
    <div class="grain" aria-hidden="true"></div>

    <div class="w-full max-w-lg space-y-6 relative z-10">
        
        <!-- Logo -->
        <div class="text-center space-y-3">
            <div class="inline-flex items-center justify-center p-3 rounded-2xl mb-1 logo-emblem-badge" style="background-color: #07080c !important; border: 1.5px solid #d9b46c !important; box-shadow: 0 8px 30px rgba(0,0,0,0.6) !important;">
                <img src="/assets/brand/logo-emblem.png" alt="The Only AI Freedom" class="h-12 w-auto object-contain">
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-[#f4f1ea] tracking-tight">
                    The Only <span class="gold-text">Ai</span> Freedom
                </h1>
                <p class="text-xs text-[#a3a6b0] mt-1 font-mono">Create Your Outreach OS Workspace</p>
            </div>
        </div>

        <div class="glass-panel rounded-2xl p-6 sm:p-8 border border-white/[0.08] shadow-[0_20px_80px_rgba(0,0,0,0.8)] space-y-5">
            
            <?php if ($error): ?>
                <div class="p-3.5 rounded-xl bg-rose-950/80 border border-rose-500/40 text-xs text-rose-200 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-400 flex-shrink-0"></i>
                    <span><?= h($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-[#f4f1ea] mb-1">Your Full Name *</label>
                        <input type="text" name="name" required value="<?= h($_POST['name'] ?? '') ?>" placeholder="e.g. Rajjat Rangari" 
                               class="w-full bg-[#0c0e14] border border-white/[0.1] rounded-xl px-3 py-2 text-xs text-white placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#f4f1ea] mb-1">Your Email Address *</label>
                        <input type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>" placeholder="name@company.com" 
                               class="w-full bg-[#0c0e14] border border-white/[0.1] rounded-xl px-3 py-2 text-xs text-white placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-[#f4f1ea] mb-1">Password *</label>
                        <input type="password" name="password" required placeholder="Minimum 6 characters" 
                               class="w-full bg-[#0c0e14] border border-white/[0.1] rounded-xl px-3 py-2 text-xs text-white placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#f4f1ea] mb-1">Your WhatsApp Number *</label>
                        <input type="text" name="sender_whatsapp" required value="<?= h($_POST['sender_whatsapp'] ?? '') ?>" placeholder="+917620654814" 
                               class="w-full bg-[#0c0e14] border border-white/[0.1] rounded-xl px-3 py-2 text-xs text-white placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                    </div>
                </div>

                <div class="pt-2 border-t border-white/[0.08]">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-[#d9b46c] mb-3">Your Offering & Scraping Goals</div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#f4f1ea] mb-1">Business / Agency Name *</label>
                            <input type="text" name="business_name" required value="<?= h($_POST['business_name'] ?? '') ?>" placeholder="e.g. Apex AI Freedom" 
                                   class="w-full bg-[#0c0e14] border border-white/[0.1] rounded-xl px-3 py-2 text-xs text-white placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#f4f1ea] mb-1">Your Industry / Niche *</label>
                            <input type="text" name="business_niche" required value="<?= h($_POST['business_niche'] ?? '') ?>" placeholder="e.g. AI Automation & Funnels" 
                                   class="w-full bg-[#0c0e14] border border-white/[0.1] rounded-xl px-3 py-2 text-xs text-white placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#f4f1ea] mb-1">What You Do & What Leads You Want *</label>
                        <textarea name="business_offering" rows="2" required placeholder="Describe your service/product and the type of high-paying leads you need (e.g. We provide AI client conversion funnels for luxury interior designers and dental clinics)..." 
                                  class="w-full bg-[#0c0e14] border border-white/[0.1] rounded-xl p-3 text-xs text-white placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c] custom-scroll"><?= h($_POST['business_offering'] ?? '') ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn-gold-cta w-full py-2.5 px-4 rounded-xl text-black font-extrabold text-xs shadow-lg transition flex items-center justify-center gap-2">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                    <span>Create Account & Discover Leads</span>
                </button>
            </form>

            <div class="pt-2 text-center text-xs text-[#a3a6b0]">
                Already have an account? 
                <a href="/login.php" class="text-[#d9b46c] font-bold hover:underline">Sign in here</a>
            </div>
        </div>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
