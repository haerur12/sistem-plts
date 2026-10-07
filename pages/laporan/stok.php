<?php
$pageTitle   = 'Laporan Stok Material';
$currentPage = 'laporan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','gudang']);

$db = db();

$komponen   = $db->query("SELECT * FROM komponen ORDER BY kategori, nama_komponen")->fetchAll();
$penggunaan = $db->query("
    SELECT pm.*, k.nama_komponen, k.satuan, p.nama_proyek, u.nama as nama_user
    FROM penggunaan_material pm
    JOIN komponen k ON pm.komponen_id = k.id
    JOIN proyek p ON pm.proyek_id = p.id
    JOIN users u ON pm.user_id = u.id
    ORDER BY pm.created_at DESC
    LIMIT 50
")->fetchAll();

// Statistik
$totalKomponen = count($komponen);
$stokAman      = count(array_filter($komponen, fn($k) => stokStatus($k['stok_tersedia'],$k['stok_minimum'])[2] === 'aman'));
$stokRendah    = count(array_filter($komponen, fn($k) => stokStatus($k['stok_tersedia'],$k['stok_minimum'])[2] === 'rendah'));
$stokKritis    = count(array_filter($komponen, fn($k) => stokStatus($k['stok_tersedia'],$k['stok_minimum'])[2] === 'kritis'));
$stokHabis     = count(array_filter($komponen, fn($k) => stokStatus($k['stok_tersedia'],$k['stok_minimum'])[2] === 'habis'));

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">Laporan Stok Material</h2>
        <p class="text-sm text-slate-400 mt-0.5">Rekapitulasi stok dan penggunaan komponen PLTS</p>
    </div>
    <a href="<?= BASE_URL ?>/pages/laporan/pdf-stok.php" target="_blank" class="btn-danger">
        <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
    </a>
</div>

<!-- Stat Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <?php
    $stats = [
        ['val'=>$totalKomponen, 'label'=>'Total Komponen', 'color'=>'bg-blue-100 text-blue-700',   'icon'=>'bi-grid-3x3-gap'],
        ['val'=>$stokAman,      'label'=>'Stok Aman',      'color'=>'bg-green-100 text-green-700', 'icon'=>'bi-check-circle'],
        ['val'=>$stokKritis + $stokRendah, 'label'=>'Perlu Perhatian','color'=>'bg-amber-100 text-amber-700','icon'=>'bi-exclamation-triangle'],
        ['val'=>$stokHabis,     'label'=>'Stok Habis',     'color'=>'bg-red-100 text-red-700',     'icon'=>'bi-x-circle'],
    ];
    foreach ($stats as $s): ?>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl <?= $s['color'] ?> flex items-center justify-center">
            <i class="bi <?= $s['icon'] ?>"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-800"><?= $s['val'] ?></div>
            <div class="text-xs text-slate-400"><?= $s['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <!-- Tabel Stok -->
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-700">Status Stok Semua Komponen</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="table-header">Komponen</th>
                        <th class="table-header text-center">Tersedia</th>
                        <th class="table-header text-center">Min</th>
                        <th class="table-header">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($komponen as $k):
                        [$cls, $label, $statusKey] = stokStatus($k['stok_tersedia'], $k['stok_minimum']);
                    ?>
                    <tr class="table-row">
                        <td class="table-cell">
                            <div class="text-sm font-medium text-slate-800 leading-tight"><?= sanitize($k['nama_komponen']) ?></div>
                            <div class="text-xs text-slate-400"><?= sanitize($k['kategori']) ?></div>
                        </td>
                        <td class="table-cell text-center">
                            <span class="font-semibold <?= $statusKey==='kritis'||$statusKey==='habis' ? 'text-red-600' : 'text-slate-800' ?>">
                                <?= $k['stok_tersedia'] ?>
                            </span>
                            <span class="text-xs text-slate-400"> <?= sanitize($k['satuan']) ?></span>
                        </td>
                        <td class="table-cell text-center text-xs text-slate-400"><?= $k['stok_minimum'] ?></td>
                        <td class="table-cell">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $cls ?>"><?= $label ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Riwayat Penggunaan -->
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-700">Riwayat Penggunaan Material</h3>
        </div>
        <div class="overflow-y-auto" style="max-height:480px">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-100 sticky top-0">
                    <tr>
                        <th class="table-header">Komponen</th>
                        <th class="table-header">Proyek</th>
                        <th class="table-header text-center">Jml</th>
                        <th class="table-header">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (empty($penggunaan)): ?>
                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-400">Belum ada penggunaan</td></tr>
                    <?php else: ?>
                    <?php foreach ($penggunaan as $pm): ?>
                    <tr class="table-row">
                        <td class="table-cell">
                            <div class="text-xs font-medium text-slate-800"><?= mb_strimwidth(sanitize($pm['nama_komponen']),0,22,'...') ?></div>
                        </td>
                        <td class="table-cell">
                            <div class="text-xs text-slate-400"><?= mb_strimwidth(sanitize($pm['nama_proyek']),0,20,'...') ?></div>
                        </td>
                        <td class="table-cell text-center">
                            <span class="text-red-600 font-semibold text-xs">-<?= $pm['jumlah_digunakan'] ?></span>
                            <span class="text-xs text-slate-400"> <?= sanitize($pm['satuan']) ?></span>
                        </td>
                        <td class="table-cell text-xs text-slate-400"><?= formatTanggalShort($pm['tanggal_penggunaan']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>