<?php
// Outreach AI Freedom - User Registration & Instant Workspace Provisioning
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
<html lang="en" class="dark h-full bg-[#090d16] text-slate-100 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Outreach AI Freedom</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="h-full flex items-center justify-center p-4 bg-[#090d16] font-sans">
    
    <div class="w-full max-w-lg space-y-6">
        
        <!-- Logo -->
        <div class="text-center space-y-2">
            <div class="inline-flex w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 via-teal-400 to-indigo-500 p-0.5 shadow-xl shadow-emerald-500/20 mb-2">
                <div class="w-full h-full bg-[#090d16] rounded-[14px] flex items-center justify-center">
                    <i data-lucide="crosshair" class="w-6 h-6 text-emerald-400"></i>
                </div>
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Create Your Outreach Account</h1>
            <p class="text-xs text-slate-400">Define what you do and start pulling verified high-budget leads in seconds</p>
        </div>

        <div class="glass-panel rounded-2xl p-6 sm:p-8 border border-slate-800 shadow-2xl space-y-5">
            
            <?php if ($error): ?>
                <div class="p-3.5 rounded-xl bg-rose-950/80 border border-rose-500/40 text-xs text-rose-200 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-400 flex-shrink-0"></i>
                    <span><?= h($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Your Full Name *</label>
                        <input type="text" name="name" required value="<?= h($_POST['name'] ?? '') ?>" placeholder="e.g. Rajjat Rangari" 
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Your Email Address *</label>
                        <input type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>" placeholder="name@company.com" 
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Password *</label>
                        <input type="password" name="password" required placeholder="Minimum 6 characters" 
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Your WhatsApp Number *</label>
                        <input type="text" name="sender_whatsapp" required value="<?= h($_POST['sender_whatsapp'] ?? '') ?>" placeholder="+917620654814" 
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-800">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-400 mb-3">Your Business Offering & What You Do</div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Business / Agency Name *</label>
                            <input type="text" name="business_name" required value="<?= h($_POST['business_name'] ?? '') ?>" placeholder="e.g. Apex AI Freedom" 
                                   class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Your Industry / Niche *</label>
                            <input type="text" name="business_niche" required value="<?= h($_POST['business_niche'] ?? '') ?>" placeholder="e.g. B2B Automation Consulting" 
                                   class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">What You Do & What Leads You Want *</label>
                        <textarea name="business_offering" rows="2" required placeholder="Describe your service/product and the type of high-paying leads you need (e.g. We provide AI client conversion funnels for luxury interior designers and dental clinics)..." 
                                  class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 custom-scroll"><?= h($_POST['business_offering'] ?? '') ?></textarea>
                    </div>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-black font-bold text-xs shadow-lg shadow-emerald-500/25 transition flex items-center justify-center gap-2">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                    <span>Create Account & Discover Leads</span>
                </button>
            </form>

            <div class="pt-2 text-center text-xs text-slate-400">
                Already have an account? 
                <a href="/login.php" class="text-emerald-400 font-semibold hover:underline">Sign in here</a>
            </div>
        </div>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
