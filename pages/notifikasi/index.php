<?php
// pages/notifikasi/index.php — PUSAT NOTIFIKASI PINTAR DENGAN ACTION LINK OTOMATIS
$pageTitle   = 'Notifikasi';
$currentPage = 'notifikasi';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
requireLogin();

$user = currentUser();
$uid  = (int)$user['id'];
$db   = db();

// Aksi POST: Tandai semua dibaca, hapus satuan, atau bersihkan yang sudah dibaca
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('error', 'Token keamanan tidak valid.');
        redirect(BASE_URL . '/pages/notifikasi/index.php');
    }

    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'baca_semua') {
        $db->prepare("UPDATE notifikasi SET status_baca = 'sudah_dibaca' WHERE user_id = ?")->execute([$uid]);
        setFlash('success', 'Semua notifikasi telah ditandai sudah dibaca.');
    } elseif ($aksi === 'hapus_dibaca') {
        $db->prepare("DELETE FROM notifikasi WHERE user_id = ? AND status_baca = 'sudah_dibaca'")->execute([$uid]);
        setFlash('success', 'Notifikasi lama yang sudah dibaca berhasil dibersihkan.');
    } elseif ($aksi === 'baca_satu') {
        $nid = (int)($_POST['id'] ?? 0);
        $db->prepare("UPDATE notifikasi SET status_baca = 'sudah_dibaca' WHERE id = ? AND user_id = ?")->execute([$nid, $uid]);
        if (!empty($_POST['redirect_to'])) {
            redirect($_POST['redirect_to']);
        }
    } elseif ($aksi === 'hapus_satu') {
        $nid = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM notifikasi WHERE id = ? AND user_id = ?")->execute([$nid, $uid]);
    }
    redirect(BASE_URL . '/pages/notifikasi/index.php');
}

// Backward compatibility jika dipanggil via GET ?baca_semua=1
if (isset($_GET['baca_semua'])) {
    $db->prepare("UPDATE notifikasi SET status_baca = 'sudah_dibaca' WHERE user_id = ?")->execute([$uid]);
    setFlash('success', 'Semua notifikasi ditandai sudah dibaca.');
    redirect(BASE_URL . '/pages/notifikasi/index.php');
}

$tab     = $_GET['tab'] ?? 'semua';
$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$whereSql = "user_id = ?";
$params   = [$uid];
if ($tab === 'belum_dibaca') {
    $whereSql .= " AND status_baca = 'belum_dibaca'";
}

