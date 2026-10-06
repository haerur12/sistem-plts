<?php
// pages/laporan/index.php
$pageTitle   = 'Laporan Lapangan';
$currentPage = 'laporan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek','teknisi_lapangan']);

$db   = db();
$user = currentUser();

// Filter
$filterStatus = $_GET['status'] ?? '';
$search       = $_GET['search'] ?? '';
$perPage      = 10;
$page         = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

// Teknisi hanya lihat laporan sendiri
if (isTeknisi()) { $where[] = 'l.user_id = ?'; $params[] = $user['id']; }
if ($filterStatus) { $where[] = 'l.status_pengerjaan = ?'; $params[] = $filterStatus; }
if ($search)       { $where[] = 'l.judul_laporan LIKE ?'; $params[] = "%$search%"; }

$whereStr = implode(' AND ', $where);

$total     = $db->prepare("SELECT COUNT(*) FROM laporan_lapangan l WHERE $whereStr");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("
    SELECT l.*, p.nama_proyek, u.nama as nama_teknisi,
    (SELECT COUNT(*) FROM foto_lapangan f WHERE f.laporan_id = l.id) as jumlah_foto
    FROM laporan_lapangan l
    JOIN proyek p ON l.proyek_id = p.id
    JOIN users u ON l.user_id = u.id
    WHERE $whereStr
    ORDER BY l.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$laporans = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">Laporan Lapangan</h2>
        <p class="text-sm text-slate-400 mt-0.5"><?= $totalRows ?> total laporan</p>
    </div>
    <?php if (isTeknisi() || isAdminOrManajer()): ?>
    <a href="<?= BASE_URL ?>/pages/laporan/create.php" class="btn-primary">
        <i class="bi bi-plus-lg"></i> Unggah Laporan
    </a>
    <?php endif; ?>
</div>

<!-- Filter -->
<div class="card p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3">
        <div class="flex-1 min-w-48">
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="<?= sanitize($search) ?>"
                    placeholder="Cari judul laporan..."
                    class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
        </div>
        <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
            <option value="">Semua Status</option>
            <?php foreach (['belum_mulai'=>'Belum Mulai','dalam_proses'=>'Dalam Proses','selesai'=>'Selesai','terkendala'=>'Terkendala'] as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= $filterStatus === $val ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary"><i class="bi bi-funnel"></i> Filter</button>
        <?php if ($filterStatus || $search): ?>
        <a href="<?= BASE_URL ?>/pages/laporan/index.php" class="btn-secondary"><i class="bi bi-x-lg"></i> Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Daftar Laporan -->
<?php if (empty($laporans)): ?>
<div class="card px-5 py-16 text-center">
    <i class="bi bi-file-earmark-x text-5xl text-slate-200 block mb-3"></i>
    <p class="text-slate-400">Belum ada laporan lapangan</p>
</div>
<?php else: ?>
<div class="space-y-3">
    <?php foreach ($laporans as $l): ?>
    <div class="card p-5 hover:shadow-md transition-shadow">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="bi bi-file-earmark-text text-green-600"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-800"><?= sanitize($l['judul_laporan']) ?></h4>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1">
                            <span class="text-xs text-slate-400">
                                <i class="bi bi-diagram-3 mr-1"></i><?= sanitize($l['nama_proyek']) ?>
                            </span>
                            <span class="text-xs text-slate-400">
                                <i class="bi bi-person mr-1"></i><?= sanitize($l['nama_teknisi']) ?>
                            </span>
                            <span class="text-xs text-slate-400">
                                <i class="bi bi-calendar mr-1"></i><?= formatTanggal($l['tanggal_laporan']) ?>
                            </span>
                            <?php if ($l['jumlah_foto'] > 0): ?>
                            <span class="text-xs text-primary-600">
                                <i class="bi bi-images mr-1"></i><?= $l['jumlah_foto'] ?> foto
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-500 mt-2 line-clamp-2">
                            <?= mb_strimwidth(sanitize($l['deskripsi']), 0, 150, '...') ?>
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-2 flex-shrink-0">
                        <?= statusBadge($l['status_pengerjaan']) ?>
                        <span class="text-xs text-slate-400"><?= timeAgo($l['created_at']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="flex items-center justify-between mt-4">
    <p class="text-xs text-slate-400">Menampilkan <?= $offset+1 ?>–<?= min($offset+$perPage,$totalRows) ?> dari <?= $totalRows ?></p>
    <div class="flex gap-1">
        <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
        <a href="?page=<?= $pg ?>&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($search) ?>"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-xs transition-colors
            <?= $pg === $page ? 'bg-primary-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            <?= $pg ?>
        </a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>