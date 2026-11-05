<?php
// pages/errors/403.php — HALAMAN AKSES DITOLAK (403 FORBIDDEN)
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$user = currentUser();
$roleLabel = !empty($user['role']) ? strtoupper(str_replace('_', ' ', $user['role'])) : 'GUEST';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Akses Dibatasi — PT Energy Lima Pilar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-200/80 p-8 text-center">
        <div class="w-20 h-20 rounded-3xl bg-red-50 text-red-500 border border-red-100 flex items-center justify-center mx-auto mb-5 shadow-xs">
            <i class="bi bi-shield-lock-fill text-4xl"></i>
        </div>

        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 text-red-700 text-xs font-extrabold uppercase tracking-wider mb-3">
            HTTP Error 403 — Akses Dibatasi
        </span>

        <h1 class="text-xl font-extrabold text-slate-900">Anda Tidak Memiliki Izin Akses</h1>
        <p class="text-xs text-slate-500 mt-2 leading-relaxed">
            Maaf, akun Anda saat ini terdaftar sebagai <strong class="text-slate-700"><?= sanitize($roleLabel) ?></strong> dan tidak memiliki izin untuk membuka modul atau melakukan tindakan ini.
        </p>

        <div class="mt-5 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 text-[11px] text-slate-500 text-left flex items-start gap-2.5">
            <i class="bi bi-info-circle-fill text-indigo-600 text-sm mt-0.5"></i>
            <span>Jika Anda memerlukan akses ke fitur ini untuk pekerjaan proyek, silakan hubungi <strong>Operator Sistem</strong> atau <strong>Administrator PT Energy Lima Pilar</strong>.</span>
        </div>

        <div class="flex items-center gap-3 mt-6">
            <button type="button" onclick="history.back()"
                    class="flex-1 py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                <i class="bi bi-arrow-left mr-1"></i> Kembali
            </button>
            <a href="<?= BASE_URL ?>/pages/dashboard/index.php"
               class="flex-1 py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-colors shadow-lg shadow-indigo-600/25">
                <i class="bi bi-speedometer2 mr-1"></i> Ke Dashboard
            </a>
        </div>
    </div>
</body>
</html>