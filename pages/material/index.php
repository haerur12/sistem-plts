<?php
// pages/material/index.php — RIWAYAT PENGGUNAAN MATERIAL & FILTER EXPORT CSV
$pageTitle   = 'Penggunaan Material';
$currentPage = 'material';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'gudang']);

$db       = db();
$search   = trim($_GET['search'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$proyekId = trim($_GET['proyek_id'] ?? '');
$dari     = trim($_GET['dari'] ?? '');
$sampai   = trim($_GET['sampai'] ?? '');

$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = '(k.nama_komponen LIKE ? OR pm.keterangan LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($kategori !== '') {
    $where[]  = 'k.kategori = ?';
    $params[] = $kategori;
}
if ($proyekId !== '') {
    $where[]  = 'pm.proyek_id = ?';
    $params[] = (int)$proyekId;
}
if ($dari !== '') {
    $where[]  = 'pm.tanggal_penggunaan >= ?';
    $params[] = $dari;
}
if ($sampai !== '') {
    $where[]  = 'pm.tanggal_penggunaan <= ?';
    $params[] = $sampai;
}

$whereStr = implode(' AND ', $where);

$cntStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM penggunaan_material pm
    JOIN komponen k ON pm.komponen_id = k.id
    WHERE $whereStr
");
$cntStmt->execute($params);
$total      = (int)$cntStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$stmt = $db->prepare("
    SELECT pm.*, k.nama_komponen, k.kategori, k.satuan, p.nama_proyek, u.nama as nama_user
    FROM penggunaan_material pm
    JOIN komponen k ON pm.komponen_id = k.id
    JOIN proyek p ON pm.proyek_id = p.id
    JOIN users u ON pm.user_id = u.id
    WHERE $whereStr
    ORDER BY pm.tanggal_penggunaan DESC, pm.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$materials = $stmt->fetchAll();

$proyekList = $db->query("SELECT id, nama_proyek FROM proyek ORDER BY nama_proyek ASC")->fetchAll();
$exportQuery = http_build_query([
    'search'    => $search,
    'kategori'  => $kategori,
    'proyek_id' => $proyekId,
    'dari'      => $dari,
    'sampai'    => $sampai,
]);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header Halaman -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-3.5">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-600 to-indigo-700 text-white flex items-center justify-center shadow-lg shadow-primary-600/25">
                <i class="bi bi-arrow-left-right text-lg"></i>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Riwayat Penggunaan Material</h2>
                <p class="text-xs text-slate-500 mt-0.5"><?= number_format($total) ?> transaksi pengeluaran material proyek tercatat</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?= BASE_URL ?>/api/export/material-csv.php?<?= $exportQuery ?>" class="btn-secondary btn-sm">
                <i class="bi bi-file-earmark-spreadsheet text-emerald-600"></i> Export CSV
            </a>
            <a href="<?= BASE_URL ?>/pages/material/create.php" class="btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Catat Penggunaan
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card p-4 mb-5">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div class="lg:col-span-2 relative">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="<?= sanitize($search) ?>"
                    placeholder="Cari nama komponen atau keterangan..."
                    class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            <select name="proyek_id" class="py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700">
                <option value="">Semua Proyek</option>
                <?php foreach ($proyekList as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $proyekId == $p['id'] ? 'selected' : '' ?>><?= sanitize($p['nama_proyek']) ?></option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="dari" value="<?= sanitize($dari) ?>"
                   class="py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700" title="Dari Tanggal">

            <input type="date" name="sampai" value="<?= sanitize($sampai) ?>"
                   class="py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700" title="Sampai Tanggal">

            <div class="flex gap-2">
                <button type="submit" class="btn-primary btn-sm flex-1 justify-center">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <?php if ($search || $kategori || $proyekId || $dari || $sampai): ?>
                <a href="<?= BASE_URL ?>/pages/material/index.php" class="btn-secondary btn-sm text-red-600" title="Reset Filter">
                    <i class="bi bi-x-lg"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabel Riwayat Penggunaan Material -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="table-header">No</th>
                        <th class="table-header">Tanggal</th>
                        <th class="table-header">Komponen PLTS</th>
                        <th class="table-header">Proyek Tujuan</th>
                        <th class="table-header">Jumlah Keluar</th>
                        <th class="table-header">Dicatat Oleh</th>
                        <th class="table-header">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($materials)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-14 text-center">
                            <i class="bi bi-inbox text-4xl text-slate-200 block mb-2"></i>
                            <p class="text-sm text-slate-400">Belum ada catatan penggunaan material</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($materials as $i => $m): ?>
                    <tr class="table-row">
                        <td class="table-cell text-slate-400 text-xs"><?= $offset + $i + 1 ?></td>
                        <td class="table-cell whitespace-nowrap text-xs font-semibold text-slate-600">
                            <?= formatTanggalShort($m['tanggal_penggunaan']) ?>
                        </td>
                        <td class="table-cell">
                            <div class="font-bold text-slate-800 text-sm"><?= sanitize($m['nama_komponen']) ?></div>
                            <span class="text-[11px] text-slate-400"><?= sanitize($m['kategori']) ?></span>
                        </td>
                        <td class="table-cell">
                            <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $m['proyek_id'] ?>" class="text-xs font-semibold text-primary-700 hover:underline">
                                <?= sanitize($m['nama_proyek']) ?>
                            </a>
                        </td>
                        <td class="table-cell whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-red-50 text-red-700 font-extrabold text-xs">
                                -<?= number_format((int)$m['jumlah_digunakan']) ?> <?= sanitize($m['satuan']) ?>
                            </span>
                        </td>
                        <td class="table-cell text-xs text-slate-600 whitespace-nowrap">
                            <i class="bi bi-person-circle text-slate-400 mr-1"></i><?= sanitize($m['nama_user']) ?>
                        </td>
                        <td class="table-cell text-xs text-slate-500"><?= sanitize($m['keterangan'] ?: '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="px-5 py-3.5 border-t border-slate-100 flex items-center justify-between">
            <p class="text-xs text-slate-400">Menampilkan <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> dari <?= $total ?> catatan</p>
            <div class="flex gap-1.5">
                <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
                <a href="?page=<?= $pg ?>&<?= $exportQuery ?>"
                    class="w-8 h-8 flex items-center justify-center rounded-xl text-xs font-bold transition-colors
                    <?= $pg === $page ? 'bg-primary-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                    <?= $pg ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>