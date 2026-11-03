<?php
// pages/pengguna/index.php — MANAJEMEN PENGGUNA & AKSES GOOGLE OAUTH
$pageTitle   = 'Manajemen Pengguna';
$currentPage = 'pengguna';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin']);

$db         = db();
$currentUser = currentUser();

$search     = trim($_GET['search'] ?? '');
$filterRole = trim($_GET['role'] ?? '');
$filterStat = trim($_GET['status'] ?? '');

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = '(u.nama LIKE ? OR u.email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filterRole !== '') {
    $where[]  = 'u.role = ?';
    $params[] = $filterRole;
}
if ($filterStat !== '') {
    $where[]  = 'u.status = ?';
    $params[] = $filterStat;
}

$whereStr = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT u.*,
           (SELECT COUNT(*) FROM laporan_lapangan l WHERE l.user_id = u.id) AS total_laporan
    FROM users u
    WHERE $whereStr
    ORDER BY FIELD(u.role, 'operator', 'admin', 'manajer_proyek', 'teknisi_lapangan', 'gudang'), u.nama ASC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Statistik Ringkasan Pengguna
$summary = $db->query("
    SELECT 
        COUNT(*) AS total,
        COALESCE(SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END), 0) AS aktif,
        COALESCE(SUM(CASE WHEN google_id IS NOT NULL AND google_id != '' THEN 1 ELSE 0 END), 0) AS google_linked,
        COALESCE(SUM(CASE WHEN is_online = 1 AND last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1 ELSE 0 END), 0) AS online_now
    FROM users
")->fetch();

