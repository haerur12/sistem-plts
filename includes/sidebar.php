<?php
// includes/sidebar.php
$currentPage = $currentPage ?? '';
$roleColors = [
    'admin'            => 'from-rose-500 to-red-600',
    'manajer_proyek'   => 'from-sky-500 to-blue-600',
    'teknisi_lapangan' => 'from-emerald-500 to-green-600',
    'gudang'           => 'from-violet-500 to-purple-600',
];
$roleLabels = [
    'admin'            => 'Admin',
    'manajer_proyek'   => 'Manajer Proyek',
    'teknisi_lapangan' => 'Teknisi Lapangan',
    'gudang'           => 'Gudang',
];
$roleColor = $roleColors[$user['role']] ?? 'from-slate-500 to-slate-600';
$roleLabel = $roleLabels[$user['role']] ?? $user['role'];
$inisial   = strtoupper(substr($user['nama'] ?? 'U', 0, 1));
?>

<!-- Sidebar -->
<aside class="w-[270px] bg-gradient-to-b from-primary-900 via-[#232258] to-primary-900 flex flex-col flex-shrink-0 overflow-y-auto">
    <!-- Brand -->
    <div class="px-5 py-6 border-b border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-amber-300 to-yellow-500 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-amber-500/20">
                <i class="bi bi-sun-fill text-primary-900 text-xl"></i>
            </div>
            <div>
                <div class="text-white font-bold text-sm leading-tight tracking-tight">Sistem PLTS</div>
                <div class="text-indigo-300/70 text-[11px] mt-0.5">PT Energy Lima Pilar</div>
            </div>
        </div>
    </div>

    <!-- User Info -->
    <div class="mx-4 mt-4 mb-2 px-4 py-3.5 rounded-2xl bg-white/5 border border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br <?= $roleColor ?> flex items-center justify-center text-white font-bold text-sm flex-shrink-0 ring-2 ring-white/20 shadow-md">
                <?= $inisial ?>
            </div>
            <div class="min-w-0">
                <div class="text-white text-sm font-semibold truncate"><?= sanitize($user['nama'] ?? '') ?></div>
                <div class="text-indigo-300/80 text-xs mt-0.5"><?= $roleLabel ?></div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1">
        <!-- Menu Utama -->
        <p class="px-3 pt-2 pb-2 text-[10px] font-bold uppercase tracking-widest sidebar-section">Menu Utama</p>

        <a href="<?= BASE_URL ?>/pages/dashboard/index.php"
            class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2 w-5 text-center text-base"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?= BASE_URL ?>/pages/notifikasi/index.php"
            class="sidebar-link <?= $currentPage === 'notifikasi' ? 'active' : '' ?>">
            <i class="bi bi-bell w-5 text-center text-base"></i>
            <span>Notifikasi</span>
            <?php if ($notifCount > 0): ?>
            <span class="ml-auto bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[20px] h-5 px-1.5 flex items-center justify-center shadow-sm shadow-red-500/40">
                <?= $notifCount > 9 ? '9+' : $notifCount ?>
            </span>
            <?php endif; ?>
        </a>

        <a href="<?= BASE_URL ?>/pages/komponen/index.php?view=status"
            class="sidebar-link <?= $currentPage === 'status-material' ? 'active' : '' ?>">
            <i class="bi bi-boxes w-5 text-center text-base"></i>
            <span>Status Material</span>
        </a>

        <?php if (isAdminOrManajer()): ?>
        <!-- Manajemen Proyek -->
        <p class="px-3 pt-5 pb-2 text-[10px] font-bold uppercase tracking-widest sidebar-section">Manajemen Proyek</p>

        <a href="<?= BASE_URL ?>/pages/proyek/index.php"
            class="sidebar-link <?= $currentPage === 'proyek' ? 'active' : '' ?>">
            <i class="bi bi-diagram-3 w-5 text-center text-base"></i>
            <span>Proyek</span>
        </a>

        <a href="<?= BASE_URL ?>/pages/jadwal/index.php"
            class="sidebar-link <?= $currentPage === 'jadwal' ? 'active' : '' ?>">
            <i class="bi bi-calendar3 w-5 text-center text-base"></i>
            <span>Jadwal Instalasi</span>
        </a>
        <?php endif; ?>

        <?php if (!isGudang()): ?>
        <a href="<?= BASE_URL ?>/pages/laporan/index.php"
            class="sidebar-link <?= $currentPage === 'laporan' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text w-5 text-center text-base"></i>
            <span>Laporan Lapangan</span>
        </a>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
        <!-- Data Master -->
        <p class="px-3 pt-5 pb-2 text-[10px] font-bold uppercase tracking-widest sidebar-section">Data Master</p>

        <a href="<?= BASE_URL ?>/pages/pelanggan/index.php"
            class="sidebar-link <?= $currentPage === 'pelanggan' ? 'active' : '' ?>">
            <i class="bi bi-people w-5 text-center text-base"></i>
            <span>Pelanggan</span>
        </a>

        <a href="<?= BASE_URL ?>/pages/pengguna/index.php"
            class="sidebar-link <?= $currentPage === 'pengguna' ? 'active' : '' ?>">
            <i class="bi bi-person-gear w-5 text-center text-base"></i>
            <span>Pengguna</span>
        </a>
        <?php endif; ?>

        <?php if (isAdminOrGudang()): ?>
        <!-- Gudang -->
        <p class="px-3 pt-5 pb-2 text-[10px] font-bold uppercase tracking-widest sidebar-section">Gudang</p>

        <a href="<?= BASE_URL ?>/pages/komponen/index.php"
            class="sidebar-link <?= $currentPage === 'komponen' ? 'active' : '' ?>">
            <i class="bi bi-grid-3x3-gap w-5 text-center text-base"></i>
            <span>Komponen PLTS</span>
        </a>

        <a href="<?= BASE_URL ?>/pages/material/index.php"
            class="sidebar-link <?= $currentPage === 'material' ? 'active' : '' ?>">
            <i class="bi bi-arrow-left-right w-5 text-center text-base"></i>
            <span>Penggunaan Material</span>
        </a>
        <?php endif; ?>
    </nav>

    <!-- Logout -->
    <div class="px-3 pb-4">
        <form method="POST" action="<?= BASE_URL ?>/api/auth/logout.php">
            <button type="submit"
                class="sidebar-link w-full text-red-300/90 hover:bg-red-500/15 hover:text-red-200 border border-transparent hover:border-red-400/20">
                <i class="bi bi-box-arrow-right w-5 text-center text-base"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

