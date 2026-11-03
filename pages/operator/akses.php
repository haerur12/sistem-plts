<?php
// pages/operator/akses.php — PUSAT KONTROL AKSES & MATRIKS PERMISSION OPERATOR
$pageTitle   = 'Pusat Kontrol & Hak Akses';
$currentPage = 'operator-akses';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';

// Hanya role 'operator' (atau admin jika belum ada akun operator) yang boleh membuka halaman ini
requireLogin();
if (!isOperator() && !isRole('admin')) {
    http_response_code(403);
    include __DIR__ . '/../errors/403.php';
    exit;
}

$db = db();

// Daftar modul yang dapat diatur hak aksesnya oleh Operator
$daftarModul = [
    'dashboard'       => ['nama' => 'Dashboard Utama',         'grup' => 'Menu Utama',       'icon' => 'bi-speedometer2'],
    'activity-log'    => ['nama' => 'Audit & Activity Log',    'grup' => 'Menu Utama',       'icon' => 'bi-activity'],
    'analitik'        => ['nama' => 'Laporan & Analitik',      'grup' => 'Menu Utama',       'icon' => 'bi-graph-up-arrow'],
    'status-material' => ['nama' => 'Status Material (View)',  'grup' => 'Menu Utama',       'icon' => 'bi-boxes'],
    'proyek'          => ['nama' => 'Manajemen Proyek',        'grup' => 'Manajemen Proyek', 'icon' => 'bi-diagram-3'],
    'jadwal'          => ['nama' => 'Jadwal Instalasi',        'grup' => 'Manajemen Proyek', 'icon' => 'bi-calendar3'],
    'gantt'           => ['nama' => 'Gantt Chart Proyek',      'grup' => 'Manajemen Proyek', 'icon' => 'bi-calendar-range'],
    'kanban'          => ['nama' => 'Kanban Board Proyek',     'grup' => 'Manajemen Proyek', 'icon' => 'bi-kanban'],
    'laporan'         => ['nama' => 'Laporan Harian Lapangan', 'grup' => 'Manajemen Proyek', 'icon' => 'bi-file-earmark-text'],
    'pelanggan'       => ['nama' => 'Data Master Pelanggan',   'grup' => 'Data Master',      'icon' => 'bi-people'],
    'pengguna'        => ['nama' => 'Manajemen Pengguna',      'grup' => 'Data Master',      'icon' => 'bi-person-gear'],
    'komponen'        => ['nama' => 'Inventaris Komponen PLTS','grup' => 'Gudang & Logistik','icon' => 'bi-grid-3x3-gap'],
    'material'        => ['nama' => 'Penggunaan Material',     'grup' => 'Gudang & Logistik','icon' => 'bi-arrow-left-right'],
    'laporan-stok'    => ['nama' => 'Laporan Stok Gudang',     'grup' => 'Gudang & Logistik','icon' => 'bi-file-bar-graph'],
];

$daftarRole = [
    'admin'            => ['label' => 'Administrator',    'badge' => 'bg-rose-50 text-rose-700 ring-rose-200',       'icon' => 'bi-shield-lock-fill'],
    'manajer_proyek'   => ['label' => 'Manajer Proyek',   'badge' => 'bg-sky-50 text-sky-700 ring-sky-200',         'icon' => 'bi-briefcase-fill'],
    'teknisi_lapangan' => ['label' => 'Teknisi Lapangan', 'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-200','icon' => 'bi-tools'],
    'gudang'           => ['label' => 'Staf Gudang',      'badge' => 'bg-violet-50 text-violet-700 ring-violet-200', 'icon' => 'bi-box-seam-fill'],
];

$activeRoleTab = $_GET['role_tab'] ?? 'manajer_proyek';
if (!isset($daftarRole[$activeRoleTab])) {
    $activeRoleTab = 'manajer_proyek';
}

// Ambil matriks permission dari database
$matrix = [];
try {
    $stmtPerm = $db->query("SELECT * FROM role_permissions");
    foreach ($stmtPerm->fetchAll() as $r) {
        $matrix[$r['role']][$r['modul']] = $r;
    }
} catch (Exception $e) {
    $matrix = [];
}

