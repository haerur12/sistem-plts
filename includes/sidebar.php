<?php
// includes/sidebar.php
$currentPage = $currentPage ?? '';
$roleColors = [
    'admin'            => 'bg-red-500',
    'manajer_proyek'   => 'bg-blue-500',
    'teknisi_lapangan' => 'bg-green-500',
    'gudang'           => 'bg-purple-500',
];
$roleLabels = [
    'admin'            => 'Admin',
    'manajer_proyek'   => 'Manajer Proyek',
    'teknisi_lapangan' => 'Teknisi Lapangan',
    'gudang'           => 'Gudang',
];
$roleColor = $roleColors[$user['role']] ?? 'bg-slate-500';
$roleLabel = $roleLabels[$user['role']] ?? $user['role'];
?>

<!-- Sidebar -->
<aside class="w-64 bg-primary-900 flex flex-col flex-shrink-0 overflow-y-auto">
    <!-- Brand -->
    <div class="px-5 py-5 border-b border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-yellow-400 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="bi bi-sun-fill text-primary-900 text-lg"></i>
            </div>
            <div>
                <div class="text-white font-semibold text-sm leading-tight">Sistem PLTS</div>
                <div class="text-slate-400 text-xs">PT Energy Lima Pilar</div>
            </div>
        </div>
    </div>

    <!-- User Info -->
    <div class="px-4 py-4 border-b border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full <?= $roleColor ?> flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
                <?= strtoupper(substr($user['nama'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="min-w-0">
                <div class="text-white text-sm font-medium truncate"><?= sanitize($user['nama'] ?? '') ?></div>
                <div class="text-slate-400 text-xs"><?= $roleLabel ?></div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1">
        <!-- Menu Utama -->
        <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Menu Utama</p>

        <a href="<?= BASE_URL ?>/pages/dashboard/index.php"
            class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2 text-lg"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?= BASE_URL ?>/pages/notifikasi/index.php"
            class="sidebar-link <?= $currentPage === 'notifikasi' ? 'active' : '' ?>">
            <i class="bi bi-bell text-lg"></i>
            <span>Notifikasi</span>
            <?php if ($notifCount > 0): ?>
            <span class="ml-auto bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                <?= $notifCount > 9 ? '9+' : $notifCount ?>
            </span>
            <?php endif; ?>
        </a>

        <a href="<?= BASE_URL ?>/pages/komponen/index.php?view=status"
            class="sidebar-link <?= $currentPage === 'status-material' ? 'active' : '' ?>">
            <i class="bi bi-boxes text-lg"></i>
            <span>Status Material</span>
        </a>

        <?php if (isAdminOrManajer()): ?>
        <!-- Manajemen Proyek -->
        <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4 mb-2">Manajemen Proyek</p>

        <a href="<?= BASE_URL ?>/pages/proyek/index.php"
            class="sidebar-link <?= $currentPage === 'proyek' ? 'active' : '' ?>">
            <i class="bi bi-diagram-3 text-lg"></i>
            <span>Proyek</span>
        </a>

        <a href="<?= BASE_URL ?>/pages/jadwal/index.php"
            class="sidebar-link <?= $currentPage === 'jadwal' ? 'active' : '' ?>">
            <i class="bi bi-calendar3 text-lg"></i>
            <span>Jadwal Instalasi</span>
        </a>
        <?php endif; ?>

        <?php if (!isGudang()): ?>
        <a href="<?= BASE_URL ?>/pages/laporan/index.php"
            class="sidebar-link <?= $currentPage === 'laporan' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text text-lg"></i>
            <span>Laporan Lapangan</span>
        </a>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
        <!-- Data Master -->
        <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4 mb-2">Data Master</p>

        <a href="<?= BASE_URL ?>/pages/pelanggan/index.php"
            class="sidebar-link <?= $currentPage === 'pelanggan' ? 'active' : '' ?>">
            <i class="bi bi-people text-lg"></i>
            <span>Pelanggan</span>
        </a>

        <a href="<?= BASE_URL ?>/pages/pengguna/index.php"
            class="sidebar-link <?= $currentPage === 'pengguna' ? 'active' : '' ?>">
            <i class="bi bi-person-gear text-lg"></i>
            <span>Pengguna</span>
        </a>
        <?php endif; ?>

        <?php if (isAdminOrGudang()): ?>
        <!-- Gudang -->
        <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4 mb-2">Gudang</p>

        <a href="<?= BASE_URL ?>/pages/komponen/index.php"
            class="sidebar-link <?= $currentPage === 'komponen' ? 'active' : '' ?>">
            <i class="bi bi-grid-3x3-gap text-lg"></i>
            <span>Komponen PLTS</span>
        </a>

        <a href="<?= BASE_URL ?>/pages/material/index.php"
            class="sidebar-link <?= $currentPage === 'material' ? 'active' : '' ?>">
            <i class="bi bi-arrow-left-right text-lg"></i>
            <span>Penggunaan Material</span>
        </a>
        <?php endif; ?>
    </nav>

    <!-- Logout -->
    <div class="px-3 py-4 border-t border-white/10">
        <form method="POST" action="<?= BASE_URL ?>/api/auth/logout.php">
            <button type="submit"
                class="sidebar-link w-full text-red-400 hover:bg-red-500/10 hover:text-red-300">
                <i class="bi bi-box-arrow-right text-lg"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

<!-- Main Content -->
<div class="flex-1 flex flex-col overflow-hidden">
    <!-- Topbar -->
    <header class="bg-white border-b border-slate-100 px-6 py-3.5 flex items-center justify-between flex-shrink-0">
        <h1 class="text-base font-semibold text-slate-800"><?= $pageTitle ?? 'Dashboard' ?></h1>
        <div class="flex items-center gap-3">
            <a href="<?= BASE_URL ?>/pages/notifikasi/index.php"
                class="relative p-2 rounded-lg hover:bg-slate-100 transition-colors">
                <i class="bi bi-bell text-slate-500 text-lg"></i>
                <?php if ($notifCount > 0): ?>
                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                <?php endif; ?>
            </a>
            <div class="w-px h-6 bg-slate-200"></div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full <?= $roleColor ?> flex items-center justify-center text-white font-semibold text-xs">
                    <?= strtoupper(substr($user['nama'] ?? 'U', 0, 1)) ?>
                </div>
                <span class="text-sm font-medium text-slate-700"><?= sanitize($user['nama'] ?? '') ?></span>
            </div>
        </div>
    </header>

    <!-- Flash Message -->
    <?php if ($flash): ?>
    <?php $flashColor = $flash['type'] === 'success'
        ? 'bg-green-50 border-green-200 text-green-800'
        : 'bg-red-50 border-red-200 text-red-800'; ?>
    <div class="mx-6 mt-4 px-4 py-3 rounded-lg border <?= $flashColor ?> flex items-center gap-2 text-sm" id="flashMsg">
        <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?>"></i>
        <?= sanitize($flash['message']) ?>
        <button onclick="document.getElementById('flashMsg').remove()" class="ml-auto text-current opacity-60 hover:opacity-100">
            <i class="bi bi-x"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- Page Content -->
    <main class="flex-1 overflow-y-auto p-6">