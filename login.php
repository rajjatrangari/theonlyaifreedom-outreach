<?php
// Outreach AI Freedom - Authentication & Login
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
<html lang="en" class="dark h-full bg-[#090d16] text-slate-100 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Outreach AI Freedom</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="h-full flex items-center justify-center p-4 bg-[#090d16] font-sans">
    
    <div class="w-full max-w-md space-y-6">
        
        <!-- Logo & Branding -->
        <div class="text-center space-y-2">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 via-teal-400 to-indigo-500 p-0.5 shadow-xl shadow-emerald-500/20 mb-2">
                <div class="w-full h-full bg-[#090d16] rounded-[14px] flex items-center justify-center">
                    <i data-lucide="crosshair" class="w-7 h-7 text-emerald-400"></i>
                </div>
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Outreach AI Freedom</h1>
            <p class="text-xs text-slate-400">Targeted lead scraping, verified contact discovery & direct multi-channel CRM</p>
        </div>

        <!-- Login Form Card -->
        <div class="glass-panel rounded-2xl p-6 sm:p-8 border border-slate-800 shadow-2xl space-y-5">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h2 class="font-bold text-white text-base">Sign In to Dashboard</h2>
                <span class="text-xs text-emerald-400 font-mono">v1.0 Live</span>
            </div>

            <?php if ($error): ?>
                <div class="p-3.5 rounded-xl bg-rose-950/80 border border-rose-500/40 text-xs text-rose-200 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-400 flex-shrink-0"></i>
                    <span><?= h($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Email Address</label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-4 h-4 text-slate-500 absolute left-3.5 top-3"></i>
                        <input type="email" name="email" required autofocus
                               value="<?= h($_POST['email'] ?? 'outreach@theonlyaifreedom.in') ?>" 
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-semibold text-slate-300">Password</label>
                        <span class="text-[11px] text-slate-400">Demo: <strong class="text-slate-300 font-mono">Outreach_Freedom_2026!</strong></span>
                    </div>
                    <div class="relative">
                        <i data-lucide="lock" class="w-4 h-4 text-slate-500 absolute left-3.5 top-3"></i>
                        <input type="password" name="password" required 
                               value="Outreach_Freedom_2026!"
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-black font-bold text-xs shadow-lg shadow-emerald-500/25 transition flex items-center justify-center gap-2">
                    <span>Sign In to Outreach</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>

            <div class="pt-2 text-center text-xs text-slate-400">
                Need a new account? 
                <a href="/signup.php" class="text-emerald-400 font-semibold hover:underline">Create new workspace</a>
            </div>
        </div>

        <div class="text-center text-[11px] text-slate-600">
            &copy; 2026 The Only AI Freedom Ecosystem. All rights reserved.
        </div>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