$roleMeta = [
    'operator'         => ['label' => 'Operator Sistem',  'icon' => 'bi-shield-shaded',    'cls' => 'bg-amber-50 text-amber-700 ring-amber-200',     'avatar' => 'from-amber-500 to-orange-600'],
    'admin'            => ['label' => 'Administrator',    'icon' => 'bi-shield-lock-fill', 'cls' => 'bg-rose-50 text-rose-700 ring-rose-200',       'avatar' => 'from-rose-500 to-red-600'],
    'manajer_proyek'   => ['label' => 'Manajer Proyek',   'icon' => 'bi-briefcase-fill',   'cls' => 'bg-sky-50 text-sky-700 ring-sky-200',         'avatar' => 'from-sky-500 to-blue-600'],
    'teknisi_lapangan' => ['label' => 'Teknisi Lapangan', 'icon' => 'bi-tools',            'cls' => 'bg-emerald-50 text-emerald-700 ring-emerald-200','avatar' => 'from-emerald-500 to-green-600'],
    'gudang'           => ['label' => 'Staf Gudang',      'icon' => 'bi-box-seam-fill',    'cls' => 'bg-violet-50 text-violet-700 ring-violet-200', 'avatar' => 'from-violet-500 to-purple-600'],
];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Manajemen Pengguna</span>
    </nav>

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3.5">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-600 to-indigo-700 text-white flex items-center justify-center shadow-lg shadow-primary-600/25">
                <i class="bi bi-person-gear text-lg"></i>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Manajemen Pengguna & Akses Google</h2>
                <p class="text-xs text-slate-500 mt-0.5">Kelola akun staf, hak akses *role*, dan integrasi login Google (Gmail) PT Energy Lima Pilar</p>
            </div>
        </div>

        <a href="<?= BASE_URL ?>/pages/pengguna/create.php" class="btn-primary">
            <i class="bi bi-person-plus-fill"></i> Tambah Pengguna / Gmail
        </a>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        <div class="card p-4 stat-card" style="--stat-accent: #4f46e5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Akun</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int)$summary['total'] ?></p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="bi bi-people-fill text-lg"></i>
                </span>
            </div>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: #10b981">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Akun Aktif</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int)$summary['aktif'] ?></p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="bi bi-person-check-fill text-lg"></i>
                </span>
            </div>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: #0ea5e9">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tertaut Google</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int)$summary['google_linked'] ?></p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i class="bi bi-google text-lg"></i>
                </span>
            </div>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: #f59e0b">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Sedang Online</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int)$summary['online_now'] ?></p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="bi bi-broadcast text-lg"></i>
                </span>
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="card p-4 mb-5">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[240px]">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="<?= sanitize($search) ?>"
                    placeholder="Cari nama staf atau alamat email Gmail..."
                    class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:bg-white transition-all">
            </div>

            <select name="role" class="py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700" data-auto-submit>
                <option value="">Semua Role Jabatan</option>
                <?php foreach ($roleMeta as $rKey => $rInfo): ?>
                <option value="<?= $rKey ?>" <?= $filterRole === $rKey ? 'selected' : '' ?>><?= $rInfo['label'] ?></option>
                <?php endforeach; ?>
            </select>

            <select name="status" class="py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700" data-auto-submit>
                <option value="">Semua Status</option>
                <option value="aktif" <?= $filterStat === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="nonaktif" <?= $filterStat === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>

            <button type="submit" class="btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
            <?php if ($search !== '' || $filterRole !== '' || $filterStat !== ''): ?>
            <a href="<?= BASE_URL ?>/pages/pengguna/index.php" class="btn-secondary btn-sm text-red-600">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabel Daftar Pengguna -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="table-header">Pengguna & Email</th>
                        <th class="table-header">Role Sistem</th>
                        <th class="table-header">Metode Login</th>
                        <th class="table-header">Aktivitas Terakhir</th>
                        <th class="table-header">Status Akun</th>
                        <th class="table-header text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="table-cell text-center py-12 text-slate-400">
                            Tidak ada pengguna yang sesuai dengan filter pencarian.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $u):
                        $meta = $roleMeta[$u['role']] ?? $roleMeta['teknisi_lapangan'];
                        $inisial = strtoupper(substr($u['nama'], 0, 1));
                        $isGoogleLinked = !empty($u['google_id']);
                        $isGmailDomain  = str_ends_with(strtolower($u['email']), '@gmail.com');
                        $onlineInfo     = isUserOnline((int)$u['id']);
                        $isMe           = ((int)$u['id'] === (int)$currentUser['id']);
                    ?>
                    <tr class="table-row">
                        <td class="table-cell">
                            <div class="flex items-center gap-3">
                                <?php if (!empty($u['avatar'])): ?>
                                <img src="<?= sanitize($u['avatar']) ?>" alt="<?= sanitize($u['nama']) ?>"
                                     class="w-10 h-10 rounded-xl object-cover ring-2 ring-slate-100 flex-shrink-0"
                                     referrerpolicy="no-referrer">
                                <?php else: ?>
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br <?= $meta['avatar'] ?> text-white font-bold text-sm flex items-center justify-center flex-shrink-0 shadow-xs">
                                    <?= $inisial ?>
                                </div>
                                <?php endif; ?>

                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-slate-900 text-sm truncate"><?= sanitize($u['nama']) ?></span>
                                        <?php if ($isMe): ?>
                                        <span class="text-[10px] px-1.5 py-0.2 rounded bg-primary-50 text-primary-700 font-bold border border-primary-200">Anda</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs text-slate-500 truncate"><?= sanitize($u['email']) ?></div>
                                </div>
                            </div>
                        </td>

                        <td class="table-cell whitespace-nowrap">
                            <span class="badge-modern ring-1 <?= $meta['cls'] ?>">
                                <i class="bi <?= $meta['icon'] ?>"></i> <?= $meta['label'] ?>
                            </span>
                        </td>

                        <td class="table-cell whitespace-nowrap">
                            <?php if ($isGoogleLinked): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-sky-50 text-sky-700 border border-sky-200 text-xs font-bold"
                                  title="Sudah tertaut dengan ID Google OAuth">
                                <i class="bi bi-google text-sky-600"></i> Google Terhubung
                            </span>
                            <?php elseif ($isGmailDomain): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 border border-slate-200 text-xs font-semibold"
                                  title="Siap digunakan login dengan tombol Google kapan saja">
                                <i class="bi bi-google text-slate-400"></i> Siap Login Google
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 text-slate-500 border border-slate-200 text-xs font-medium">
                                <i class="bi bi-key"></i> Email & Password
                            </span>
                            <?php endif; ?>
                        </td>

                        <td class="table-cell whitespace-nowrap text-xs">
                            <?= formatLastSeen($onlineInfo['last_seen'], $onlineInfo['online']) ?>
                            <?php if ($u['role'] === 'teknisi_lapangan'): ?>
                            <div class="text-[11px] text-slate-400 mt-0.5"><?= (int)$u['total_laporan'] ?> laporan dikirim</div>
                            <?php endif; ?>
                        </td>

                        <td class="table-cell whitespace-nowrap">
                            <?php if ($u['status'] === 'aktif'): ?>
                            <span class="badge-modern bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                            </span>
                            <?php else: ?>
                            <span class="badge-modern bg-red-50 text-red-700 ring-1 ring-red-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Nonaktif
                            </span>
                            <?php endif; ?>
                        </td>

                        <td class="table-cell text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="<?= BASE_URL ?>/pages/pengguna/edit.php?id=<?= $u['id'] ?>"
                                   class="btn-secondary btn-sm" title="Edit Pengguna">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>

                                <?php if (!$isMe): ?>
                                <form method="POST" action="<?= BASE_URL ?>/api/pengguna/delete.php" class="inline"
                                      onsubmit="event.preventDefault(); confirmDelete(this, 'Hapus akun pengguna <?= addslashes(sanitize($u['nama'])) ?>?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn-secondary btn-sm text-red-600 hover:bg-red-50" title="Hapus Pengguna">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>