$total = $db->prepare("SELECT COUNT(*) FROM notifikasi WHERE $whereSql");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $db->prepare("SELECT * FROM notifikasi WHERE $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$notifikasis = $stmt->fetchAll();

$belumDibaca = countNotifBelumDibaca($uid);

/**
 * Deteksi ikon, warna, dan URL tujuan otomatis berdasarkan judul/isi notifikasi
 */
$resolveNotifMeta = function (string $judul): array {
    $j = strtolower($judul);
    if (str_contains($j, 'stok') || str_contains($j, 'komponen') || str_contains($j, 'material')) {
        return [
            'icon'  => 'bi-box-seam-fill',
            'cls'   => 'bg-amber-50 text-amber-600 ring-amber-200',
            'url'   => isAdminOrGudang() ? BASE_URL . '/pages/komponen/index.php' : BASE_URL . '/pages/komponen/status.php',
            'label' => 'Cek Inventaris Stok'
        ];
    }
    if (str_contains($j, 'laporan')) {
        return [
            'icon'  => 'bi-clipboard2-pulse-fill',
            'cls'   => 'bg-sky-50 text-sky-600 ring-sky-200',
            'url'   => BASE_URL . '/pages/laporan/index.php',
            'label' => 'Buka Laporan Lapangan'
        ];
    }
    if (str_contains($j, 'jadwal')) {
        return [
            'icon'  => 'bi-calendar-event-fill',
            'cls'   => 'bg-violet-50 text-violet-600 ring-violet-200',
            'url'   => BASE_URL . '/pages/jadwal/index.php',
            'label' => 'Lihat Kalender Jadwal'
        ];
    }
    if (str_contains($j, 'penempatan') || str_contains($j, 'proyek')) {
        return [
            'icon'  => 'bi-diagram-3-fill',
            'cls'   => 'bg-emerald-50 text-emerald-600 ring-emerald-200',
            'url'   => isTeknisi() ? BASE_URL . '/pages/dashboard/index.php' : BASE_URL . '/pages/proyek/index.php',
            'label' => 'Lihat Proyek'
        ];
    }
    return [
        'icon'  => 'bi-bell-fill',
        'cls'   => 'bg-indigo-50 text-indigo-600 ring-indigo-200',
        'url'   => '',
        'label' => ''
    ];
};

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Pusat Notifikasi</span>
    </nav>

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <div class="flex items-center gap-3.5">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-600 to-indigo-700 text-white flex items-center justify-center shadow-lg shadow-primary-600/25">
                <i class="bi bi-bell-fill text-lg"></i>
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Pusat Notifikasi Sistem</h2>
                    <?php if ($belumDibaca > 0): ?>
                    <span class="badge-modern bg-red-50 text-red-700 ring-1 ring-red-200"><?= $belumDibaca ?> Baru</span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Pemberitahuan pembaruan proyek, laporan lapangan, dan peringatan stok gudang</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <?php if ($belumDibaca > 0): ?>
            <form method="POST" class="inline">
                <?= csrfField() ?>
                <input type="hidden" name="aksi" value="baca_semua">
                <button type="submit" class="btn-secondary btn-sm text-primary-700">
                    <i class="bi bi-check2-all"></i> Tandai Semua Dibaca
                </button>
            </form>
            <?php endif; ?>

            <form method="POST" class="inline" onsubmit="event.preventDefault(); confirmDelete(this, 'Bersihkan semua notifikasi yang sudah dibaca?')">
                <?= csrfField() ?>
                <input type="hidden" name="aksi" value="hapus_dibaca">
                <button type="submit" class="btn-secondary btn-sm text-slate-600 hover:text-red-600">
                    <i class="bi bi-trash3"></i> Bersihkan yang Dibaca
                </button>
            </form>
        </div>
    </div>

    <!-- Filter Tab -->
    <div class="flex items-center gap-2 mb-4">
        <a href="?tab=semua"
           class="px-4 py-2 rounded-xl text-xs font-bold border transition-all
           <?= $tab === 'semua' ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' ?>">
            Semua Notifikasi
        </a>
        <a href="?tab=belum_dibaca"
           class="px-4 py-2 rounded-xl text-xs font-bold border transition-all flex items-center gap-1.5
           <?= $tab === 'belum_dibaca' ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' ?>">
            Belum Dibaca
            <?php if ($belumDibaca > 0): ?>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-red-500 text-white"><?= $belumDibaca ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Daftar Notifikasi -->
    <div class="card overflow-hidden">
        <?php if (empty($notifikasis)): ?>
        <div class="px-6 py-16 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl">
                <i class="bi bi-bell-slash"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800">Tidak Ada Notifikasi</h3>
            <p class="text-xs text-slate-400 mt-1">Semua pemberitahuan Anda sudah bersih.</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-100">
            <?php foreach ($notifikasis as $n):
                $isUnread = ($n['status_baca'] === 'belum_dibaca');
                $meta     = $resolveNotifMeta($n['judul']);
            ?>
            <div class="p-4 sm:p-5 flex items-start justify-between gap-4 transition-colors <?= $isUnread ? 'bg-indigo-50/40 hover:bg-indigo-50/60' : 'hover:bg-slate-50/80' ?>">
                <div class="flex items-start gap-3.5 min-w-0 flex-1">
                    <!-- Icon Kategori Notifikasi -->
                    <div class="w-10 h-10 rounded-xl ring-1 <?= $meta['cls'] ?> flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="bi <?= $meta['icon'] ?> text-base"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 class="text-sm <?= $isUnread ? 'font-extrabold text-slate-900' : 'font-semibold text-slate-700' ?>">
                                <?= sanitize($n['judul']) ?>
                            </h4>
                            <?php if ($isUnread): ?>
                            <span class="w-2 h-2 rounded-full bg-primary-600"></span>
                            <?php endif; ?>
                            <span class="text-[11px] text-slate-400">· <?= timeAgo($n['created_at']) ?></span>
                        </div>

                        <p class="text-xs text-slate-600 mt-1 leading-relaxed"><?= sanitize($n['pesan']) ?></p>

                        <!-- Tombol Pintasan Langsung ke Modul Terkait -->
                        <?php if ($meta['url'] !== ''): ?>
                        <div class="mt-2.5">
                            <form method="POST" class="inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="aksi" value="baca_satu">
                                <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                <input type="hidden" name="redirect_to" value="<?= sanitize($meta['url']) ?>">
                                <button type="submit" class="text-xs font-bold text-primary-600 hover:text-primary-800 inline-flex items-center gap-1">
                                    <?= $meta['label'] ?> <i class="bi bi-arrow-right"></i>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Aksi Kanan: Tandai Dibaca & Hapus -->
                <div class="flex items-center gap-1 flex-shrink-0">
                    <?php if ($isUnread): ?>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="aksi" value="baca_satu">
                        <input type="hidden" name="id" value="<?= $n['id'] ?>">
                        <button type="submit" class="p-2 rounded-lg text-primary-600 hover:bg-primary-50 transition-colors" title="Tandai Sudah Dibaca">
                            <i class="bi bi-check2-circle text-sm"></i>
                        </button>
                    </form>
                    <?php endif; ?>

                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="aksi" value="hapus_satu">
                        <input type="hidden" name="id" value="<?= $n['id'] ?>">
                        <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Hapus Notifikasi">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="px-5 py-3.5 border-t border-slate-100 flex items-center justify-between">
            <p class="text-xs text-slate-400">Halaman <?= $page ?> dari <?= $totalPages ?></p>
            <div class="flex gap-1.5">
                <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
                <a href="?page=<?= $pg ?>&tab=<?= urlencode($tab) ?>"
                   class="w-8 h-8 flex items-center justify-center rounded-xl text-xs font-bold transition-colors
                   <?= $pg === $page ? 'bg-primary-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                    <?= $pg ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>