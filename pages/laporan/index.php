<?php
$pageTitle   = 'Laporan Lapangan';
$currentPage = 'laporan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek','teknisi_lapangan']);

$db   = db();
$user = currentUser();

$filterStatus = $_GET['status'] ?? '';
$search       = $_GET['search'] ?? '';
$filterProyek = $_GET['proyek_id'] ?? '';
$perPage      = isTeknisi() ? 9 : 10;
$page         = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if (isTeknisi())    { $where[] = 'l.user_id = ?';            $params[] = $user['id']; }
if ($filterStatus)  { $where[] = 'l.status_pengerjaan = ?';  $params[] = $filterStatus; }
if ($filterProyek)  { $where[] = 'l.proyek_id = ?';          $params[] = (int)$filterProyek; }
if ($search)        { $where[] = 'l.judul_laporan LIKE ?';   $params[] = "%$search%"; }
$whereStr = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM laporan_lapangan l WHERE $whereStr");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("
    SELECT l.*,
        p.nama_proyek,
        u.nama as nama_teknisi,
        (SELECT COUNT(*) FROM foto_lapangan f WHERE f.laporan_id = l.id) as jumlah_foto,
        (SELECT path_file FROM foto_lapangan f WHERE f.laporan_id = l.id ORDER BY f.id ASC LIMIT 1) as foto_pertama
    FROM laporan_lapangan l
    JOIN proyek p ON l.proyek_id = p.id
    JOIN users u ON l.user_id = u.id
    WHERE $whereStr
    ORDER BY l.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$laporans = $stmt->fetchAll();

// Dropdown proyek untuk filter
$proyekList = $db->query("SELECT id, nama_proyek FROM proyek ORDER BY nama_proyek")->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">Laporan Lapangan</h2>
        <p class="text-sm text-slate-400 mt-0.5"><?= $totalRows ?> total laporan</p>
    </div>
    <?php if (!isGudang()): ?>
    <a href="<?= BASE_URL ?>/pages/laporan/create.php" class="btn-primary">
        <i class="bi bi-plus-lg"></i> Unggah Laporan
    </a>
    <?php endif; ?>
</div>

