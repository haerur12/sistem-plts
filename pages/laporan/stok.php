<?php
// pages/laporan/stok.php
$pageTitle   = 'Laporan Stok & Penggunaan';
$currentPage = 'laporan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','gudang','manajer_proyek']);

$db = db();

// Summary stok
$summary = $db->query("
    SELECT
        SUM(CASE WHEN stok_tersedia > stok_minimum * 1.5 THEN 1 ELSE 0 END) as aman,
        SUM(CASE WHEN stok_tersedia > stok_minimum AND stok_tersedia <= stok_minimum * 1.5 THEN 1 ELSE 0 END) as rendah,
        SUM(CASE WHEN stok_tersedia > 0 AND stok_tersedia <= stok_minimum THEN 1 ELSE 0 END) as kritis,
        SUM(CASE WHEN stok_tersedia = 0 THEN 1 ELSE 0 END) as habis
    FROM komponen
")->fetch();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">Laporan Stok & Penggunaan</h2>
        <p class="text-sm text-slate-400 mt-0.5">Ringkasan kondisi material dan penggunaannya</p>
    </div>
    <a href="<?= BASE_URL ?>/pages/material/index.php" class="btn-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<!-- Summary Stok -->
<div class="grid grid-cols-4 gap-3 mb-5">
    <?php
    $sCards = [
        ['val'=>$summary['aman']?:0,   'label'=>'Aman',   'cls'=>'bg-green-50 border-green-200', 'dot'=>'bg-green-500', 'text'=>'text-green-700'],
        ['val'=>$summary['rendah']?:0, 'label'=>'Rendah', 'cls'=>'bg-amber-50 border-amber-200', 'dot'=>'bg-amber-500', 'text'=>'text-amber-700'],
        ['val'=>$summary['kritis']?:0, 'label'=>'Kritis', 'cls'=>'bg-orange-50 border-orange-200','dot'=>'bg-orange-500','text'=>'text-orange-700'],
        ['val'=>$summary['habis']?:0,  'label'=>'Habis',  'cls'=>'bg-red-50 border-red-200',     'dot'=>'bg-red-500',   'text'=>'text-red-700'],
    ];
    foreach ($sCards as $s): ?>
    <div class="border rounded-xl p-3 <?= $s['cls'] ?>">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-2 h-2 rounded-full <?= $s['dot'] ?>"></div>
            <span class="text-xs font-medium <?= $s['text'] ?>"><?= $s['label'] ?></span>
        </div>
        <div class="text-2xl font-bold text-slate-800"><?= $s['val'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Chart Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

    <!-- Chart Stok per Kategori (Horizontal Bar) -->
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
            <i class="bi bi-bar-chart-horizontal text-primary-600"></i>
            Stok Tersedia Per Kategori
        </h3>
        <div style="height:200px">
            <canvas id="chartStokKategori"></canvas>
        </div>
    </div>

    <!-- Chart Penggunaan Per Proyek (Donut) -->
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
            <i class="bi bi-pie-chart text-purple-600"></i>
            Penggunaan Per Proyek (Bulan Ini)
        </h3>
        <?php
        $byProyek = $db->query("
            SELECT p.nama_proyek, SUM(pm.jumlah_digunakan) as total
            FROM penggunaan_material pm
            JOIN proyek p ON pm.proyek_id = p.id
            WHERE MONTH(pm.created_at) = MONTH(CURDATE())
            GROUP BY pm.proyek_id, p.nama_proyek
            ORDER BY total DESC LIMIT 6
        ")->fetchAll();
        ?>
        <?php if (empty($byProyek)): ?>
        <div class="flex items-center justify-center h-44 text-slate-300">
            <div class="text-center">
                <i class="bi bi-pie-chart text-5xl block mb-2 opacity-30"></i>
                <p class="text-sm">Belum ada data bulan ini</p>
            </div>
        </div>
        <?php else: ?>
        <div class="flex items-center gap-4">
            <div style="height:160px;width:160px;flex-shrink:0">
                <canvas id="chartProyek"></canvas>
            </div>
            <div class="flex-1 space-y-2">
                <?php
                $colors = ['#1a237e','#1565c0','#0277bd','#00695c','#2e7d32','#f9a825'];
                foreach ($byProyek as $i => $p):
                    $color = $colors[$i % count($colors)];
                ?>
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:<?= $color ?>"></div>
                        <span class="text-xs text-slate-600 truncate" style="max-width:120px">
                            <?= sanitize($p['nama_proyek']) ?>
                        </span>
                    </div>
                    <span class="text-xs font-semibold text-slate-700 flex-shrink-0">
                        <?= number_format($p['total']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabel Stok + Riwayat (tetap sama seperti sebelumnya) -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
            <i class="bi bi-box-seam text-amber-600"></i>
            Stok Komponen
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200">
                        <th class="table-header">Komponen</th>
                        <th class="table-header text-right">Stok</th>
                        <th class="table-header text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php 
                    $komps = $db->query("SELECT * FROM komponen ORDER BY stok_tersedia ASC LIMIT 10")->fetchAll();
                    foreach ($komps as $k): 
                        [$cls, $label] = stokStatus($k['stok_tersedia'], $k['stok_minimum']);
                    ?>
                    <tr>
                        <td class="table-cell">
                            <div class="font-medium text-slate-800 text-sm"><?= sanitize($k['nama_komponen']) ?></div>
                            <div class="text-xs text-slate-400"><?= sanitize($k['kategori']) ?></div>
                        </td>
                        <td class="table-cell text-right font-semibold text-sm">
                            <?= $k['stok_tersedia'] ?> <span class="font-normal text-xs text-slate-400"><?= sanitize($k['satuan']) ?></span>
                        </td>
                        <td class="table-cell text-center">
                            <span class="badge-modern inline-flex items-center rounded-full text-xs font-semibold <?= $cls ?>"><?= $label ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
            <i class="bi bi-clock-history text-blue-600"></i>
            Riwayat Penggunaan Terbaru
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200">
                        <th class="table-header">Komponen</th>
                        <th class="table-header">Proyek</th>
                        <th class="table-header text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php 
                    $history = $db->query("
                        SELECT pm.*, k.nama_komponen, k.satuan, p.nama_proyek 
                        FROM penggunaan_material pm
                        JOIN komponen k ON pm.komponen_id = k.id
                        JOIN proyek p ON pm.proyek_id = p.id
                        ORDER BY pm.created_at DESC LIMIT 10
                    ")->fetchAll();
                    foreach ($history as $h): ?>
                    <tr>
                        <td class="table-cell">
                            <div class="font-medium text-slate-800 text-sm"><?= sanitize($h['nama_komponen']) ?></div>
                            <div class="text-xs text-slate-400"><?= formatTanggalShort($h['tanggal_penggunaan']) ?></div>
                        </td>
                        <td class="table-cell text-xs text-slate-600">
                            <?= sanitize($h['nama_proyek']) ?>
                        </td>
                        <td class="table-cell text-right">
                            <span class="text-red-600 font-bold">-<?= $h['jumlah_digunakan'] ?></span>
                            <span class="text-xs text-slate-400"><?= sanitize($h['satuan']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Chart Stok Per Kategori
<?php
$stokKat = $db->query("
    SELECT kategori, SUM(stok_tersedia) as total
    FROM komponen GROUP BY kategori ORDER BY total DESC
")->fetchAll();
?>
const chartSKData = <?= json_encode([
    'labels' => array_column($stokKat, 'kategori'),
    'data'   => array_column($stokKat, 'total'),
]) ?>;

const ctxSK = document.getElementById('chartStokKategori');
if (ctxSK) {
    new Chart(ctxSK, {
        type: 'bar',
        data: {
            labels: chartSKData.labels,
            datasets: [{
                label: 'Total Stok',
                data: chartSKData.data,
                backgroundColor: [
                    'rgba(26,35,126,.8)','rgba(21,101,192,.8)','rgba(2,119,189,.8)',
                    'rgba(0,105,92,.8)','rgba(46,125,50,.8)','rgba(85,139,47,.8)',
                    'rgba(249,168,37,.8)','rgba(230,81,0,.8)',
                ],
                borderRadius: 6,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { font:{size:11} }, grid: { color:'rgba(0,0,0,.04)' } },
                y: { ticks: { font:{size:11} }, grid: { display:false } }
            }
        }
    });
}

<?php if (!empty($byProyek)): ?>
// Chart Donut Penggunaan Per Proyek
const chartProjData = <?= json_encode([
    'labels' => array_column($byProyek, 'nama_proyek'),
    'data'   => array_column($byProyek, 'total'),
]) ?>;

const ctxProj = document.getElementById('chartProyek');
if (ctxProj) {
    new Chart(ctxProj, {
        type: 'doughnut',
        data: {
            labels: chartProjData.labels,
            datasets: [{
                data: chartProjData.data,
                backgroundColor: ['#1a237e','#1565c0','#0277bd','#00695c','#2e7d32','#f9a825'],
                borderWidth: 0, hoverOffset: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '65%',
            plugins: { legend: { display: false } }
        }
    });
}
<?php endif; ?>
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
