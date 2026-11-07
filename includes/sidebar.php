<?php
// includes/sidebar.php — COLLAPSIBLE MINI-RAIL DARK SIDEBAR (DENGAN FLOATING TOOLTIP)
$currentPage = $currentPage ?? '';

$roleColors = [
    'operator'         => 'from-amber-500 to-orange-600',
    'admin'            => 'from-rose-500 to-red-600',
    'manajer_proyek'   => 'from-sky-500 to-blue-600',
    'teknisi_lapangan' => 'from-emerald-500 to-green-600',
    'gudang'           => 'from-violet-500 to-purple-600',
];
$roleLabels = [
    'operator'         => 'Operator Sistem',
    'admin'            => 'Administrator',
    'manajer_proyek'   => 'Manajer Proyek',
    'teknisi_lapangan' => 'Teknisi Lapangan',
    'gudang'           => 'Staf Gudang',
];

$roleColor     = $roleColors[$user['role'] ?? ''] ?? 'from-indigo-500 to-blue-600';
$roleLabel     = $roleLabels[$user['role'] ?? ''] ?? ($user['role'] ?? 'User');
$inisial       = strtoupper(substr(trim($user['nama'] ?? 'U'), 0, 1));
$userAvatarUrl = function_exists('resolveAvatarUrl') ? resolveAvatarUrl($user['avatar'] ?? null) : null;
?>

<!-- ===== BACKDROP OVERLAY KHUSUS MOBILE (< 1024px) ===== -->
<div id="sidebarOverlay"
     onclick="collapseSidebar()"
     class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-[60] hidden opacity-0 transition-opacity duration-300 lg:hidden no-print"></div>

<!-- ===== FLOATING TOOLTIP (MUNCUL SAAT HOVER IKON DI MODE RAMPING) ===== -->
<div id="railTooltip" class="no-print" role="tooltip">
    <span id="railTooltipText">Menu</span>
</div>