<!-- Filter -->
<div class="card p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3">
        <div class="flex-1 min-w-48 relative">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" name="search" value="<?= sanitize($search) ?>"
                placeholder="Cari judul laporan..."
                class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white" data-auto-submit>
            <option value="">Semua Status</option>
            <?php foreach (['belum_mulai'=>'Belum Mulai','dalam_proses'=>'Dalam Proses','selesai'=>'Selesai','terkendala'=>'Terkendala'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= $filterStatus===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
        <select name="proyek_id" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
            <option value="">Semua Proyek</option>
            <?php foreach ($proyekList as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $filterProyek==$p['id']?'selected':'' ?>>
                <?= sanitize($p['nama_proyek']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary"><i class="bi bi-funnel"></i> Filter</button>
        <?php if ($filterStatus || $search || $filterProyek): ?>
        <a href="<?= BASE_URL ?>/pages/laporan/index.php" class="btn-secondary"><i class="bi bi-x-lg"></i> Reset</a>
        <?php endif; ?>
    </form>
</div>

<?php if (isTeknisi()): ?>
<!-- ===== TAMPILAN GRID CARD UNTUK TEKNISI ===== -->
<?php if (empty($laporans)): ?>
<div class="card px-5 py-16 text-center">
    <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <i class="bi bi-file-earmark-x text-4xl text-slate-300"></i>
    </div>
    <h3 class="text-base font-semibold text-slate-600 mb-1">Belum ada laporan</h3>
    <p class="text-sm text-slate-400 mb-5">Mulai dokumentasikan pekerjaan lapangan Anda</p>
    <a href="<?= BASE_URL ?>/pages/laporan/create.php" class="btn-primary inline-flex">
        <i class="bi bi-upload"></i> Unggah Laporan Pertama
    </a>
</div>
<?php else: ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($laporans as $l):
        $statusColors = [
            'selesai'      => ['bg'=>'bg-green-500', 'text'=>'text-green-700', 'light'=>'bg-green-100'],
            'dalam_proses' => ['bg'=>'bg-blue-500',  'text'=>'text-blue-700',  'light'=>'bg-blue-100'],
            'terkendala'   => ['bg'=>'bg-red-500',   'text'=>'text-red-700',   'light'=>'bg-red-100'],
            'belum_mulai'  => ['bg'=>'bg-slate-400', 'text'=>'text-slate-600', 'light'=>'bg-slate-100'],
        ];
        $sc = $statusColors[$l['status_pengerjaan']] ?? $statusColors['belum_mulai'];
    ?>
    <div class="card overflow-hidden card-hover group">
        <!-- Foto Thumbnail -->
        <div class="relative aspect-video bg-slate-100 overflow-hidden">
            <?php if ($l['foto_pertama']): ?>
            <img src="<?= BASE_URL ?>/uploads/<?= sanitize($l['foto_pertama']) ?>"
                alt="Foto laporan"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
                onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center\'><i class=\'bi bi-image text-4xl text-slate-300\'></i></div>'">
            <?php else: ?>
            <div class="w-full h-full flex flex-col items-center justify-center">
                <i class="bi bi-file-earmark-text text-4xl text-slate-300 mb-1"></i>
                <span class="text-xs text-slate-400">Tidak ada foto</span>
            </div>
            <?php endif; ?>

            <!-- Status Badge Overlay -->
            <div class="absolute top-3 left-3">
                <?= statusBadge($l['status_pengerjaan']) ?>
            </div>

            <!-- Foto Count -->
            <?php if ($l['jumlah_foto'] > 0): ?>
            <div class="absolute bottom-3 right-3 bg-black/60 text-white text-xs px-2 py-1 rounded-lg flex items-center gap-1">
                <i class="bi bi-images"></i> <?= $l['jumlah_foto'] ?>
            </div>
            <?php endif; ?>

            <!-- Hover Overlay -->
            <div class="absolute inset-0 bg-primary-900/0 group-hover:bg-primary-900/60 transition-all duration-300 flex items-center justify-center">
                <a href="<?= BASE_URL ?>/pages/laporan/show.php?id=<?= $l['id'] ?>"
                    class="opacity-0 group-hover:opacity-100 transition-opacity duration-300
                    bg-white text-primary-800 text-sm font-medium px-4 py-2 rounded-xl
                    flex items-center gap-2 shadow-lg">
                    <i class="bi bi-eye"></i> Lihat Detail
                </a>
            </div>
        </div>

        <!-- Card Body -->
        <div class="p-4">
            <h4 class="text-sm font-semibold text-slate-800 mb-1 line-clamp-1">
                <?= sanitize($l['judul_laporan']) ?>
            </h4>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-3">
                <i class="bi bi-diagram-3"></i>
                <span class="truncate"><?= sanitize($l['nama_proyek']) ?></span>
            </div>
            <div class="text-xs text-slate-500 line-clamp-2 mb-3">
                <?= mb_strimwidth(sanitize($l['deskripsi']), 0, 100, '...') ?>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-400">
                    <i class="bi bi-calendar2 me-1"></i><?= formatTanggal($l['tanggal_laporan']) ?>
                </span>
                <a href="<?= BASE_URL ?>/pages/laporan/show.php?id=<?= $l['id'] ?>"
                    class="text-xs text-primary-700 hover:text-primary-900 font-medium flex items-center gap-1">
                    Detail <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="flex items-center justify-between mt-5">
    <p class="text-xs text-slate-400">
        Menampilkan <?= $offset+1 ?>–<?= min($offset+$perPage,$totalRows) ?> dari <?= $totalRows ?>
    </p>
    <div class="flex gap-1.5">
        <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
        <a href="?page=<?= $pg ?>&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($search) ?>&proyek_id=<?= $filterProyek ?>"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-xs transition-colors
            <?= $pg===$page ? 'bg-primary-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            <?= $pg ?>
        </a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php else: ?>
<!-- ===== TAMPILAN LIST UNTUK ADMIN/MANAJER ===== -->
<div class="space-y-3">
    <?php if (empty($laporans)): ?>
    <div class="card px-5 py-12 text-center">
        <i class="bi bi-file-earmark-x text-5xl text-slate-200 block mb-3"></i>
        <p class="text-slate-400">Belum ada laporan lapangan</p>
    </div>
    <?php else: ?>
    <?php foreach ($laporans as $l): ?>
    <div class="card p-5 hover:shadow-md transition-shadow">
        <div class="flex gap-4">
            <!-- Thumbnail kecil -->
            <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-100 flex-shrink-0">
                <?php if ($l['foto_pertama']): ?>
                <img src="<?= BASE_URL ?>/uploads/<?= sanitize($l['foto_pertama']) ?>"
                    class="w-full h-full object-cover" loading="lazy">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center">
                    <i class="bi bi-file-earmark-text text-2xl text-slate-300"></i>
                </div>
                <?php endif; ?>
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-800"><?= sanitize($l['judul_laporan']) ?></h4>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-1">
                            <span class="text-xs text-slate-400">
                                <i class="bi bi-diagram-3 me-1"></i><?= sanitize($l['nama_proyek']) ?>
                            </span>
                            <span class="text-xs text-slate-400">
                                <i class="bi bi-person me-1"></i><?= sanitize($l['nama_teknisi']) ?>
                            </span>
                            <span class="text-xs text-slate-400">
                                <i class="bi bi-calendar me-1"></i><?= formatTanggal($l['tanggal_laporan']) ?>
                            </span>
                            <?php if ($l['jumlah_foto'] > 0): ?>
                            <span class="text-xs text-primary-600">
                                <i class="bi bi-images me-1"></i><?= $l['jumlah_foto'] ?> foto
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-500 mt-1.5 line-clamp-1">
                            <?= mb_strimwidth(sanitize($l['deskripsi']), 0, 120, '...') ?>
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-2 flex-shrink-0">
                        <?= statusBadge($l['status_pengerjaan']) ?>
                        <a href="<?= BASE_URL ?>/pages/laporan/show.php?id=<?= $l['id'] ?>"
                            class="text-xs text-primary-700 font-medium hover:text-primary-900">
                            Lihat Detail <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>