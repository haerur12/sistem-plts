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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=2">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: { primary: {
                        50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',400:'#818cf8',
                        500:'#6366f1',600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#1e1b4b'
                    } }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        .login-input { @apply w-full pl-11 pr-4 py-3 border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary-500/15 focus:border-primary-500 transition-all; }
        .login-bg { @apply bg-primary-900; }
        .login-grid { background-image: radial-gradient(rgba(255,255,255,0.15) 1px, transparent 1px); background-size: 24px 24px; }
        .shadow-card { box-shadow: 0 10px 40px -10px rgba(0,0,0,0.08); }
        .glass { @apply bg-white/10 backdrop-blur-md border border-white/20; }
    </style>
</head>
<body class="font-sans antialiased">

<div class="min-h-screen flex">

    <!-- ===== Panel Kiri: Branding (desktop) ===== -->
    <div class="hidden lg:flex lg:w-[46%] login-bg relative overflow-hidden flex-col justify-between p-12 text-white">
        <div class="absolute inset-0 login-grid pointer-events-none"></div>

        <div class="relative flex items-center gap-3">
            <div class="w-11 h-11 bg-gradient-to-br from-amber-300 to-yellow-500 rounded-2xl flex items-center justify-center shadow-lg shadow-amber-500/25">
                <i class="bi bi-sun-fill text-primary-900 text-xl"></i>
            </div>
            <div>
                <div class="font-bold tracking-tight leading-tight">Sistem PLTS</div>
                <div class="text-indigo-300/70 text-xs">PT Energy Lima Pilar</div>
            </div>
        </div>

        <div class="relative max-w-md">
            <h2 class="text-3xl font-extrabold tracking-tight leading-snug mb-4">
                Pantau proyek surya Anda,<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-yellow-400">dari mana saja.</span>
            </h2>
            <p class="text-indigo-200/80 text-sm leading-relaxed mb-8">
                Kelola proyek instalasi, jadwal, material, dan laporan lapangan dalam satu platform yang terintegrasi.
            </p>
            <div class="space-y-4">
                <?php
                $features = [
                    ['bi-diagram-3',      'Manajemen proyek & progres real-time'],
                    ['bi-boxes',          'Monitoring stok komponen gudang'],
                    ['bi-file-earmark-text','Laporan lapangan digital terstruktur'],
                ];
                foreach ($features as [$icon, $text]): ?>
                <div class="flex items-center gap-3 glass rounded-xl px-4 py-3">
                    <span class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
                        <i class="bi <?= $icon ?> text-amber-300"></i>
                    </span>
                    <span class="text-sm text-indigo-100/90"><?= $text ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <p class="relative text-indigo-300/50 text-xs">© <?= date('Y') ?> PT Energy Lima Pilar — Sistem Monitoring PLTS</p>
    </div>

    <!-- ===== Panel Kanan: Form Login ===== -->
    <div class="flex-1 flex items-center justify-center p-6 sm:p-10 bg-slate-50">
        <div class="w-full max-w-md">
            <!-- Logo versi mobile -->
            <div class="lg:hidden text-center mb-8">
                <div class="w-14 h-14 bg-gradient-to-br from-amber-300 to-yellow-500 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg shadow-amber-500/20">
                    <i class="bi bi-sun-fill text-primary-900 text-2xl"></i>
                </div>
                <h1 class="text-primary-900 text-lg font-bold">Sistem Monitoring PLTS</h1>
                <p class="text-slate-500 text-sm mt-0.5">PT Energy Lima Pilar</p>
            </div>

            <div class="bg-white rounded-3xl shadow-card border border-slate-200/70 p-8 sm:p-10">
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Selamat datang kembali 👋</h2>
                <p class="text-sm text-slate-500 mt-1 mb-7">Masuk untuk melanjutkan ke dashboard Anda.</p>

                <?php if ($error && $error['type'] === 'error'): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-5 flex items-center gap-2.5" role="alert">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <?= sanitize($error['message']) ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>/api/auth/login.php" id="loginForm" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="bi bi-envelope text-slate-400"></i>
                            </div>
                            <input type="email" name="email" required autofocus
                                value="<?= sanitize($_POST['email'] ?? '') ?>"
                                placeholder="nama@perusahaan.com"
                                autocomplete="email" class="login-input">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="bi bi-lock text-slate-400"></i>
                            </div>
                            <input type="password" name="password" id="pwInput" required
                                placeholder="••••••••"
                                autocomplete="current-password" class="login-input pr-11">
                            <button type="button" id="togglePw"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-primary-600 transition-colors">
                                <i class="bi bi-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" id="submitBtn"
                        class="w-full bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white font-semibold py-3 px-4 rounded-xl transition-all shadow-lg shadow-primary-600/25 hover:shadow-primary-700/30 hover:-translate-y-0.5 flex items-center justify-center gap-2 text-sm mt-2">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Masuk ke Sistem
                    </button>
                </form>

                <!-- Demo accounts -->
                <div class="mt-7 pt-6 border-t border-slate-100">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3">Akun Demo — klik untuk mengisi</p>
                    <div class="grid grid-cols-2 gap-2.5">
                        <?php
                        $demos = [
                            ['admin@plts.com',   'Admin',   'bi-shield-lock',   'bg-rose-50 text-rose-700 ring-rose-200 hover:bg-rose-100'],
                            ['manajer@plts.com', 'Manajer', 'bi-briefcase',     'bg-sky-50 text-sky-700 ring-sky-200 hover:bg-sky-100'],
                            ['teknisi@plts.com', 'Teknisi', 'bi-tools',         'bg-emerald-50 text-emerald-700 ring-emerald-200 hover:bg-emerald-100'],
                            ['gudang@plts.com',  'Gudang',  'bi-warehouse',     'bg-violet-50 text-violet-700 ring-violet-200 hover:bg-violet-100'],
                        ];
                        foreach ($demos as [$email, $label, $icon, $cls]): ?>
                        <button type="button" onclick="fillDemo('<?= $email ?>')"
                            class="text-xs ring-1 rounded-xl px-3.5 py-2.5 text-left transition-colors <?= $cls ?>">
                            <div class="font-bold flex items-center gap-1.5"><i class="bi <?= $icon ?>"></i> <?= $label ?></div>
                            <div class="opacity-60 mt-0.5 truncate"><?= $email ?></div>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <p class="lg:hidden text-center text-slate-400 text-xs mt-6">
                © <?= date('Y') ?> PT Energy Lima Pilar — Sistem Monitoring PLTS
            </p>
        </div>
    </div>
</div>

<script>
// Toggle password
document.getElementById('togglePw').addEventListener('click', function () {
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
document.getElementById('loginForm').addEventListener('submit', function () {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i> Memproses...';
    btn.disabled  = true;
    btn.classList.add('opacity-70', 'cursor-not-allowed');
});
</script>
</body>
</html>
