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

// Sapaan berdasarkan jam
$jam    = (int) date('G');
$sapaan = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
?>

<!-- Greeting -->
<div class="mb-6">
    <h2 class="text-xl font-bold text-slate-900 tracking-tight"><?= $sapaan ?>, <?= sanitize(explode(' ', $user['nama'] ?? 'Pengguna')[0]) ?> 👋</h2>
    <p class="text-sm text-slate-500 mt-1">Berikut ringkasan sistem PLTS hari ini<?= $laporanHariIni > 0 ? " · {$laporanHariIni} laporan masuk hari ini" : '' ?>.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php
    $stats = [
        ['label'=>'Total Proyek',   'value'=>$totalProyek,   'icon'=>'bi-diagram-3',            'accent'=>'#4f46e5', 'light'=>'bg-indigo-50 text-indigo-600'],
        ['label'=>'Proyek Aktif',   'value'=>$proyekAktif,   'icon'=>'bi-activity',             'accent'=>'#0ea5e9', 'light'=>'bg-sky-50 text-sky-600'],
        ['label'=>'Proyek Selesai', 'value'=>$proyekSelesai, 'icon'=>'bi-check-circle',         'accent'=>'#10b981', 'light'=>'bg-emerald-50 text-emerald-600'],
        ['label'=>'Stok Kritis',    'value'=>$stokKritis,    'icon'=>'bi-exclamation-triangle', 'accent'=>($stokKritis>0?'#ef4444':'#64748b'), 'light'=>($stokKritis>0?'bg-red-50 text-red-600':'bg-slate-100 text-slate-500')],
    ];
    foreach ($stats as $s): ?>
    <div class="card card-hover stat-card p-5" style="--stat-accent: <?= $s['accent'] ?>">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-slate-500 mb-1"><?= $s['label'] ?></p>
                <p class="text-[28px] leading-none font-extrabold text-slate-900 tracking-tight"><?= $s['value'] ?></p>
            </div>
            <div class="w-11 h-11 <?= $s['light'] ?> rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="bi <?= $s['icon'] ?> text-lg"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts + Proyek Terbaru -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <!-- Chart -->
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-slate-800 tracking-tight">Status Proyek</h3>
            <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <i class="bi bi-pie-chart"></i>
            </span>
        </div>
        <div class="flex items-center justify-center h-44">
            <canvas id="chartStatus"></canvas>
        </div>
    </div>

    <!-- Proyek Terbaru -->
    <div class="card lg:col-span-2 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-800 tracking-tight">Proyek Terbaru</h3>
            <?php if (isAdminOrManajer()): ?>
            <a href="<?= BASE_URL ?>/pages/proyek/index.php" class="text-xs text-primary-600 hover:text-primary-700 font-semibold inline-flex items-center gap-1">
                Lihat Semua <i class="bi bi-arrow-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if (empty($proyekTerbaru)): ?>
            <div class="px-5 py-10 text-center">
                <span class="empty-icon mb-3"><i class="bi bi-diagram-3"></i></span>
                <p class="text-slate-400 text-sm">Belum ada proyek</p>
            </div>
            <?php else: ?>
            <?php foreach ($proyekTerbaru as $p): ?>
            <div class="px-5 py-3.5 flex items-center gap-3 hover:bg-slate-50/70 transition-colors">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-50 to-indigo-100 text-primary-700 flex items-center justify-center flex-shrink-0 text-xs font-bold">
                    <?= strtoupper(substr($p['nama_proyek'], 0, 2)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $p['id'] ?>"
                        class="text-sm font-semibold text-slate-800 hover:text-primary-600 truncate block transition-colors">
                        <?= sanitize($p['nama_proyek']) ?>
                    </a>
                    <p class="text-xs text-slate-400 mt-0.5"><?= sanitize($p['nama_pelanggan']) ?></p>
                </div>
                <?= statusBadge($p['status']) ?>
                <div class="flex items-center gap-2 w-28 flex-shrink-0">
                    <div class="flex-1 h-1.5 progress-track rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-primary-500 to-primary-600 rounded-full progress-fill" style="width:<?= $p['progres'] ?>%"></div>
                    </div>
                    <span class="text-xs font-semibold text-slate-500 w-8 text-right"><?= $p['progres'] ?>%</span>
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
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-800 tracking-tight">Laporan Lapangan Terbaru</h3>
            <a href="<?= BASE_URL ?>/pages/laporan/index.php" class="text-xs text-primary-600 hover:text-primary-700 font-semibold inline-flex items-center gap-1">
                Lihat Semua <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if (empty($laporanTerbaru)): ?>
            <div class="px-5 py-10 text-center">
                <span class="empty-icon mb-3"><i class="bi bi-file-earmark-text"></i></span>
                <p class="text-slate-400 text-sm">Belum ada laporan</p>
            </div>
            <?php else: ?>
            <?php foreach ($laporanTerbaru as $l): ?>
            <div class="px-5 py-3.5 hover:bg-slate-50/70 transition-colors">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">
                            <?= sanitize($l['judul_laporan']) ?>
                        </p>
                        <p class="text-xs text-slate-400 mt-1 flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center gap-1"><i class="bi bi-person"></i><?= sanitize($l['nama_teknisi']) ?></span>
                            <span class="text-slate-300">·</span>
                            <span class="inline-flex items-center gap-1"><i class="bi bi-folder"></i><?= sanitize($l['nama_proyek']) ?></span>
                        </p>
                        <p class="text-[11px] text-slate-400 mt-1 inline-flex items-center gap-1 bg-slate-100 rounded-full px-2 py-0.5">
                            <i class="bi bi-clock"></i> <?= timeAgo($l['created_at']) ?>
                        </p>
                    </div>
                    <?= statusBadge($l['status_pengerjaan']) ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stok Kritis -->
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-red-500"></i>
                Komponen Stok Kritis
            </h3>
            <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="text-xs text-primary-600 hover:text-primary-700 font-semibold inline-flex items-center gap-1">
                Kelola <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <?php if (empty($komponenKritis)): ?>
        <div class="px-5 py-10 text-center">
            <span class="empty-icon mb-3 !bg-emerald-50 !text-emerald-500"><i class="bi bi-check-circle"></i></span>
            <p class="text-sm text-slate-400">Semua stok komponen aman</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-100">
            <?php foreach ($komponenKritis as $k):
                [$cls, $label] = stokStatus($k['stok_tersedia'], $k['stok_minimum']);
            ?>
            <div class="px-5 py-3.5 flex items-center justify-between hover:bg-slate-50/70 transition-colors">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-red-50 text-red-500 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate"><?= sanitize($k['nama_komponen']) ?></p>
                        <p class="text-xs text-slate-400"><?= sanitize($k['kategori']) ?></p>
                    </div>
                </div>
                <div class="text-right flex-shrink-0 ml-3">
                    <span class="badge-modern inline-flex items-center rounded-full text-xs font-semibold <?= $cls ?>">
                        <?= $k['stok_tersedia'] ?> <?= sanitize($k['satuan']) ?>
                    </span>
                    <p class="text-[11px] text-slate-400 mt-1">Min: <?= $k['stok_minimum'] ?></p>
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
            backgroundColor: ['#94a3b8','#38bdf8','#6366f1','#10b981','#f59e0b'],
            borderWidth: 2,
            borderColor: '#ffffff',
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle', font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" }, padding: 12, color: '#64748b' }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
