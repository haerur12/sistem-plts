<?php
// pages/laporan/stok.php
$pageTitle   = 'Laporan Stok & Penggunaan';
$currentPage = 'laporan-stok';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'gudang', 'manajer_proyek']);

$db = db();
$summary = $db->query("
    SELECT
        COUNT(*) AS total_komponen,
        COALESCE(SUM(stok_tersedia), 0) AS total_stok,
        COALESCE(SUM(CASE WHEN stok_tersedia > stok_minimum * 1.5 THEN 1 ELSE 0 END), 0) AS aman,
        COALESCE(SUM(CASE WHEN stok_tersedia > stok_minimum AND stok_tersedia <= stok_minimum * 1.5 THEN 1 ELSE 0 END), 0) AS rendah,
        COALESCE(SUM(CASE WHEN stok_tersedia > 0 AND stok_tersedia <= stok_minimum THEN 1 ELSE 0 END), 0) AS kritis,
        COALESCE(SUM(CASE WHEN stok_tersedia = 0 THEN 1 ELSE 0 END), 0) AS habis
    FROM komponen
")->fetch();

$stokKategori = $db->query("
    SELECT kategori, SUM(stok_tersedia) AS total_stok, COUNT(*) AS jumlah_item,
           SUM(CASE WHEN stok_tersedia <= stok_minimum THEN 1 ELSE 0 END) AS kritis
    FROM komponen
    GROUP BY kategori
    ORDER BY total_stok DESC
")->fetchAll();

$byProyek = $db->query("
    SELECT p.nama_proyek, SUM(pm.jumlah_digunakan) AS total
    FROM penggunaan_material pm
    JOIN proyek p ON pm.proyek_id = p.id
    WHERE YEAR(pm.created_at) = YEAR(CURDATE())
      AND MONTH(pm.created_at) = MONTH(CURDATE())
    GROUP BY pm.proyek_id, p.nama_proyek
    ORDER BY total DESC
    LIMIT 6
")->fetchAll();

$topKomponen = $db->query("
    SELECT k.nama_komponen, k.kategori, k.satuan,
           SUM(pm.jumlah_digunakan) AS total_digunakan
    FROM penggunaan_material pm
    JOIN komponen k ON pm.komponen_id = k.id
    WHERE YEAR(pm.created_at) = YEAR(CURDATE())
      AND MONTH(pm.created_at) = MONTH(CURDATE())
    GROUP BY pm.komponen_id, k.nama_komponen, k.kategori, k.satuan
    ORDER BY total_digunakan DESC
    LIMIT 5
")->fetchAll();

$komponenKritis = $db->query("
    SELECT id, nama_komponen, kategori, stok_tersedia, stok_minimum, satuan
    FROM komponen
    WHERE stok_tersedia <= stok_minimum
    ORDER BY stok_tersedia ASC, nama_komponen
    LIMIT 10
")->fetchAll();

$riwayat = $db->query("
    SELECT pm.*, k.nama_komponen, k.satuan, p.nama_proyek, u.nama AS nama_user
    FROM penggunaan_material pm
    JOIN komponen k ON pm.komponen_id = k.id
    JOIN proyek p ON pm.proyek_id = p.id
    JOIN users u ON pm.user_id = u.id
    ORDER BY pm.created_at DESC
    LIMIT 10
")->fetchAll();

$penggunaanBulanIni = (int)$db->query("
    SELECT COALESCE(SUM(jumlah_digunakan), 0)
    FROM penggunaan_material
    WHERE YEAR(created_at) = YEAR(CURDATE())
      AND MONTH(created_at) = MONTH(CURDATE())
")->fetchColumn();
$maxPakai = max(array_map(static fn(array $item): int => (int)$item['total_digunakan'], $topKomponen) ?: [0]);
$jumlahKomponenKritis = (int)$summary['kritis'] + (int)$summary['habis'];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">Laporan Stok & Penggunaan</h2>
        <p class="text-sm text-slate-400 mt-0.5">Ringkasan kondisi stok dan pemakaian material bulan ini</p>
    </div>
    <div class="flex gap-2">
        <?php if (isAdminOrGudang()): ?>
        <a href="<?= BASE_URL ?>/api/export/material-csv.php" class="btn-secondary btn-sm">
            <i class="bi bi-download"></i> Export CSV
        </a>
        <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="btn-primary btn-sm">
            <i class="bi bi-box-seam"></i> Kelola Komponen
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-5">
    <?php
    $stats = [
        ['val' => number_format((int)$summary['total_komponen']), 'label' => 'Total Komponen', 'icon' => 'bi-grid-3x3-gap', 'color' => 'bg-indigo-50 text-indigo-600'],
        ['val' => number_format((int)$summary['total_stok']), 'label' => 'Total Unit Stok', 'icon' => 'bi-boxes', 'color' => 'bg-sky-50 text-sky-600'],
        ['val' => number_format($jumlahKomponenKritis), 'label' => 'Komponen Kritis', 'icon' => 'bi-exclamation-triangle', 'color' => $jumlahKomponenKritis ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600'],
        ['val' => number_format($penggunaanBulanIni), 'label' => 'Unit Terpakai Bulan Ini', 'icon' => 'bi-arrow-left-right', 'color' => 'bg-amber-50 text-amber-600'],
    ];
    foreach ($stats as $stat): ?>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl <?= $stat['color'] ?> flex items-center justify-center flex-shrink-0">
            <i class="bi <?= $stat['icon'] ?> text-lg"></i>
        </div>
        <div class="min-w-0">
            <div class="text-xl font-bold text-slate-800"><?= $stat['val'] ?></div>
            <div class="text-xs text-slate-500"><?= $stat['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-5">
    <?php
    $stockStates = [
        ['value' => $summary['aman'], 'label' => 'Aman', 'colors' => 'bg-green-50 border-green-200 text-green-700', 'dot' => 'bg-green-500'],
        ['value' => $summary['rendah'], 'label' => 'Rendah', 'colors' => 'bg-amber-50 border-amber-200 text-amber-700', 'dot' => 'bg-amber-500'],
        ['value' => $summary['kritis'], 'label' => 'Kritis', 'colors' => 'bg-orange-50 border-orange-200 text-orange-700', 'dot' => 'bg-orange-500'],
        ['value' => $summary['habis'], 'label' => 'Habis', 'colors' => 'bg-red-50 border-red-200 text-red-700', 'dot' => 'bg-red-500'],
    ];
    foreach ($stockStates as $state): ?>
    <div class="border rounded-xl p-3 <?= $state['colors'] ?>">
        <div class="flex items-center gap-2 mb-1">
            <span class="w-2 h-2 rounded-full <?= $state['dot'] ?>"></span>
            <span class="text-xs font-medium"><?= $state['label'] ?></span>
        </div>
        <div class="text-2xl font-bold"><?= (int)$state['value'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
            <i class="bi bi-bar-chart-horizontal text-primary-600"></i> Stok Tersedia Per Kategori
        </h3>
        <?php if ($stokKategori): ?>
        <div style="height:220px"><canvas id="chartStokKategori"></canvas></div>
        <?php else: ?>
        <p class="text-sm text-slate-400 py-16 text-center">Belum ada data komponen.</p>
        <?php endif; ?>
    </div>

    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
            <i class="bi bi-pie-chart text-purple-600"></i> Penggunaan Per Proyek (Bulan Ini)
        </h3>
        <?php if ($byProyek): ?>
        <div class="flex flex-wrap items-center gap-4">
            <div style="height:190px;width:190px;flex-shrink:0"><canvas id="chartProyek"></canvas></div>
            <div class="flex-1 min-w-[160px] space-y-2">
                <?php foreach ($byProyek as $i => $proyek): ?>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs text-slate-600 truncate"><?= sanitize($proyek['nama_proyek']) ?></span>
                    <span class="text-xs font-semibold text-slate-700"><?= number_format((int)$proyek['total']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <p class="text-sm text-slate-400 py-16 text-center">Belum ada penggunaan material bulan ini.</p>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
            <i class="bi bi-trophy text-amber-600"></i> Top 5 Komponen Terpakai Bulan Ini
        </h3>
        <?php if ($topKomponen): ?>
        <div class="space-y-4">
            <?php foreach ($topKomponen as $item):
                $percent = $maxPakai > 0 ? (int)round(((int)$item['total_digunakan'] / $maxPakai) * 100) : 0;
            ?>
            <div>
                <div class="flex items-center justify-between gap-3 mb-1">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-slate-700 truncate"><?= sanitize($item['nama_komponen']) ?></div>
                        <div class="text-xs text-slate-400"><?= sanitize($item['kategori']) ?></div>
                    </div>
                    <span class="text-sm font-semibold text-slate-700 whitespace-nowrap">
                        <?= number_format((int)$item['total_digunakan']) ?> <?= sanitize($item['satuan']) ?>
                    </span>
                </div>
                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full bg-primary-600" style="width:<?= $percent ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-sm text-slate-400 py-10 text-center">Belum ada data penggunaan bulan ini.</p>
        <?php endif; ?>
    </div>

    <div class="card p-5">
        <div class="flex items-center justify-between gap-2 mb-4">
            <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                <i class="bi bi-exclamation-triangle text-red-500"></i> Komponen Perlu Perhatian
            </h3>
            <?php if (isAdminOrGudang()): ?>
            <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="text-xs text-primary-600 font-semibold">Kelola</a>
            <?php endif; ?>
        </div>
        <?php if ($komponenKritis): ?>
        <div class="space-y-2">
            <?php foreach ($komponenKritis as $item):
                [, $label] = stokStatus((int)$item['stok_tersedia'], (int)$item['stok_minimum']);
            ?>
            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 px-3 py-2">
                <div class="min-w-0">
                    <div class="text-sm font-medium text-slate-700 truncate"><?= sanitize($item['nama_komponen']) ?></div>
                    <div class="text-xs text-slate-400"><?= sanitize($item['kategori']) ?> · <?= $label ?></div>
                </div>
                <div class="text-right whitespace-nowrap">
                    <div class="text-sm font-semibold text-slate-700"><?= (int)$item['stok_tersedia'] ?> <?= sanitize($item['satuan']) ?></div>
                    <div class="text-xs text-slate-400">Min: <?= (int)$item['stok_minimum'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-10">
            <i class="bi bi-check-circle text-3xl text-emerald-500"></i>
            <p class="text-sm text-slate-500 mt-2">Semua stok aman.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card p-5">
    <div class="flex items-center justify-between gap-2 mb-3">
        <h3 class="text-sm font-semibold text-slate-700">Riwayat Penggunaan Terbaru</h3>
        <a href="<?= BASE_URL ?>/pages/material/index.php" class="text-xs text-primary-600 font-semibold">Lihat Semua</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 border-y border-slate-200">
                    <th class="table-header">Tanggal</th>
                    <th class="table-header">Komponen</th>
                    <th class="table-header">Proyek</th>
                    <th class="table-header text-right">Jumlah</th>
                    <th class="table-header">Oleh</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$riwayat): ?>
                <tr><td colspan="5" class="table-cell text-center text-slate-400">Belum ada riwayat penggunaan.</td></tr>
                <?php else: foreach ($riwayat as $item): ?>
                <tr>
                    <td class="table-cell text-xs text-slate-500"><?= formatTanggalShort($item['tanggal_penggunaan']) ?></td>
                    <td class="table-cell">
                        <div class="text-sm font-medium text-slate-700"><?= sanitize($item['nama_komponen']) ?></div>
                        <div class="text-xs text-slate-400"><?= sanitize($item['kategori']) ?></div>
                    </td>
                    <td class="table-cell text-sm text-slate-600"><?= sanitize($item['nama_proyek']) ?></td>
                    <td class="table-cell text-right text-sm font-semibold text-red-600">
                        -<?= (int)$item['jumlah_digunakan'] ?> <?= sanitize($item['satuan']) ?>
                    </td>
                    <td class="table-cell text-sm text-slate-500"><?= sanitize($item['nama_user']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const stokKategoriData = <?= json_encode([
    'labels' => array_column($stokKategori, 'kategori'),
    'data' => array_map('intval', array_column($stokKategori, 'total_stok')),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const stokKategoriCanvas = document.getElementById('chartStokKategori');
if (stokKategoriCanvas) {
    new Chart(stokKategoriCanvas, {
        type: 'bar',
        data: {
            labels: stokKategoriData.labels,
            datasets: [{
                label: 'Total Stok',
                data: stokKategoriData.data,
                backgroundColor: ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#64748b'],
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true }, y: { grid: { display: false } } }
        }
    });
}

const proyekData = <?= json_encode([
    'labels' => array_column($byProyek, 'nama_proyek'),
    'data' => array_map('intval', array_column($byProyek, 'total')),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const proyekCanvas = document.getElementById('chartProyek');
if (proyekCanvas) {
    new Chart(proyekCanvas, {
        type: 'doughnut',
        data: {
            labels: proyekData.labels,
            datasets: [{
                data: proyekData.data,
                backgroundColor: ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%' }
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
