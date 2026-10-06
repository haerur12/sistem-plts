<?php
// pages/dashboard/index.php
$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$db = db();
$user = currentUser();

// Statistik
$totalProyek    = $db->query("SELECT COUNT(*) FROM proyek")->fetchColumn();
$proyekAktif    = $db->query("SELECT COUNT(*) FROM proyek WHERE status IN ('survei','perencanaan','instalasi')")->fetchColumn();
$proyekSelesai  = $db->query("SELECT COUNT(*) FROM proyek WHERE status = 'selesai'")->fetchColumn();
$stokKritis     = $db->query("SELECT COUNT(*) FROM komponen WHERE stok_tersedia <= stok_minimum")->fetchColumn();
$laporanHariIni = $db->query("SELECT COUNT(*) FROM laporan_lapangan WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// Proyek terbaru
$proyekTerbaru = $db->query("
    SELECT p.*, pl.nama_pelanggan
    FROM proyek p
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    ORDER BY p.created_at DESC LIMIT 6
")->fetchAll();

// Laporan terbaru
$laporanTerbaru = $db->query("
    SELECT l.*, p.nama_proyek, u.nama as nama_teknisi
    FROM laporan_lapangan l
    JOIN proyek p ON l.proyek_id = p.id
    JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC LIMIT 5
")->fetchAll();

// Komponen kritis
$komponenKritis = $db->query("
    SELECT * FROM komponen
    WHERE stok_tersedia <= stok_minimum
    ORDER BY stok_tersedia ASC LIMIT 5
")->fetchAll();

// Chart data
$chartStatus = $db->query("
    SELECT status, COUNT(*) as total
    FROM proyek GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Stats Grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php
    $stats = [
        ['label'=>'Total Proyek',    'value'=>$totalProyek,    'icon'=>'bi-diagram-3',           'color'=>'bg-primary-900',  'light'=>'bg-primary-50 text-primary-700'],
        ['label'=>'Proyek Aktif',    'value'=>$proyekAktif,    'icon'=>'bi-activity',             'color'=>'bg-blue-600',     'light'=>'bg-blue-50 text-blue-700'],
        ['label'=>'Proyek Selesai',  'value'=>$proyekSelesai,  'icon'=>'bi-check-circle',         'color'=>'bg-green-600',    'light'=>'bg-green-50 text-green-700'],
        ['label'=>'Stok Kritis',     'value'=>$stokKritis,     'icon'=>'bi-exclamation-triangle', 'color'=>($stokKritis>0?'bg-red-600':'bg-slate-500'), 'light'=>'bg-red-50 text-red-700'],
    ];
    foreach ($stats as $s): ?>
    <div class="card p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-slate-500 mb-1"><?= $s['label'] ?></p>
                <p class="text-3xl font-bold text-slate-800"><?= $s['value'] ?></p>
            </div>
            <div class="w-10 h-10 <?= $s['color'] ?> rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="bi <?= $s['icon'] ?> text-white"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts + Proyek Terbaru -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <!-- Chart -->
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-700 mb-4">Status Proyek</h3>
        <div class="flex items-center justify-center h-44">
            <canvas id="chartStatus"></canvas>
        </div>
    </div>

    <!-- Proyek Terbaru -->
    <div class="card lg:col-span-2">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700">Proyek Terbaru</h3>
            <?php if (isAdminOrManajer()): ?>
            <a href="<?= BASE_URL ?>/pages/proyek/index.php" class="text-xs text-primary-700 hover:text-primary-900 font-medium">
                Lihat Semua <i class="bi bi-arrow-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <div class="divide-y divide-slate-50">
            <?php if (empty($proyekTerbaru)): ?>
            <div class="px-5 py-8 text-center text-slate-400 text-sm">Belum ada proyek</div>
            <?php else: ?>
            <?php foreach ($proyekTerbaru as $p): ?>
            <div class="px-5 py-3 flex items-center gap-3">
                <div class="flex-1 min-w-0">
                    <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $p['id'] ?>"
                        class="text-sm font-medium text-slate-800 hover:text-primary-700 truncate block">
                        <?= sanitize($p['nama_proyek']) ?>
                    </a>
                    <p class="text-xs text-slate-400 mt-0.5"><?= sanitize($p['nama_pelanggan']) ?></p>
                </div>
                <?= statusBadge($p['status']) ?>
                <div class="flex items-center gap-1.5 w-24 flex-shrink-0">
                    <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-primary-700 rounded-full" style="width:<?= $p['progres'] ?>%"></div>
                    </div>
                    <span class="text-xs text-slate-500"><?= $p['progres'] ?>%</span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Laporan Terbaru + Stok Kritis -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <!-- Laporan Terbaru -->
    <div class="card">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700">Laporan Lapangan Terbaru</h3>
            <a href="<?= BASE_URL ?>/pages/laporan/index.php" class="text-xs text-primary-700 hover:text-primary-900 font-medium">
                Lihat Semua <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="divide-y divide-slate-50">
            <?php if (empty($laporanTerbaru)): ?>
            <div class="px-5 py-8 text-center text-slate-400 text-sm">Belum ada laporan</div>
            <?php else: ?>
            <?php foreach ($laporanTerbaru as $l): ?>
            <div class="px-5 py-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">
                            <?= sanitize($l['judul_laporan']) ?>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <?= sanitize($l['nama_teknisi']) ?> · <?= sanitize($l['nama_proyek']) ?>
                        </p>
                        <p class="text-xs text-slate-400"><?= timeAgo($l['created_at']) ?></p>
                    </div>
                    <?= statusBadge($l['status_pengerjaan']) ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stok Kritis -->
    <div class="card">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                <i class="bi bi-exclamation-triangle text-red-500"></i>
                Komponen Stok Kritis
            </h3>
            <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="text-xs text-primary-700 hover:text-primary-900 font-medium">
                Kelola <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <?php if (empty($komponenKritis)): ?>
        <div class="px-5 py-8 text-center">
            <i class="bi bi-check-circle text-green-400 text-3xl mb-2 block"></i>
            <p class="text-sm text-slate-400">Semua stok komponen aman</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-50">
            <?php foreach ($komponenKritis as $k):
                [$cls, $label] = stokStatus($k['stok_tersedia'], $k['stok_minimum']);
            ?>
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-800"><?= sanitize($k['nama_komponen']) ?></p>
                    <p class="text-xs text-slate-400"><?= sanitize($k['kategori']) ?></p>
                </div>
                <div class="text-right flex-shrink-0">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $cls ?>">
                        <?= $k['stok_tersedia'] ?> <?= sanitize($k['satuan']) ?>
                    </span>
                    <p class="text-xs text-slate-400 mt-0.5">Min: <?= $k['stok_minimum'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Chart donut status proyek
const chartData = <?= json_encode([
    'labels' => ['Survei','Perencanaan','Instalasi','Selesai','Ditunda'],
    'data'   => [
        (int)($chartStatus['survei']      ?? 0),
        (int)($chartStatus['perencanaan'] ?? 0),
        (int)($chartStatus['instalasi']   ?? 0),
        (int)($chartStatus['selesai']     ?? 0),
        (int)($chartStatus['ditunda']     ?? 0),
    ],
]) ?>;

new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: chartData.labels,
        datasets: [{
            data: chartData.data,
            backgroundColor: ['#94a3b8','#60a5fa','#3b82f6','#22c55e','#f59e0b'],
            borderWidth: 0,
            hoverOffset: 4,
        }]
    },
    options: {
        responsive: true,
        cutout: '68%',
        plugins: {
            legend: { position:'bottom', labels:{ boxWidth:10, font:{ size:11 }, padding:12 } }
        }
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>