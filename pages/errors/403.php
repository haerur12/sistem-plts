<?php
// pages/errors/403.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Akses Ditolak</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center">
    <div class="text-center px-6">
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
        </div>
        <h1 class="text-6xl font-bold text-slate-200 mb-2">403</h1>
        <h2 class="text-xl font-semibold text-slate-800 mb-2">Akses Ditolak</h2>
        <p class="text-slate-500 mb-8">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
        <div class="flex gap-3 justify-center">
            <a href="javascript:history.back()"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition-colors">
                ← Kembali
            </a>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/pages/dashboard/index.php"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-900 hover:bg-blue-800 text-white rounded-lg text-sm font-medium transition-colors">
                Dashboard
            </a>
        </div>
    </div>
</body>
</html>