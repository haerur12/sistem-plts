<?php
$pageTitle   = 'Penggunaan Material';
$currentPage = 'material';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','gudang']);

$db      = db();
$search  = $_GET['search']     ?? '';
$kat     = $_GET['kategori']   ?? '';
$proyek  = $_GET['proyek_id']  ?? '';
$dari    = $_GET['dari']       ?? '';
$sampai  = $_GET['sampai']     ?? '';
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($search)  { $where[] = 'k.nama_komponen LIKE ?'; $params[] = "%$search%"; }
if ($kat)     { $where[] = 'k.kategori = ?';         $params[] = $kat; }
if ($proyek)  { $where[] = 'pm.proyek_id = ?';       $params[] = (int)$proyek; }
if ($dari)    { $where[] = 'pm.tanggal_penggunaan >= ?'; $params[] = $dari; }
if ($sampai)  { $where[] = 'pm.tanggal_penggunaan <= ?'; $params[] = $sampai; }
$whereStr = implode(' AND ', $where);

$cntStmt = $db->prepare("
    SELECT COUNT(*) FROM penggunaan_material pm
    JOIN komponen k ON pm.komponen_id = k.id
    WHERE $whereStr
");
$cntStmt->execute($params);
$totalRows  = (int)$cntStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("
    SELECT pm.*, k.nama_komponen, k.satuan, k.kategori,
           p.nama_proyek, u.nama as nama_user
    FROM penggunaan_material pm
    JOIN komponen k ON pm.komponen_id = k.id
    JOIN proyek p ON pm.proyek_id = p.id
    JOIN users u ON pm.user_id = u.id
    WHERE $whereStr
    ORDER BY pm.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$materials = $stmt->fetchAll();

// Statistik bulan ini
$statBulan = $db->query("
    SELECT COUNT(*) as transaksi, SUM(pm.jumlah_digunakan) as total_unit,
           COUNT(DISTINCT pm.komponen_id) as jenis_komponen
    FROM penggunaan_material pm
    WHERE MONTH(pm.created_at) = MONTH(CURDATE()) AND YEAR(pm.created_at) = YEAR(CURDATE())
")->fetch();

// Dropdown filter
$proyekList  = $db->query("SELECT id, nama_proyek FROM proyek ORDER BY nama_proyek")->fetchAll();
$kategoriList= $db->query("SELECT DISTINCT k.kategori FROM komponen k ORDER BY k.kategori")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Header -->
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">Penggunaan Material</h2>
        <p class="text-sm text-slate-400 mt-0.5"><?= $totalRows ?> total catatan</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>/api/export/material-csv.php" class="btn-secondary btn-sm">
            <i class="bi bi-download"></i> Export CSV
        </a>
        <a href="<?= BASE_URL ?>/pages/laporan/stok.php" class="btn-secondary btn-sm">
            <i class="bi bi-file-bar-graph"></i> Laporan Stok
        </a>
        <a href="<?= BASE_URL ?>/pages/material/create.php" class="btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Catat Penggunaan
        </a>
    </div>
</div>

<!-- Stat Bulan Ini -->
<div class="grid grid-cols-3 gap-3 mb-5">
    <?php
    $sCards = [
        ['val'=>$statBulan['transaksi']      ?? 0, 'label'=>'Transaksi',       'sub'=>'Bulan ini',      'icon'=>'bi-receipt',       'color'=>'text-primary-700 bg-primary-50'],
        ['val'=>$statBulan['total_unit']      ?? 0, 'label'=>'Total Unit Keluar','sub'=>'Semua komponen', 'icon'=>'bi-box-arrow-right','color'=>'text-red-600 bg-red-50'],
        ['val'=>$statBulan['jenis_komponen']  ?? 0, 'label'=>'Jenis Komponen',  'sub'=>'Digunakan',      'icon'=>'bi-grid-3x3-gap',  'color'=>'text-purple-700 bg-purple-50'],
    ];
    foreach ($sCards as $s): ?>
    <div class="card p-4 flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl <?= $s['color'] ?> flex items-center justify-center flex-shrink-0">
            <i class="bi <?= $s['icon'] ?> text-lg"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-800"><?= number_format($s['val']) ?></div>
            <div class="text-xs font-medium text-slate-600"><?= $s['label'] ?></div>
            <div class="text-xs text-slate-400"><?= $s['sub'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="card p-4 mb-5">
    <form method="GET">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="<?= sanitize($search) ?>"
                    placeholder="Cari komponen..."
                    class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
            <select name="kategori" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white" data-auto-submit>
                <option value="">Semua Kategori</option>
                <?php foreach ($kategoriList as $k): ?>
                <option value="<?= sanitize($k) ?>" <?= $kat===$k?'selected':'' ?>><?= sanitize($k) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="proyek_id" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                <option value="">Semua Proyek</option>
                <?php foreach ($proyekList as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $proyek==$p['id']?'selected':'' ?>><?= sanitize($p['nama_proyek']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary flex-1 justify-center btn-sm">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <?php if ($search || $kat || $proyek || $dari || $sampai): ?>
                <a href="<?= BASE_URL ?>/pages/material/index.php" class="btn-secondary btn-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <!-- Filter Tanggal -->
        <div class="flex items-center gap-3 pt-3 border-t border-slate-50">
            <span class="text-xs text-slate-500 font-medium flex-shrink-0">Rentang Tanggal:</span>
            <input type="date" name="dari" value="<?= $dari ?>"
                class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            <span class="text-slate-400 text-sm">s/d</span>
            <input type="date" name="sampai" value="<?= $sampai ?>"
                class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
    </form>
</div>

<!-- Tabel -->
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="table-header">No</th>
                    <th class="table-header">Komponen</th>
                    <th class="table-header">Proyek</th>
                    <th class="table-header text-center">Jumlah Keluar</th>
                    <th class="table-header">Dicatat Oleh</th>
                    <th class="table-header">Tanggal</th>
                    <th class="table-header">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php if (empty($materials)): ?>
                <tr><td colspan="7" class="px-4 py-12 text-center">
                    <i class="bi bi-inbox text-4xl text-slate-200 block mb-2"></i>
                    <p class="text-sm text-slate-400">Belum ada catatan penggunaan</p>
                </td></tr>
                <?php else: ?>
                <?php foreach ($materials as $i => $m): ?>
                <tr class="table-row">
                    <td class="table-cell text-slate-400 text-xs"><?= $offset+$i+1 ?></td>
                    <td class="table-cell">
                        <div class="font-semibold text-sm text-slate-800"><?= sanitize($m['nama_komponen']) ?></div>
                        <span class="inline-block mt-0.5 text-xs px-1.5 py-0.5 bg-slate-100 text-slate-500 rounded">
                            <?= sanitize($m['kategori']) ?>
                        </span>
                    </td>
                    <td class="table-cell">
                        <span class="text-xs text-slate-600"><?= sanitize($m['nama_proyek']) ?></span>
                    </td>
                    <td class="table-cell text-center">
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-red-50 text-red-600 rounded-full text-sm font-bold">
                            <i class="bi bi-dash text-xs"></i><?= $m['jumlah_digunakan'] ?>
                            <span class="font-normal text-xs text-red-400"><?= sanitize($m['satuan']) ?></span>
                        </span>
                    </td>
                    <td class="table-cell text-xs text-slate-500"><?= sanitize($m['nama_user']) ?></td>
                    <td class="table-cell">
                        <span class="text-xs text-slate-500"><?= formatTanggalShort($m['tanggal_penggunaan']) ?></span>
                        <span class="block text-xs text-slate-300"><?= timeAgo($m['created_at']) ?></span>
                    </td>
                    <td class="table-cell text-xs text-slate-400"><?= sanitize($m['keterangan'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between">
        <p class="text-xs text-slate-400">
            Menampilkan <?= $offset+1 ?>–<?= min($offset+$perPage,$totalRows) ?> dari <?= $totalRows ?>
        </p>
        <div class="flex gap-1">
            <?php for ($pg = 1; $pg <= min($totalPages,7); $pg++): ?>
            <a href="?page=<?= $pg ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kat) ?>&proyek_id=<?= $proyek ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-xs transition-colors
                <?= $pg===$page ? 'bg-primary-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                <?= $pg ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>