<!-- ===== COLLAPSIBLE MINI-RAIL SIDEBAR ===== -->
<aside id="sidebar"
       aria-label="Navigasi Utama"
       class="rail-sidebar is-collapsed select-none no-print">

    <!-- Tombol Toggle Panah Ganda (« / ») di Tepi Kanan Atas (Persis Gambar Referensi) -->
    <button type="button"
            id="sidebarEdgeToggle"
            onclick="toggleSidebar(event)"
            class="rail-edge-btn"
            title="Lebarkan / Kecilkan Menu">
        <i class="bi bi-chevron-double-right" id="railToggleIcon"></i>
    </button>

    <!-- 1. HEADER BRAND & LOGO -->
    <div class="rail-header">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php" class="rail-brand-link" data-tooltip="Sistem PLTS — PT Energy Lima Pilar">
            <div class="rail-logo-box">
                <img src="<?= BASE_URL ?>/assets/img/Logo-elp.png" alt="ELP"
                     class="w-7 h-7 object-contain drop-shadow-[0_0_8px_rgba(56,189,248,0.5)]">
            </div>
            <div class="rail-hide-on-collapse min-w-0">
                <div class="text-white font-extrabold text-[15px] leading-tight tracking-tight truncate">Sistem PLTS</div>
                <div class="text-indigo-300/75 text-[11px] font-medium truncate">PT Energy Lima Pilar</div>
            </div>
        </a>
    </div>

    <!-- 2. SEARCH BAR (Berubah Jadi Tombol Ikon Bulat Saat Sidebar Ramping) -->
    <div class="rail-search-wrap">
        <button type="button"
                onclick="expandAndFocusSearch(event)"
                class="rail-search-icon-btn"
                data-tooltip="Cari menu navigasi...">
            <i class="bi bi-search"></i>
        </button>

        <div class="rail-search-input-box rail-hide-on-collapse">
            <i class="bi bi-search text-slate-400 text-xs"></i>
            <input type="text"
                   id="sidebarMenuFilter"
                   oninput="filterSidebarMenu(this.value)"
                   placeholder="Cari menu apa saja..."
                   class="w-full bg-transparent text-xs text-white placeholder-slate-400 focus:outline-none">
        </div>
    </div>

    <div class="mx-3 my-1 border-t border-white/[0.08]"></div>

    <!-- 3. DAFTAR MENU NAVIGASI -->
    <nav id="sidebarNavList" class="rail-nav">

        <?php if (function_exists('isOperator') && isOperator()): ?>
        <div class="rail-section" data-menu-group>
            <div class="rail-section-title rail-hide-on-collapse text-amber-400">Operator</div>
            <a href="<?= BASE_URL ?>/pages/operator/akses.php"
               class="rail-item <?= $currentPage === 'operator-akses' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Kontrol & Hak Akses">
                <span class="rail-icon"><i class="bi bi-shield-lock-fill text-amber-400"></i></span>
                <span class="rail-label rail-hide-on-collapse">Kontrol & Hak Akses</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- GRUP: MAIN / MENU UTAMA -->
        <div class="rail-section" data-menu-group>
            <div class="rail-section-title rail-hide-on-collapse">Menu Utama</div>

            <?php if (!function_exists('canAccess') || canAccess('dashboard')): ?>
            <a href="<?= BASE_URL ?>/pages/dashboard/index.php"
               class="rail-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Dashboard">
                <span class="rail-icon"><i class="bi bi-house-door"></i></span>
                <span class="rail-label rail-hide-on-collapse">Dashboard</span>
            </a>
            <?php endif; ?>

            <?php if (!function_exists('canAccess') || canAccess('activity-log')): ?>
            <a href="<?= BASE_URL ?>/pages/activity-log/index.php"
               class="rail-item <?= $currentPage === 'activity-log' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Activity Log">
                <span class="rail-icon"><i class="bi bi-activity"></i></span>
                <span class="rail-label rail-hide-on-collapse">Activity Log</span>
            </a>
            <?php endif; ?>

            <?php if (!function_exists('canAccess') || canAccess('analitik')): ?>
            <a href="<?= BASE_URL ?>/pages/analitik/index.php"
               class="rail-item <?= $currentPage === 'analitik' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Laporan & Analitik">
                <span class="rail-icon"><i class="bi bi-bar-chart-line"></i></span>
                <span class="rail-label rail-hide-on-collapse">Laporan & Analitik</span>
            </a>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>/pages/notifikasi/index.php"
               class="rail-item <?= $currentPage === 'notifikasi' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Notifikasi<?= ($notifCount ?? 0) > 0 ? ' (' . $notifCount . ' baru)' : '' ?>">
                <span class="rail-icon relative">
                    <i class="bi bi-bell"></i>
                    <?php if (($notifCount ?? 0) > 0): ?>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-[#070720]"></span>
                    <?php endif; ?>
                </span>
                <span class="rail-label rail-hide-on-collapse">Notifikasi</span>
                <?php if (($notifCount ?? 0) > 0): ?>
                <span class="rail-hide-on-collapse ml-auto bg-rose-500 text-white text-[10px] font-bold rounded-full min-w-[20px] h-5 px-1.5 flex items-center justify-center">
                    <?= $notifCount > 9 ? '9+' : $notifCount ?>
                </span>
                <?php endif; ?>
            </a>

            <?php if (!function_exists('canAccess') || canAccess('status-material')): ?>
            <a href="<?= BASE_URL ?>/pages/komponen/status.php"
               class="rail-item <?= $currentPage === 'status-material' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Status Material">
                <span class="rail-icon"><i class="bi bi-boxes"></i></span>
                <span class="rail-label rail-hide-on-collapse">Status Material</span>
            </a>
            <?php endif; ?>
        </div>

        <!-- GRUP: MANAJEMEN PROYEK -->
        <?php if (!function_exists('canAccess') || canAccess('proyek') || canAccess('jadwal') || canAccess('gantt') || canAccess('kanban') || canAccess('laporan')): ?>
        <div class="rail-section" data-menu-group>
            <div class="rail-section-title rail-hide-on-collapse">Manajemen Proyek</div>

            <?php if (!function_exists('canAccess') || canAccess('proyek')): ?>
            <a href="<?= BASE_URL ?>/pages/proyek/index.php"
               class="rail-item <?= $currentPage === 'proyek' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Proyek PLTS">
                <span class="rail-icon"><i class="bi bi-diagram-3"></i></span>
                <span class="rail-label rail-hide-on-collapse">Proyek PLTS</span>
            </a>
            <?php endif; ?>

            <?php if (!function_exists('canAccess') || canAccess('jadwal')): ?>
            <a href="<?= BASE_URL ?>/pages/jadwal/index.php"
               class="rail-item <?= $currentPage === 'jadwal' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Jadwal Instalasi">
                <span class="rail-icon"><i class="bi bi-calendar3"></i></span>
                <span class="rail-label rail-hide-on-collapse">Jadwal Instalasi</span>
            </a>
            <?php endif; ?>

            <?php if (!function_exists('canAccess') || canAccess('gantt')): ?>
            <a href="<?= BASE_URL ?>/pages/proyek/gantt.php"
               class="rail-item <?= $currentPage === 'gantt' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Gantt Chart">
                <span class="rail-icon"><i class="bi bi-calendar-range"></i></span>
                <span class="rail-label rail-hide-on-collapse">Gantt Chart</span>
            </a>
            <?php endif; ?>

            <?php if (!function_exists('canAccess') || canAccess('kanban')): ?>
            <a href="<?= BASE_URL ?>/pages/proyek/kanban.php"
               class="rail-item <?= $currentPage === 'kanban' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Kanban Board">
                <span class="rail-icon"><i class="bi bi-kanban"></i></span>
                <span class="rail-label rail-hide-on-collapse">Kanban Board</span>
            </a>
            <?php endif; ?>

            <?php if (!function_exists('canAccess') || canAccess('laporan')): ?>
            <a href="<?= BASE_URL ?>/pages/laporan/index.php"
               class="rail-item <?= $currentPage === 'laporan' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Laporan Lapangan">
                <span class="rail-icon"><i class="bi bi-file-earmark-text"></i></span>
                <span class="rail-label rail-hide-on-collapse">Laporan Lapangan</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- GRUP: GUDANG & LOGISTIK -->
        <?php if (!function_exists('canAccess') || canAccess('komponen') || canAccess('material') || canAccess('laporan-stok')): ?>
        <div class="rail-section" data-menu-group>
            <div class="rail-section-title rail-hide-on-collapse">Gudang</div>

            <?php if (!function_exists('canAccess') || canAccess('komponen')): ?>
            <a href="<?= BASE_URL ?>/pages/komponen/index.php"
               class="rail-item <?= $currentPage === 'komponen' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Komponen PLTS">
                <span class="rail-icon"><i class="bi bi-box-seam"></i></span>
                <span class="rail-label rail-hide-on-collapse">Komponen PLTS</span>
            </a>
            <?php endif; ?>

            <?php if (!function_exists('canAccess') || canAccess('material')): ?>
            <a href="<?= BASE_URL ?>/pages/material/index.php"
               class="rail-item <?= $currentPage === 'material' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Penggunaan Material">
                <span class="rail-icon"><i class="bi bi-arrow-left-right"></i></span>
                <span class="rail-label rail-hide-on-collapse">Penggunaan Material</span>
            </a>
            <?php endif; ?>

            <?php if (!function_exists('canAccess') || canAccess('laporan-stok')): ?>
            <a href="<?= BASE_URL ?>/pages/laporan/stok.php"
               class="rail-item <?= $currentPage === 'laporan-stok' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Laporan Stok">
                <span class="rail-icon"><i class="bi bi-file-bar-graph"></i></span>
                <span class="rail-label rail-hide-on-collapse">Laporan Stok</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- GRUP: DATA MASTER -->
        <?php if (!function_exists('canAccess') || canAccess('pelanggan') || canAccess('pengguna')): ?>
        <div class="rail-section" data-menu-group>
            <div class="rail-section-title rail-hide-on-collapse">Data Master</div>

            <?php if (!function_exists('canAccess') || canAccess('pelanggan')): ?>
            <a href="<?= BASE_URL ?>/pages/pelanggan/index.php"
               class="rail-item <?= $currentPage === 'pelanggan' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Pelanggan">
                <span class="rail-icon"><i class="bi bi-buildings"></i></span>
                <span class="rail-label rail-hide-on-collapse">Pelanggan</span>
            </a>
            <?php endif; ?>

            <?php if (!function_exists('canAccess') || canAccess('pengguna')): ?>
            <a href="<?= BASE_URL ?>/pages/pengguna/index.php"
               class="rail-item <?= $currentPage === 'pengguna' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Pengguna">
                <span class="rail-icon"><i class="bi bi-people"></i></span>
                <span class="rail-label rail-hide-on-collapse">Pengguna</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- GRUP: PENGATURAN & LOGOUT -->
        <div class="rail-section mt-auto pt-2" data-menu-group>
            <a href="<?= BASE_URL ?>/pages/profil/index.php"
               class="rail-item <?= $currentPage === 'profil' ? 'active' : '' ?>"
               data-menu-item data-tooltip="Pengaturan Profil">
                <span class="rail-icon"><i class="bi bi-gear"></i></span>
                <span class="rail-label rail-hide-on-collapse">Pengaturan Profil</span>
            </a>

            <a href="<?= BASE_URL ?>/api/auth/logout.php"
               class="rail-item text-rose-400 hover:!text-rose-300"
               data-menu-item data-tooltip="Keluar (Logout)">
                <span class="rail-icon"><i class="bi bi-box-arrow-left text-rose-400"></i></span>
                <span class="rail-label rail-hide-on-collapse text-rose-300">Keluar Sistem</span>
            </a>
        </div>
    </nav>

    <!-- 4. FOOTER PROFIL USER DI BAGIAN PALING BAWAH (Persis Gambar Referensi) -->
    <div class="rail-footer">
        <a href="<?= BASE_URL ?>/pages/profil/index.php"
           class="rail-profile-card"
           data-tooltip="<?= sanitize(($user['nama'] ?? 'User') . ' — ' . $roleLabel) ?>">
            <?php if (!empty($userAvatarUrl)): ?>
            <img src="<?= sanitize($userAvatarUrl) ?>" alt="<?= $inisial ?>"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                 class="w-9 h-9 rounded-full object-cover flex-shrink-0 ring-2 ring-indigo-500/40 bg-white">
            <div style="display:none" class="w-9 h-9 rounded-full bg-gradient-to-br <?= $roleColor ?> items-center justify-center text-white font-bold text-xs flex-shrink-0 ring-2 ring-white/20">
                <?= $inisial ?>
            </div>
            <?php else: ?>
            <div class="w-9 h-9 rounded-full bg-gradient-to-br <?= $roleColor ?> flex items-center justify-center text-white font-bold text-xs flex-shrink-0 ring-2 ring-white/20">
                <?= $inisial ?>
            </div>
            <?php endif; ?>

            <div class="rail-hide-on-collapse min-w-0 flex-1">
                <div class="text-white text-xs font-bold truncate leading-tight"><?= sanitize($user['nama'] ?? '') ?></div>
                <div class="text-slate-400 text-[10px] truncate mt-0.5"><?= sanitize($user['email'] ?? $roleLabel) ?></div>
            </div>
        </a>
    </div>
</aside>

<!-- ===== MAIN CONTENT WRAPPER ===== -->
<div id="mainContentArea" class="flex-1 flex flex-col overflow-hidden min-w-0">

    <!-- Topbar -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-4 sm:px-6 py-3.5 flex items-center justify-between flex-shrink-0 z-30 no-print">
        <div class="flex items-center gap-3">
            <!-- Tombol Buka Sidebar khusus layar HP (< 1024px) -->
            <button type="button" onclick="toggleSidebar(event)"
                class="lg:hidden w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 transition-colors">
                <i class="bi bi-list text-xl"></i>
            </button>
            <h1 class="text-base font-bold text-slate-800 tracking-tight truncate"><?= sanitize($pageTitle ?? 'Dashboard') ?></h1>
        </div>

        <div class="flex items-center gap-2">
            <!-- Global Search Button -->
            <button type="button" onclick="openGlobalSearch()"
                class="flex items-center gap-2.5 bg-slate-100 hover:bg-slate-200/70 text-slate-500 rounded-xl px-3.5 py-2 text-xs transition-colors">
                <i class="bi bi-search text-slate-400"></i>
                <span class="hidden sm:inline text-slate-400">Cari proyek, laporan, stok...</span>
                <kbd class="hidden sm:inline bg-white text-slate-400 text-[10px] px-1.5 py-0.5 rounded border border-slate-200 font-mono">Ctrl+K</kbd>
            </button>

            <!-- Dark Mode Toggle -->
            <button type="button" onclick="toggleDarkMode()"
                class="w-9 h-9 rounded-xl hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors"
                title="Toggle Dark Mode">
                <i class="bi bi-moon-stars text-lg" id="darkModeIcon"></i>
            </button>

            <!-- Notifikasi Bell -->
            <div class="relative" id="notifContainer">
                <button type="button" onclick="toggleNotifDropdown()"
                    class="relative w-9 h-9 rounded-xl hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors">
                    <i class="bi bi-bell text-lg"></i>
                    <span id="notifBadge"
                        class="<?= ($notifCount ?? 0) > 0 ? 'flex' : 'hidden' ?> absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] px-1 items-center justify-center ring-2 ring-white">
                        <?= ($notifCount ?? 0) > 9 ? '9+' : ($notifCount ?? 0) ?>
                    </span>
                </button>

                <div id="notifDropdown"
                    class="hidden absolute right-0 top-12 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-100 z-50 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-800">Notifikasi</span>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="bacaSemuaNotif()" class="text-xs text-primary-600 hover:underline font-medium">
                                Tandai semua dibaca
                            </button>
                            <span class="text-slate-300">·</span>
                            <a href="<?= BASE_URL ?>/pages/notifikasi/index.php" class="text-xs text-slate-500 hover:underline">
                                Lihat semua
                            </a>
                        </div>
                    </div>
                    <div id="notifList" class="divide-y divide-slate-50 max-h-80 overflow-y-auto">
                        <div class="px-4 py-8 text-center text-xs text-slate-400">Memuat...</div>
                    </div>
                </div>
            </div>

            <div class="h-6 w-px bg-slate-200 mx-1"></div>

            <!-- User Dropdown Menu -->
            <div class="relative" id="userMenuContainer">
                <button type="button" onclick="toggleUserMenu()"
                    class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-slate-100 transition-colors">
                    <?php if (!empty($userAvatarUrl)): ?>
                    <img src="<?= sanitize($userAvatarUrl) ?>" alt="<?= $inisial ?>"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                         class="w-8 h-8 rounded-lg object-cover flex-shrink-0 shadow-sm ring-1 ring-slate-200 bg-white">
                    <div style="display:none" class="w-8 h-8 rounded-lg bg-gradient-to-br <?= $roleColor ?> items-center justify-center text-white font-semibold text-xs flex-shrink-0 shadow-sm">
                        <?= $inisial ?>
                    </div>
                    <?php else: ?>
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br <?= $roleColor ?> flex items-center justify-center text-white font-semibold text-xs flex-shrink-0 shadow-sm">
                        <?= $inisial ?>
                    </div>
                    <?php endif; ?>
                    <div class="hidden sm:block text-left">
                        <div class="text-xs font-bold text-slate-700 leading-tight"><?= sanitize($user['nama'] ?? '') ?></div>
                        <div class="text-[10px] text-slate-400 leading-tight"><?= $roleLabel ?></div>
                    </div>
                    <i class="bi bi-chevron-down text-xs text-slate-400 hidden sm:block"></i>
                </button>

                <div id="userDropdown"
                    class="hidden absolute right-0 top-12 w-56 bg-white rounded-2xl shadow-2xl border border-slate-100 z-50 overflow-hidden py-1.5">
                    <div class="px-4 py-2.5 border-b border-slate-100 sm:hidden">
                        <div class="text-xs font-bold text-slate-800"><?= sanitize($user['nama'] ?? '') ?></div>
                        <div class="text-[11px] text-slate-400"><?= $roleLabel ?></div>
                    </div>
                    <a href="<?= BASE_URL ?>/pages/profil/index.php"
                        class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 transition-colors">
                        <i class="bi bi-person-circle text-slate-400 w-4"></i> Profil Saya
                    </a>
                    <a href="<?= BASE_URL ?>/pages/notifikasi/index.php"
                        class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 transition-colors">
                        <i class="bi bi-bell text-slate-400 w-4"></i> Notifikasi
                    </a>
                    <div class="border-t border-slate-100 my-1"></div>
                    <a href="<?= BASE_URL ?>/api/auth/logout.php"
                        class="flex items-center gap-2.5 px-4 py-2 text-xs text-red-600 hover:bg-red-50 transition-colors font-medium">
                        <i class="bi bi-box-arrow-left w-4"></i> Keluar
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Global Search Modal -->
    <div id="globalSearchModal"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-start justify-center pt-16 sm:pt-24 p-4"
        onclick="if(event.target===this)closeGlobalSearch()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden border border-slate-200/80">
            <div class="flex items-center gap-3 px-4 py-3.5 border-b border-slate-100">
                <i class="bi bi-search text-primary-600"></i>
                <input type="text" id="globalSearchInput"
                    placeholder="Ketik untuk mencari proyek, laporan, komponen, pelanggan..."
                    class="flex-1 text-sm text-slate-800 placeholder-slate-400 focus:outline-none bg-transparent"
                    oninput="handleGlobalSearch(this.value)">
                <kbd class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded font-mono">ESC</kbd>
            </div>
            <div id="globalSearchResults" class="max-h-96 overflow-y-auto divide-y divide-slate-50">
                <div class="px-4 py-10 text-center text-xs text-slate-400">
                    <i class="bi bi-command text-2xl text-slate-300 block mb-1.5"></i>
                    Ketik minimal 2 karakter untuk mulai mencari
                </div>
            </div>
        </div>
    </div>

    <!-- ===== STYLE & SCRIPT KHUSUS COLLAPSIBLE MINI-RAIL SIDEBAR ===== -->
    <style>
        /* Kontainer Utama Sidebar Gelap (#070720) */
        .rail-sidebar {
            position: relative;
            z-index: 65;
            background: linear-gradient(180deg, #06061b 0%, #0a0a28 55%, #06061b 100%);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            width: 264px;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            transition: width 0.26s cubic-bezier(0.4, 0, 0.2, 1), transform 0.26s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Mode Ramping / Collapsed di Layar Desktop (>= 1024px): Tidak hilang, lebar 74px */
        @media (min-width: 1024px) {
            .rail-sidebar {
                transform: none !important;
            }
            .rail-sidebar.is-collapsed {
                width: 74px;
            }
            .rail-sidebar.is-collapsed .rail-hide-on-collapse {
                display: none !important;
            }
        }

        /* Mode Layar HP/Tablet (< 1024px): Slide Drawer saat dibuka */
        @media (max-width: 1023px) {
            .rail-sidebar {
                position: fixed !important;
                top: 0;
                bottom: 0;
                left: 0;
                width: 270px !important;
                transform: translateX(-105%) !important;
            }
            .rail-sidebar:not(.is-collapsed) {
                transform: translateX(0) !important;
            }
            .rail-edge-btn {
                display: none !important;
            }
        }

        /* Tombol Panah Ganda (« / ») di Tepi Kanan Atas Sidebar */
        .rail-edge-btn {
            position: absolute;
            top: 22px;
            right: -13px;
            width: 26px;
            height: 26px;
            border-radius: 7px;
            background: linear-gradient(135deg, #4338ca, #3730a3);
            color: #ffffff;
            border: 2px solid #0a0a28;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(55, 48, 163, 0.5);
            transition: all 0.2s ease;
            z-index: 80;
        }
        .rail-edge-btn:hover {
            background: #4f46e5;
            transform: scale(1.08);
        }

        /* Header Brand */
        .rail-header {
            padding: 16px 14px 10px;
        }
        .rail-brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .rail-sidebar.is-collapsed .rail-brand-link {
            justify-content: center;
        }
        .rail-logo-box {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Search Box (Input Panjang saat Lebar, Tombol Bulat saat Ramping) */
        .rail-search-wrap {
            padding: 6px 14px 10px;
            display: flex;
            justify-content: center;
        }
        .rail-search-input-box {
            width: 100%;
            height: 36px;
            padding: 0 12px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .rail-search-icon-btn {
            display: none;
            width: 38px;
            height: 38px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        .rail-search-icon-btn:hover {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
        }
        @media (min-width: 1024px) {
            .rail-sidebar.is-collapsed .rail-search-icon-btn {
                display: flex;
            }
        }

        /* Area Scroll Menu */
        .rail-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 6px 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .rail-nav::-webkit-scrollbar {
            width: 3px;
        }
        .rail-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 9999px;
        }

        .rail-section-title {
            padding: 12px 20px 4px;
            font-size: 11px;
            font-weight: 600;
            color: rgba(148, 163, 184, 0.75);
            letter-spacing: 0.02em;
        }

        /* Item Menu */
        .rail-item {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            height: 42px;
            padding: 0 16px;
            color: rgba(226, 232, 240, 0.8);
            font-size: 13px;
            font-weight: 500;
            transition: all 0.18s ease;
            white-space: nowrap;
        }
        @media (min-width: 1024px) {
            .rail-sidebar.is-collapsed .rail-item {
                justify-content: center;
                padding: 0;
            }
        }
        .rail-item:hover {
            background: rgba(55, 48, 163, 0.28);
            color: #ffffff;
        }
        /* Highlight Baris Biru-Ungu Penuh Saat Aktif (Persis Gambar Referensi) */
        .rail-item.active {
            background: linear-gradient(90deg, rgba(49, 46, 129, 0.95) 0%, rgba(30, 27, 75, 0.85) 100%);
            color: #ffffff;
            font-weight: 700;
            box-shadow: inset 3px 0 0 #6366f1;
        }
        .rail-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        /* Footer Profil di Paling Bawah */
        .rail-footer {
            padding: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.03);
        }
        .rail-profile-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px;
            border-radius: 12px;
            transition: background 0.2s ease;
        }
        .rail-profile-card:hover {
            background: rgba(255, 255, 255, 0.08);
        }
        @media (min-width: 1024px) {
            .rail-sidebar.is-collapsed .rail-profile-card {
                justify-content: center;
                padding: 4px 0;
            }
        }

        /* Floating Tooltip Biru Gelap Berpanah Kiri (Persis Gambar Referensi) */
        #railTooltip {
            position: fixed;
            z-index: 9999;
            background: #2e2388;
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 13px;
            border-radius: 8px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.35);
            pointer-events: none;
            opacity: 0;
            transform: translateX(-6px);
            transition: opacity 0.15s ease, transform 0.15s ease;
            white-space: nowrap;
        }
        #railTooltip::before {
            content: '';
            position: absolute;
            left: -5px;
            top: 50%;
            transform: translateY(-50%);
            border-width: 5px 6px 5px 0;
            border-style: solid;
            border-color: transparent #2e2388 transparent transparent;
        }
        #railTooltip.show {
            opacity: 1;
            transform: translateX(0);
        }
    </style>

    <script>
    (function() {
        const sb   = document.getElementById('sidebar');
        const ov   = document.getElementById('sidebarOverlay');
        const icon = document.getElementById('railToggleIcon');
        const tip  = document.getElementById('railTooltip');
        const tipT = document.getElementById('railTooltipText');

        function syncSidebarIcon() {
            if (!sb || !icon) return;
            const collapsed = sb.classList.contains('is-collapsed');
            icon.className = collapsed ? 'bi bi-chevron-double-right' : 'bi bi-chevron-double-left';
        }

        // Lebarkan Sidebar (Membuka semua label menu)
        window.expandSidebar = function() {
            if (!sb) return;
            sb.classList.remove('is-collapsed');
            if (window.innerWidth < 1024 && ov) {
                ov.classList.remove('hidden', 'opacity-0');
                ov.classList.add('opacity-100');
            }
            if (tip) tip.classList.remove('show');
            syncSidebarIcon();
        };

        // Kecilkan Sidebar (Kembali ke Mode Icon-Rail di Desktop, atau tutup di HP)
        window.collapseSidebar = function() {
            if (!sb) return;
            sb.classList.add('is-collapsed');
            if (ov) {
                ov.classList.add('opacity-0');
                setTimeout(() => ov.classList.add('hidden'), 250);
            }
            syncSidebarIcon();
        };

        window.toggleSidebar = function(e) {
            if (e) e.stopPropagation();
            if (!sb) return;
            if (sb.classList.contains('is-collapsed')) {
                window.expandSidebar();
            } else {
                window.collapseSidebar();
            }
        };

        window.expandAndFocusSearch = function(e) {
            if (e) e.stopPropagation();
            window.expandSidebar();
            setTimeout(() => {
                document.getElementById('sidebarMenuFilter')?.focus();
            }, 180);
        };

        // Kompatibilitas dengan app.js & tombol mobile bottom nav
        window.openSidebar  = window.expandSidebar;
        window.closeSidebar = window.collapseSidebar;
        window.addEventListener('load', function() {
            window.openSidebar  = window.expandSidebar;
            window.closeSidebar = window.collapseSidebar;
        });

        // KLIK AREA DI LUAR SIDEBAR -> OTOMATIS KEMBALI RAMPING (COLLAPSE)
        document.addEventListener('click', function(e) {
            if (!sb || sb.classList.contains('is-collapsed')) return;
            const clickedInsideSidebar = sb.contains(e.target);
            if (!clickedInsideSidebar) {
                window.collapseSidebar();
            }
        });

        // Tekan tombol Esc -> Otomatis kembali ramping
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sb && !sb.classList.contains('is-collapsed')) {
                window.collapseSidebar();
            }
        });

        // Floating Tooltip saat kursor diarahkan ke ikon dalam mode ramping
        document.querySelectorAll('#sidebar [data-tooltip]').forEach(el => {
            el.addEventListener('mouseenter', function() {
                if (window.innerWidth < 1024 || !sb.classList.contains('is-collapsed')) return;
                const text = this.getAttribute('data-tooltip');
                if (!text || !tip || !tipT) return;
                const rect = this.getBoundingClientRect();
                tipT.textContent = text;
                tip.style.top  = (rect.top + (rect.height / 2) - 15) + 'px';
                tip.style.left = (rect.right + 12) + 'px';
                tip.classList.add('show');
            });
            el.addEventListener('mouseleave', function() {
                tip?.classList.remove('show');
            });
        });

        // Filter pencarian menu di dalam sidebar
        window.filterSidebarMenu = function(query) {
            const q = (query || '').trim().toLowerCase();
            document.querySelectorAll('#sidebarNavList [data-menu-group]').forEach(group => {
                let count = 0;
                group.querySelectorAll('[data-menu-item]').forEach(item => {
                    const match = q === '' || item.textContent.toLowerCase().includes(q) || (item.dataset.tooltip || '').toLowerCase().includes(q);
                    item.style.display = match ? '' : 'none';
                    if (match) count++;
                });
                group.style.display = count > 0 ? '' : 'none';
            });
        };

        syncSidebarIcon();
    })();
    </script>

    <!-- Page Content -->
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 pb-24 lg:pb-6">
        <?php $flash = getFlash(); if ($flash): ?>
        <?php
            $styles = [
                'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
                'error'   => 'bg-red-50 border-red-200 text-red-800',
                'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
                'info'    => 'bg-blue-50 border-blue-200 text-blue-800',
            ];
            $icons = [
                'success' => 'bi-check-circle-fill text-emerald-500',
                'error'   => 'bi-x-circle-fill text-red-500',
                'warning' => 'bi-exclamation-triangle-fill text-amber-500',
                'info'    => 'bi-info-circle-fill text-blue-500',
            ];
            $cls  = $styles[$flash['type']] ?? $styles['info'];
            $icon = $icons[$flash['type']]  ?? $icons['info'];
        ?>
        <div id="flashMsg"
            class="flex items-center justify-between gap-3 px-4 py-3 mb-5 rounded-xl border text-sm font-medium shadow-xs <?= $cls ?> no-print">
            <div class="flex items-center gap-2.5">
                <i class="bi <?= $icon ?> text-base"></i>
                <span><?= sanitize($flash['message']) ?></span>
            </div>
            <button onclick="document.getElementById('flashMsg').remove()"
                class="opacity-60 hover:opacity-100 transition-opacity">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>
        <?php endif; ?>