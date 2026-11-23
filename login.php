<?php
// Outreach AI Freedom - Authentication & Login (The Only AI Freedom Theme)
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';

$error = '';
$redirect = $_GET['redirect'] ?? '/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email and password.';
    } else {
        $user = one("SELECT * FROM users WHERE LOWER(email) = ?", [$email]);
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['outreach_user_id'] = $user['id'];
            header('Location: ' . (filter_var($redirect, FILTER_VALIDATE_URL) ? '/index.php' : $redirect));
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?><!DOCTYPE html>
<html lang="en" class="h-full antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Outreach — The Only AI Freedom</title>
    <link rel="icon" type="image/png" href="/assets/brand/logo-emblem.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/assets/css/app.css?v=8">
    <script src="/assets/js/theme.js?v=8"></script>
</head>
<body class="h-full flex items-center justify-center p-4 font-sans">
    
    <!-- Ambient Background -->
    <div class="ambient" aria-hidden="true"></div>
    <div class="grain" aria-hidden="true"></div>

    <div class="w-full max-w-md space-y-6 relative z-10">
        
        <!-- Logo & Branding -->
        <div class="text-center space-y-3">
            <div class="inline-flex items-center justify-center p-3 rounded-2xl mb-1 logo-emblem-badge" style="background-color: #07080c !important; border: 1.5px solid #d9b46c !important; box-shadow: 0 8px 30px rgba(0,0,0,0.6) !important;">
                <img src="/assets/brand/logo-emblem.png" alt="The Only AI Freedom" class="h-12 w-auto object-contain">
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-[#f4f1ea] tracking-tight">
                    The Only <span class="gold-text">Ai</span> Freedom
                </h1>
                <p class="text-xs text-[#a3a6b0] mt-1 font-mono">Outreach OS • Qualified B2B Client Engine</p>
            </div>
        </div>

        <!-- Login Card -->
        <div class="glass-panel rounded-2xl p-6 sm:p-8 border border-white/[0.08] shadow-[0_20px_80px_rgba(0,0,0,0.8)] space-y-5">
            
            <div class="flex items-center justify-between pb-3 border-b border-white/[0.08]">
                <h2 class="font-bold text-[#f4f1ea] text-sm tracking-tight">Sign In to Outreach OS</h2>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#d9b46c]/15 text-[#f3dca4] border border-[#d9b46c]/30 font-mono">v1.0 Live</span>
            </div>

            <?php if ($error): ?>
                <div class="p-3.5 rounded-xl bg-rose-950/80 border border-rose-500/40 text-xs text-rose-200 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-400 flex-shrink-0"></i>
                    <span><?= h($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Email Address</label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-4 h-4 text-[#6e7280] absolute left-3.5 top-3"></i>
                        <input type="email" name="email" required autofocus
                               value="<?= h($_POST['email'] ?? 'outreach@theonlyaifreedom.in') ?>" 
                               class="w-full bg-[#0c0e14] border border-white/[0.1] rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-semibold text-[#f4f1ea]">Password</label>
                        <span class="text-[11px] text-[#a3a6b0]">Demo: <strong class="text-[#f3dca4] font-mono">Outreach_Freedom_2026!</strong></span>
                    </div>
                    <div class="relative">
                        <i data-lucide="lock" class="w-4 h-4 text-[#6e7280] absolute left-3.5 top-3"></i>
                        <input type="password" name="password" required 
                               value="Outreach_Freedom_2026!"
                               class="w-full bg-[#0c0e14] border border-white/[0.1] rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                    </div>
                </div>

                <button type="submit" class="btn-gold-cta w-full py-2.5 px-4 rounded-xl text-black font-extrabold text-xs shadow-lg transition flex items-center justify-center gap-2">
                    <span>Sign In to Outreach</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>

            <div class="pt-2 text-center text-xs text-[#a3a6b0]">
                Need a workspace for your agency? 
                <a href="/signup.php" class="text-[#d9b46c] font-bold hover:underline">Create new workspace</a>
            </div>
        </div>

        <div class="text-center text-[11px] text-[#6e7280]">
            &copy; 2026 The Only AI Freedom. All rights reserved.
        </div>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
