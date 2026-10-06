<?php
// pages/auth/login.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/pages/dashboard/index.php');
    exit;
}
$error = getFlash();
$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sistem Monitoring PLTS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: { 800:'#283593', 900:'#1a237e' } } } }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-primary-900 via-blue-900 to-primary-800 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">
    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <!-- Header -->
        <div class="bg-primary-900 px-8 py-8 text-center">
            <div class="w-16 h-16 bg-yellow-400 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="bi bi-sun-fill text-primary-900 text-3xl"></i>
            </div>
            <h1 class="text-white text-xl font-bold">Sistem Monitoring PLTS</h1>
            <p class="text-blue-200 text-sm mt-1">PT Energy Lima Pilar</p>
        </div>

        <!-- Form -->
        <div class="px-8 py-8">
            <h2 class="text-slate-800 text-lg font-semibold mb-6">Masuk ke Sistem</h2>

            <?php if ($error && $error['type'] === 'error'): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-4 flex items-center gap-2">
                <i class="bi bi-exclamation-triangle"></i>
                <?= sanitize($error['message']) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/api/auth/login.php" id="loginForm">
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="bi bi-envelope text-slate-400"></i>
                            </div>
                            <input type="email" name="email" required autofocus
                                value="<?= sanitize($_POST['email'] ?? '') ?>"
                                placeholder="email@example.com"
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-800 focus:border-transparent transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="bi bi-lock text-slate-400"></i>
                            </div>
                            <input type="password" name="password" id="pwInput" required
                                placeholder="••••••••"
                                class="w-full pl-10 pr-10 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-800 focus:border-transparent transition-all">
                            <button type="button" id="togglePw"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                <i class="bi bi-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" id="submitBtn"
                        class="w-full bg-primary-900 hover:bg-primary-800 text-white font-medium py-2.5 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 text-sm">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Masuk ke Sistem
                    </button>
                </div>
            </form>

            <!-- Demo accounts -->
            <div class="mt-6 pt-5 border-t border-slate-100">
                <p class="text-xs font-medium text-slate-500 mb-3">Akun Demo:</p>
                <div class="grid grid-cols-2 gap-2">
                    <?php
                    $demos = [
                        ['admin@plts.com',    'Admin',    'bg-red-50 text-red-700 border-red-200'],
                        ['manajer@plts.com',  'Manajer',  'bg-blue-50 text-blue-700 border-blue-200'],
                        ['teknisi@plts.com',  'Teknisi',  'bg-green-50 text-green-700 border-green-200'],
                        ['gudang@plts.com',   'Gudang',   'bg-purple-50 text-purple-700 border-purple-200'],
                    ];
                    foreach ($demos as [$email, $label, $cls]): ?>
                    <button type="button" onclick="fillDemo('<?= $email ?>')"
                        class="text-xs border rounded-lg px-3 py-2 text-left <?= $cls ?> hover:opacity-80 transition-opacity">
                        <div class="font-medium"><?= $label ?></div>
                        <div class="opacity-70 text-xs mt-0.5"><?= $email ?></div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <p class="text-center text-blue-200/60 text-xs mt-5">
        © <?= date('Y') ?> PT Energy Lima Pilar — Sistem Monitoring PLTS
    </p>
</div>

<script>
// Toggle password
document.getElementById('togglePw').addEventListener('click', function() {
    const input = document.getElementById('pwInput');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
});

// Isi demo account
function fillDemo(email) {
    document.querySelector('input[name="email"]').value    = email;
    document.querySelector('input[name="password"]').value = 'password';
}

// Loading state saat submit
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i> Memproses...';
    btn.disabled  = true;
});
</script>
</body>
</html>