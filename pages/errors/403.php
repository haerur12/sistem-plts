<?php
// pages/errors/403.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Akses Ditolak</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                colors: { primary: { 600:'#4f46e5', 700:'#4338ca', 900:'#1e1b4b' } }
            } }
        }
    </script>
</head>
<body class="font-sans antialiased bg-slate-100/70 min-h-screen flex items-center justify-center p-6">
    <div class="text-center max-w-md w-full bg-white rounded-3xl shadow-card border border-slate-200/70 px-10 py-14">
        <div class="w-20 h-20 bg-red-50 text-red-500 rounded-3xl flex items-center justify-center mx-auto mb-6 ring-1 ring-red-100">
            <i class="bi bi-shield-lock text-4xl"></i>
        </div>
        <p class="text-5xl font-extrabold text-slate-200 tracking-tight mb-1">403</p>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight mb-2">Akses Ditolak</h2>
        <p class="text-slate-500 text-sm leading-relaxed mb-8">Anda tidak memiliki izin untuk mengakses halaman ini. Hubungi administrator jika Anda yakin seharusnya punya akses.</p>
        <div class="flex gap-3 justify-center">
            <a href="javascript:history.back()"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-white hover:bg-slate-50 text-slate-600 border border-slate-200 shadow-sm rounded-xl text-sm font-semibold transition-colors">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/pages/dashboard/index.php"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-semibold shadow-lg shadow-primary-600/25 transition-all hover:-translate-y-0.5">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </div>
    </div>
</body>
</html>