<!-- Main Content -->
<div class="flex-1 flex flex-col overflow-hidden">
    <!-- Topbar -->
    <header class="bg-white/80 backdrop-blur-md border-b border-slate-200/80 px-6 py-3.5 flex items-center justify-between flex-shrink-0 sticky top-0 z-10">
        <div class="flex items-center gap-3">
            <h1 class="text-base font-bold text-slate-800 tracking-tight"><?= $pageTitle ?? 'Dashboard' ?></h1>
            <span class="hidden md:inline-flex items-center gap-1.5 text-[11px] font-medium text-slate-400 bg-slate-100 rounded-full px-2.5 py-1">
                <i class="bi bi-calendar3"></i> <?= date('d M Y') ?>
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= BASE_URL ?>/pages/notifikasi/index.php"
                class="relative p-2.5 rounded-xl hover:bg-slate-100 transition-colors group">
                <i class="bi bi-bell text-slate-500 text-lg group-hover:text-primary-600 transition-colors"></i>
                <?php if ($notifCount > 0): ?>
                <span class="absolute top-1.5 right-1.5 min-w-[16px] h-4 px-1 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center ring-2 ring-white">
                    <?= $notifCount > 9 ? '9+' : $notifCount ?>
                </span>
                <?php endif; ?>
            </a>
            <div class="w-px h-6 bg-slate-200 mx-1"></div>
            <div class="flex items-center gap-2.5 pl-1 pr-2 py-1 rounded-xl hover:bg-slate-50 transition-colors">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br <?= $roleColor ?> flex items-center justify-center text-white font-bold text-xs shadow-sm">
                    <?= $inisial ?>
                </div>
                <div class="hidden sm:block leading-tight">
                    <span class="text-sm font-semibold text-slate-700 block"><?= sanitize($user['nama'] ?? '') ?></span>
                    <span class="text-[11px] text-slate-400"><?= $roleLabel ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Message -->
    <?php if ($flash): ?>
    <?php $flashStyle = $flash['type'] === 'success'
        ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
        : 'bg-red-50 border-red-200 text-red-800'; ?>
    <div class="mx-6 mt-4 px-4 py-3.5 rounded-xl border <?= $flashStyle ?> flex items-center gap-3 text-sm font-medium shadow-sm" id="flashMsg">
        <span class="w-7 h-7 rounded-full <?= $flash['type'] === 'success' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' ?> flex items-center justify-center flex-shrink-0">
            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-lg' : 'bi-exclamation-triangle-fill' ?>"></i>
        </span>
        <?= sanitize($flash['message']) ?>
        <button onclick="document.getElementById('flashMsg').remove()" class="ml-auto text-current opacity-50 hover:opacity-100 transition-opacity">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- Page Content -->
    <main class="flex-1 overflow-y-auto p-6">