// Pantauan seluruh sesi & akses pengguna
$usersMonitoring = $db->query("
    SELECT u.*,
           (SELECT a.deskripsi FROM activity_log a WHERE a.user_id = u.id ORDER BY a.created_at DESC LIMIT 1) AS aksi_terakhir,
           (SELECT a.modul FROM activity_log a WHERE a.user_id = u.id ORDER BY a.created_at DESC LIMIT 1) AS modul_terakhir,
           (SELECT a.ip_address FROM activity_log a WHERE a.user_id = u.id ORDER BY a.created_at DESC LIMIT 1) AS ip_terakhir
    FROM users u
    ORDER BY u.is_online DESC, u.last_seen DESC
")->fetchAll();

// Statistik Keamanan & Akses Hari Ini
$loginHariIni = (int)$db->query("SELECT COUNT(*) FROM activity_log WHERE tipe = 'login' AND DATE(created_at) = CURDATE()")->fetchColumn();
$aksiHariIni  = (int)$db->query("SELECT COUNT(*) FROM activity_log WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$userOnline   = count(array_filter($usersMonitoring, fn($u) => $u['is_online'] && $u['last_seen'] && (time() - strtotime($u['last_seen'])) < 300));

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Panel Operator — Kontrol & Hak Akses</span>
    </nav>

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3.5">
            <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white flex items-center justify-center shadow-lg shadow-amber-500/25">
                <i class="bi bi-shield-lock-fill text-xl"></i>
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Pusat Kontrol Akses & Matriks Role (Operator)</h2>
                    <span class="badge-modern bg-amber-50 text-amber-700 ring-1 ring-amber-200">Super Access</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Pantau seluruh aktivitas akses pengguna secara *real-time* dan atur fitur apa saja yang dapat diakses oleh setiap *role*</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?= BASE_URL ?>/pages/activity-log/index.php" class="btn-secondary btn-sm">
                <i class="bi bi-activity text-primary-600"></i> Audit Trail Lengkap
            </a>
            <a href="<?= BASE_URL ?>/pages/pengguna/index.php" class="btn-primary btn-sm">
                <i class="bi bi-person-gear"></i> Kelola Akun Pengguna
            </a>
        </div>
    </div>

    <!-- KPI Pemantauan Akses -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card p-4 stat-card" style="--stat-accent: #10b981">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pengguna Online</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= $userOnline ?> <span class="text-xs font-medium text-slate-400">Aktif</span></p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="bi bi-broadcast text-lg"></i>
                </span>
            </div>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: #4f46e5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Sesi Login Hari Ini</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= number_format($loginHariIni) ?></p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="bi bi-box-arrow-in-right text-lg"></i>
                </span>
            </div>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: #0ea5e9">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Aktivitas Hari Ini</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= number_format($aksiHariIni) ?></p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i class="bi bi-lightning-charge-fill text-lg"></i>
                </span>
            </div>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: #f59e0b">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Modul Terproteksi</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= count($daftarModul) ?> <span class="text-xs font-medium text-slate-400">Fitur</span></p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="bi bi-sliders2-vertical text-lg"></i>
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <!-- ==========================================================
             KOLOM KIRI (7 KOLOM): PENGATURAN MATRIKS HAK AKSES PER ROLE
        =========================================================== -->
        <div class="xl:col-span-7">
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i class="bi bi-Sliders text-amber-500"></i> Pengaturan Hak Akses Fitur (Role Matrix)
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Pilih *role* di bawah ini lalu centang fitur apa saja yang boleh diakses oleh *role* tersebut</p>
                    </div>
                </div>

                <!-- Tab Pilihan Role yang Akan Diatur -->
                <div class="px-6 pt-4 pb-3 bg-slate-50/70 border-b border-slate-100 flex items-center gap-2 flex-wrap">
                    <?php foreach ($daftarRole as $rKey => $rInfo):
                        $isSel = ($activeRoleTab === $rKey);
                    ?>
                    <a href="?role_tab=<?= urlencode($rKey) ?>"
                       class="px-3.5 py-2 rounded-xl text-xs font-bold border transition-all flex items-center gap-2
                       <?= $isSel ? 'bg-slate-900 text-white border-slate-900 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100' ?>">
                        <i class="bi <?= $rInfo['icon'] ?>"></i>
                        <?= $rInfo['label'] ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Form Matriks Permission -->
                <form method="POST" action="<?= BASE_URL ?>/api/operator/save-permissions.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="target_role" value="<?= sanitize($activeRoleTab) ?>">

                    <div class="p-4 bg-indigo-50/40 border-b border-indigo-100 flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold text-slate-700">
                            Mengatur izin akses untuk aktor: <strong class="text-indigo-700 uppercase"><?= $daftarRole[$activeRoleTab]['label'] ?></strong>
                        </span>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="toggleAllPermissions(true)" class="text-[11px] font-bold text-primary-600 hover:underline">
                                Centang Semua
                            </button>
                            <span class="text-slate-300">·</span>
                            <button type="button" onclick="toggleAllPermissions(false)" class="text-[11px] font-bold text-red-600 hover:underline">
                                Cabut Semua
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="table-header">Nama Modul / Fitur</th>
                                    <th class="table-header text-center">Buka & Lihat<br><span class="text-[9px] font-normal text-slate-400">(Menu Sidebar)</span></th>
                                    <th class="table-header text-center">Tambah<br><span class="text-[9px] font-normal text-slate-400">(Create)</span></th>
                                    <th class="table-header text-center">Ubah<br><span class="text-[9px] font-normal text-slate-400">(Edit)</span></th>
                                    <th class="table-header text-center">Hapus<br><span class="text-[9px] font-normal text-slate-400">(Delete)</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($daftarModul as $modKey => $modInfo):
                                    $perm = $matrix[$activeRoleTab][$modKey] ?? [
                                        'can_view'   => 0,
                                        'can_create' => 0,
                                        'can_edit'   => 0,
                                        'can_delete' => 0,
                                    ];
                                ?>
                                <tr class="table-row">
                                    <td class="table-cell">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center flex-shrink-0">
                                                <i class="bi <?= $modInfo['icon'] ?> text-sm"></i>
                                            </span>
                                            <div>
                                                <div class="text-xs font-bold text-slate-800"><?= $modInfo['nama'] ?></div>
                                                <div class="text-[10px] text-slate-400"><?= $modInfo['grup'] ?> · <code><?= $modKey ?></code></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="table-cell text-center">
                                        <input type="checkbox" name="perm[<?= $modKey ?>][view]" value="1"
                                               class="perm-checkbox w-4 h-4 rounded text-primary-600 focus:ring-primary-500 cursor-pointer"
                                               <?= !empty($perm['can_view']) ? 'checked' : '' ?>>
                                    </td>
                                    <td class="table-cell text-center">
                                        <input type="checkbox" name="perm[<?= $modKey ?>][create]" value="1"
                                               class="perm-checkbox w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                               <?= !empty($perm['can_create']) ? 'checked' : '' ?>>
                                    </td>
                                    <td class="table-cell text-center">
                                        <input type="checkbox" name="perm[<?= $modKey ?>][edit]" value="1"
                                               class="perm-checkbox w-4 h-4 rounded text-amber-500 focus:ring-amber-500 cursor-pointer"
                                               <?= !empty($perm['can_edit']) ? 'checked' : '' ?>>
                                    </td>
                                    <td class="table-cell text-center">
                                        <input type="checkbox" name="perm[<?= $modKey ?>][delete]" value="1"
                                               class="perm-checkbox w-4 h-4 rounded text-red-600 focus:ring-red-500 cursor-pointer"
                                               <?= !empty($perm['can_delete']) ? 'checked' : '' ?>>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-400">
                            <i class="bi bi-info-circle"></i> Perubahan langsung berdampak pada menu *sidebar* dan izin halaman aktor terkait.
                        </span>
                        <button type="submit" class="btn-primary">
                            <i class="bi bi-check2-circle"></i> Simpan Hak Akses <?= $daftarRole[$activeRoleTab]['label'] ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==========================================================
             KOLOM KANAN (5 KOLOM): PEMANTAUAN AKSES PENGGUNA REAL-TIME
        =========================================================== -->
        <div class="xl:col-span-5 space-y-5">
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i class="bi bi-radar text-emerald-600"></i> Pemantauan Akses Pengguna
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Status sesi, IP, dan aktivitas terakhir seluruh aktor</p>
                    </div>
                </div>

                <div class="divide-y divide-slate-100 max-h-[680px] overflow-y-auto">
                    <?php foreach ($usersMonitoring as $um):
                        $onlineInfo = isUserOnline((int)$um['id']);
                        $isMe = ((int)$um['id'] === (int)currentUser()['id']);
                    ?>
                    <div class="p-4 hover:bg-slate-50/80 transition-colors">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="relative flex-shrink-0">
                                    <div class="w-10 h-10 rounded-xl bg-slate-800 text-white font-bold text-xs flex items-center justify-center">
                                        <?= strtoupper(substr($um['nama'], 0, 1)) ?>
                                    </div>
                                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white <?= $onlineInfo['online'] ? 'bg-emerald-500' : 'bg-slate-300' ?>"></span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs font-bold text-slate-900 truncate"><?= sanitize($um['nama']) ?></span>
                                        <span class="text-[10px] px-2 py-0.2 rounded-full bg-slate-100 text-slate-600 font-bold uppercase">
                                            <?= str_replace('_', ' ', $um['role']) ?>
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 truncate"><?= sanitize($um['email']) ?></p>

                                    <?php if (!empty($um['aksi_terakhir'])): ?>
                                    <div class="mt-2 p-2 rounded-lg bg-slate-50 border border-slate-100 text-[11px] text-slate-600">
                                        <span class="font-semibold text-primary-700">[<?= strtoupper(sanitize($um['modul_terakhir'] ?: 'SISTEM')) ?>]</span>
                                        <?= sanitize($um['aksi_terakhir']) ?>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            IP: <code><?= sanitize($um['ip_terakhir'] ?? '-') ?></code> · <?= formatLastSeen($onlineInfo['last_seen'], $onlineInfo['online']) ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Tombol Cepat Blokir / Aktifkan Akses oleh Operator -->
                            <?php if (!$isMe): ?>
                            <form method="POST" action="<?= BASE_URL ?>/api/operator/toggle-user.php" class="flex-shrink-0">
                                <?= csrfField() ?>
                                <input type="hidden" name="user_id" value="<?= $um['id'] ?>">
                                <?php if ($um['status'] === 'aktif'): ?>
                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 text-[11px] font-bold transition-colors"
                                        title="Bekukan / Blokir Akses Akun Ini">
                                    <i class="bi bi-slash-circle"></i> Bekukan
                                </button>
                                <?php else: ?>
                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[11px] font-bold transition-colors"
                                        title="Aktifkan Kembali Akses Akun Ini">
                                    <i class="bi bi-check-circle"></i> Aktifkan
                                </button>
                                <?php endif; ?>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAllPermissions(state) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = state);
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>