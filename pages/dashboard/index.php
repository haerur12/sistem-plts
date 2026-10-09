<?php
// pages/dashboard/index.php — UPGRADED
$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';
requireLogin();
updateLastSeen();

$db   = db();
$user = currentUser();
$isAdmin = isAdmin();

if (isGudang()) {

    $db   = db();
    $user = currentUser();

    // Statistik utama
    $totalKomponen  = (int)$db->query("SELECT COUNT(*) FROM komponen")->fetchColumn();
    $stokAman       = (int)$db->query("SELECT COUNT(*) FROM komponen WHERE stok_tersedia > stok_minimum * 1.5")->fetchColumn();
    $stokRendah     = (int)$db->query("SELECT COUNT(*) FROM komponen WHERE stok_tersedia > stok_minimum AND stok_tersedia <= stok_minimum * 1.5")->fetchColumn();
    $stokKritis     = (int)$db->query("SELECT COUNT(*) FROM komponen WHERE stok_tersedia > 0 AND stok_tersedia <= stok_minimum")->fetchColumn();
    $stokHabis      = (int)$db->query("SELECT COUNT(*) FROM komponen WHERE stok_tersedia = 0")->fetchColumn();

    // Penggunaan hari ini
    $penggunaanHariIni = (int)$db->query("SELECT COUNT(*) FROM penggunaan_material WHERE DATE(created_at) = CURDATE()")->fetchColumn();

    // Penggunaan bulan ini
    $penggunaanBulanIni = (int)$db->query("SELECT COUNT(*) FROM penggunaan_material WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();

    // Komponen kritis & habis
    $komponenKritis = $db->query("
        SELECT * FROM komponen
        WHERE stok_tersedia <= stok_minimum
        ORDER BY stok_tersedia ASC
    ")->fetchAll();

    // Top 5 komponen paling sering dipakai bulan ini
    $topKomponen = $db->query("
        SELECT k.nama_komponen, k.satuan, k.kategori,
               SUM(pm.jumlah_digunakan) as total_digunakan
        FROM penggunaan_material pm
        JOIN komponen k ON pm.komponen_id = k.id
        WHERE MONTH(pm.created_at) = MONTH(CURDATE())
        GROUP BY pm.komponen_id, k.nama_komponen, k.satuan, k.kategori
        ORDER BY total_digunakan DESC
        LIMIT 5
    ")->fetchAll();

    // Penggunaan terbaru
    $penggunaanTerbaru = $db->query("
        SELECT pm.*, k.nama_komponen, k.satuan, p.nama_proyek, u.nama as nama_user
        FROM penggunaan_material pm
        JOIN komponen k ON pm.komponen_id = k.id
        JOIN proyek p ON pm.proyek_id = p.id
        JOIN users u ON pm.user_id = u.id
        ORDER BY pm.created_at DESC
        LIMIT 5
    ")->fetchAll();

    // Chart stok per kategori
    $chartKategori = $db->query("
        SELECT kategori,
               SUM(stok_tersedia) as total_stok,
               COUNT(*) as jumlah_item
        FROM komponen
        GROUP BY kategori
        ORDER BY total_stok DESC
    ")->fetchAll();

    // Chart penggunaan 7 hari terakhir
    $chartPenggunaan = $db->query("
        SELECT DATE_FORMAT(created_at,'%d/%m') as tgl,
               COUNT(*) as transaksi,
               SUM(jumlah_digunakan) as total_unit
        FROM penggunaan_material
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at), DATE_FORMAT(created_at,'%d/%m')
        ORDER BY DATE(created_at) ASC
    ")->fetchAll();

    // Notif belum dibaca
    $notifCount = countNotifBelumDibaca($user['id']);

    include __DIR__ . '/../../includes/header.php';
    include __DIR__ . '/../../includes/sidebar.php';
    ?>

<!-- HEADER -->
<div class="flex items-center justify-between mb-6">
    <div>
        <?php
        $jam = (int)date('H');
        $greeting = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
        ?>
        <h2 class="text-xl font-bold text-slate-800"><?= $greeting ?>, <?= sanitize(explode(' ',$user['nama'])[0]) ?>! 🏭</h2>
        <p class="text-sm text-slate-400 mt-0.5">
            <?= date('l, d F Y') ?>
            <?php if ($stokKritis + $stokHabis > 0): ?>
            · <span class="text-red-500 font-semibold animate-pulse">⚠️ <?= $stokKritis + $stokHabis ?> komponen perlu perhatian</span>
            <?php else: ?>
            · <span class="text-green-600 font-medium">✅ Semua stok dalam kondisi aman</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>/pages/material/create.php" class="btn-secondary btn-sm">
            <i class="bi bi-arrow-left-right"></i> Catat Penggunaan
        </a>
        <a href="<?= BASE_URL ?>/pages/komponen/create.php" class="btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Tambah Komponen
        </a>
    </div>
</div>

<!-- ALERT STOK HABIS -->
<?php if ($stokHabis > 0): ?>
<div class="bg-red-50 border border-red-200 rounded-xl px-5 py-4 mb-5 flex items-start gap-3">
    <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
        <i class="bi bi-exclamation-triangle-fill text-red-500 text-lg"></i>
    </div>
    <div>
        <h4 class="text-sm font-semibold text-red-800">
            <?= $stokHabis ?> Komponen Stok Habis!
        </h4>
        <p class="text-xs text-red-600 mt-0.5">
            Segera lakukan pengisian stok untuk komponen yang sudah habis agar tidak mengganggu jadwal instalasi.
        </p>
    </div>
    <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="ml-auto btn-danger btn-sm flex-shrink-0">
        Kelola Stok
    </a>
</div>
<?php endif; ?>

<!-- STAT CARDS -->
<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
    <?php
    $cards = [
        ['val'=>$totalKomponen,  'label'=>'Total Komponen', 'icon'=>'bi-grid-3x3-gap',           'bg'=>'bg-slate-700',   'border'=>'border-slate-200'],
        ['val'=>$stokAman,       'label'=>'Stok Aman',      'icon'=>'bi-check-circle-fill',       'bg'=>'bg-green-500',   'border'=>'border-green-200'],
        ['val'=>$stokRendah,     'label'=>'Stok Rendah',    'icon'=>'bi-dash-circle-fill',        'bg'=>'bg-amber-500',   'border'=>'border-amber-200'],
        ['val'=>$stokKritis,     'label'=>'Stok Kritis',    'icon'=>'bi-exclamation-circle-fill', 'bg'=>'bg-orange-500',  'border'=>'border-orange-200'],
        ['val'=>$stokHabis,      'label'=>'Stok Habis',     'icon'=>'bi-x-circle-fill',           'bg'=>'bg-red-500',     'border'=>'border-red-200'],
    ];
    foreach ($cards as $c): ?>
    <div class="card p-4 border <?= $c['border'] ?> card-hover <?= ($c['val'] > 0 && in_array($c['label'],['Stok Kritis','Stok Habis'])) ? 'stok-kritis-pulse' : '' ?>">
        <div class="w-9 h-9 <?= $c['bg'] ?> rounded-xl flex items-center justify-center mb-3 shadow-sm">
            <i class="bi <?= $c['icon'] ?> text-white"></i>
        </div>
        <div class="text-2xl font-bold text-slate-800"><?= $c['val'] ?></div>
        <div class="text-xs text-slate-500 mt-0.5"><?= $c['label'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- CHARTS -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

    <!-- Chart Penggunaan 7 Hari -->
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                <i class="bi bi-graph-up text-primary-600"></i>
                Penggunaan 7 Hari Terakhir
            </h3>
            <span class="text-xs text-slate-400"><?= $penggunaanBulanIni ?> transaksi bulan ini</span>
        </div>
        <div style="height:180px">
            <canvas id="chartPenggunaan"></canvas>
        </div>
    </div>

    <!-- Chart Stok Per Kategori -->
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                <i class="bi bi-pie-chart text-purple-600"></i>
                Distribusi Stok Per Kategori
            </h3>
        </div>
        <div class="flex items-center gap-4">
            <div style="height:160px;width:160px;flex-shrink:0">
                <canvas id="chartKategori"></canvas>
            </div>
            <div class="flex-1 space-y-2">
                <?php
                $colors = ['#1a237e','#1565c0','#0277bd','#00695c','#2e7d32','#558b2f','#f9a825','#e65100','#6a1b9a'];
                foreach ($chartKategori as $i => $k):
                    $color = $colors[$i % count($colors)];
                ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:<?= $color ?>"></div>
                        <span class="text-xs text-slate-600 truncate"><?= sanitize($k['kategori']) ?></span>
                    </div>
                    <span class="text-xs font-semibold text-slate-700"><?= $k['total_stok'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- KOMPONEN KRITIS + TOP PENGGUNAAN + TERBARU -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    <!-- Komponen Kritis -->
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                <i class="bi bi-exclamation-triangle text-red-500"></i>
                Perlu Perhatian
            </h3>
            <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="text-xs text-primary-700 font-medium hover:text-primary-900">
                Kelola <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <?php if (empty($komponenKritis)): ?>
        <div class="px-5 py-10 text-center">
            <i class="bi bi-check-circle text-4xl text-green-300 block mb-2"></i>
            <p class="text-sm text-slate-400">Semua stok aman!</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-50">
            <?php foreach ($komponenKritis as $k):
                [$cls, $label, $statusKey] = stokStatus($k['stok_tersedia'], $k['stok_minimum']);
                $pct = $k['stok_minimum'] > 0 ? min(100, round($k['stok_tersedia'] / $k['stok_minimum'] * 100)) : 0;
                $barColor = $statusKey === 'habis' ? 'bg-slate-400' : ($statusKey === 'kritis' ? 'bg-red-500' : 'bg-amber-500');
            ?>
            <div class="px-5 py-3.5">
                <div class="flex items-start justify-between gap-2 mb-1.5">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-slate-800 leading-tight truncate">
                            <?= sanitize($k['nama_komponen']) ?>
                        </p>
                        <p class="text-xs text-slate-400"><?= sanitize($k['kategori']) ?></p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $cls ?> flex-shrink-0">
                        <?= $label ?>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full <?= $barColor ?> rounded-full" style="width:<?= $pct ?>%"></div>
                    </div>
                    <span class="text-xs font-semibold <?= $statusKey==='habis' ? 'text-slate-500' : 'text-red-600' ?>">
                        <?= $k['stok_tersedia'] ?> / <?= $k['stok_minimum'] ?>
                    </span>
                </div>
                <!-- Quick Update Stok -->
                <div class="mt-2">
                    <a href="<?= BASE_URL ?>/pages/komponen/edit.php?id=<?= $k['id'] ?>"
                        class="text-xs text-primary-600 hover:text-primary-800 font-medium flex items-center gap-1">
                        <i class="bi bi-plus-circle"></i> Update Stok
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Top Komponen Dipakai -->
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50">
            <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                <i class="bi bi-trophy text-amber-500"></i>
                Paling Banyak Dipakai
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Bulan <?= date('F Y') ?></p>
        </div>
        <?php if (empty($topKomponen)): ?>
        <div class="px-5 py-10 text-center">
            <i class="bi bi-inbox text-4xl text-slate-200 block mb-2"></i>
            <p class="text-sm text-slate-400">Belum ada penggunaan bulan ini</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-50">
            <?php
            $maxVal = max(array_column($topKomponen, 'total_digunakan'));
            foreach ($topKomponen as $i => $k):
                $pct = $maxVal > 0 ? round($k['total_digunakan'] / $maxVal * 100) : 0;
                $medals = ['🥇','🥈','🥉','4️⃣','5️⃣'];
            ?>
            <div class="px-5 py-3.5">
                <div class="flex items-start gap-3">
                    <span class="text-lg flex-shrink-0 mt-0.5"><?= $medals[$i] ?></span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <p class="text-xs font-semibold text-slate-800 truncate">
                                <?= sanitize($k['nama_komponen']) ?>
                            </p>
                            <span class="text-xs font-bold text-primary-700 flex-shrink-0">
                                <?= $k['total_digunakan'] ?> <?= sanitize($k['satuan']) ?>
                            </span>
                        </div>
                        <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-primary-600 rounded-full transition-all" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Penggunaan Terbaru -->
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                <i class="bi bi-clock-history text-blue-500"></i>
                Penggunaan Terbaru
            </h3>
            <a href="<?= BASE_URL ?>/pages/material/index.php" class="text-xs text-primary-700 font-medium hover:text-primary-900">
                Semua <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <?php if (empty($penggunaanTerbaru)): ?>
        <div class="px-5 py-10 text-center">
            <i class="bi bi-inbox text-4xl text-slate-200 block mb-2"></i>
            <p class="text-sm text-slate-400">Belum ada catatan penggunaan</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-50">
            <?php foreach ($penggunaanTerbaru as $pm): ?>
            <div class="px-5 py-3.5">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-slate-800 truncate">
                            <?= sanitize($pm['nama_komponen']) ?>
                        </p>
                        <p class="text-xs text-slate-400 truncate mt-0.5">
                            <?= sanitize($pm['nama_proyek']) ?>
                        </p>
                        <p class="text-xs text-slate-300 mt-0.5"><?= timeAgo($pm['created_at']) ?></p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <span class="text-sm font-bold text-red-500">
                            -<?= $pm['jumlah_digunakan'] ?>
                        </span>
                        <span class="text-xs text-slate-400"> <?= sanitize($pm['satuan']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Chart Penggunaan 7 Hari
const chartPData = <?= json_encode([
    'labels'     => array_column($chartPenggunaan, 'tgl'),
    'transaksi'  => array_column($chartPenggunaan, 'transaksi'),
    'total_unit' => array_column($chartPenggunaan, 'total_unit'),
]) ?>;

const ctxP = document.getElementById('chartPenggunaan');
if (ctxP) {
    new Chart(ctxP, {
        type: 'bar',
        data: {
            labels: chartPData.labels,
            datasets: [{
                label: 'Transaksi',
                data: chartPData.transaksi,
                backgroundColor: 'rgba(26,35,126,0.15)',
                borderColor: '#1a237e',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize:1, font:{size:11} }, grid: { color:'rgba(0,0,0,.04)' } },
                x: { ticks: { font:{size:11} }, grid: { display:false } }
            }
        }
    });
}

// Chart Donut Stok Per Kategori
const chartKData = <?= json_encode([
    'labels' => array_column($chartKategori, 'kategori'),
    'data'   => array_column($chartKategori, 'total_stok'),
]) ?>;
const colors = ['#1a237e','#1565c0','#0277bd','#00695c','#2e7d32','#558b2f','#f9a825','#e65100','#6a1b9a'];

const ctxK = document.getElementById('chartKategori');
if (ctxK) {
    new Chart(ctxK, {
        type: 'doughnut',
        data: {
            labels: chartKData.labels,
            datasets: [{
                data: chartKData.data,
                backgroundColor: colors.slice(0, chartKData.labels.length),
                borderWidth: 0,
                hoverOffset: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '65%',
            plugins: { legend: { display: false } }
        }
    });
}
</script>

    <?php
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

// ============ STATISTIK DASAR ============
$totalProyek    = (int) $db->query("SELECT COUNT(*) FROM proyek")->fetchColumn();
$proyekAktif    = (int) $db->query("SELECT COUNT(*) FROM proyek WHERE status IN ('survei','perencanaan','instalasi')")->fetchColumn();
$proyekSelesai  = (int) $db->query("SELECT COUNT(*) FROM proyek WHERE status = 'selesai'")->fetchColumn();
$stokKritis     = (int) $db->query("SELECT COUNT(*) FROM komponen WHERE stok_tersedia <= stok_minimum")->fetchColumn();
$laporanHariIni = (int) $db->query("SELECT COUNT(*) FROM laporan_lapangan WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// ============ KPI vs BULAN LALU ============
$currentMonth = date('Y-m');
$lastMonth    = date('Y-m', strtotime('-1 month'));

$proyekBulanIni  = (int) $db->prepare("SELECT COUNT(*) FROM proyek WHERE DATE_FORMAT(created_at, '%Y-%m') = ?")->execute([$currentMonth]) ? $db->query("SELECT COUNT(*) FROM proyek WHERE DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth'")->fetchColumn() : 0;
$proyekBulanLalu = (int) $db->query("SELECT COUNT(*) FROM proyek WHERE DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth'")->fetchColumn();

$laporanBulanIni  = (int) $db->query("SELECT COUNT(*) FROM laporan_lapangan WHERE DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth'")->fetchColumn();
$laporanBulanLalu = (int) $db->query("SELECT COUNT(*) FROM laporan_lapangan WHERE DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth'")->fetchColumn();

$materialBulanIni  = (int) $db->query("SELECT COALESCE(SUM(jumlah_digunakan),0) FROM penggunaan_material WHERE DATE_FORMAT(tanggal_penggunaan, '%Y-%m') = '$currentMonth'")->fetchColumn();
$materialBulanLalu = (int) $db->query("SELECT COALESCE(SUM(jumlah_digunakan),0) FROM penggunaan_material WHERE DATE_FORMAT(tanggal_penggunaan, '%Y-%m') = '$lastMonth'")->fetchColumn();

$proyekSelesaiBulanIni  = (int) $db->query("SELECT COUNT(*) FROM proyek WHERE status='selesai' AND DATE_FORMAT(tanggal_selesai, '%Y-%m') = '$currentMonth'")->fetchColumn();
$proyekSelesaiBulanLalu = (int) $db->query("SELECT COUNT(*) FROM proyek WHERE status='selesai' AND DATE_FORMAT(tanggal_selesai, '%Y-%m') = '$lastMonth'")->fetchColumn();

function hitungPersen($sekarang, $lalu) {
    if ($lalu == 0) return $sekarang > 0 ? 100 : 0;
    return round((($sekarang - $lalu) / $lalu) * 100);
}

// ============ CHART: TREN PROYEK 6 BULAN ============
$trenProyek = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $stmt = $db->prepare("SELECT COUNT(*) FROM proyek WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$bulan]);
    $trenProyek[] = ['label' => $label, 'total' => (int) $stmt->fetchColumn()];
}

// ============ HEATMAP: AKTIVITAS PER HARI (30 HARI) ============
$heatmap = [];
$stmt = $db->query("
    SELECT DATE(created_at) as tgl, COUNT(*) as total
    FROM activity_log
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
");
$aktivitasPerHari = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

for ($i = 29; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $heatmap[] = [
        'tgl'   => $tgl,
        'label' => date('d', strtotime($tgl)),
        'total' => (int)($aktivitasPerHari[$tgl] ?? 0),
        'hari'  => date('D', strtotime($tgl)),
    ];
}

// ============ ACTIVITY LOG TERKINI ============
$activityLog = $db->query("
    SELECT a.*, u.nama, u.role
    FROM activity_log a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 10
")->fetchAll();

// ============ PROYEK TERBARU ============
$proyekTerbaru = $db->query("
    SELECT p.*, pl.nama_pelanggan
    FROM proyek p
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    ORDER BY p.created_at DESC LIMIT 6
")->fetchAll();

// ============ LAPORAN TERBARU ============
$laporanTerbaru = $db->query("
    SELECT l.*, p.nama_proyek, u.nama as nama_teknisi
    FROM laporan_lapangan l
    JOIN proyek p ON l.proyek_id = p.id
    JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC LIMIT 5
")->fetchAll();

// ============ KOMPONEN KRITIS ============
$komponenKritis = $db->query("
    SELECT * FROM komponen
    WHERE stok_tersedia <= stok_minimum
    ORDER BY stok_tersedia ASC LIMIT 5
")->fetchAll();

// ============ CHART STATUS ============
$chartStatus = $db->query("SELECT status, COUNT(*) as total FROM proyek GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';

$jam    = (int) date('G');
$sapaan = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
?>

<div class="mb-6 flex items-center justify-between flex-wrap gap-3">
    <div>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight"><?= $sapaan ?>, <?= sanitize(explode(' ', $user['nama'] ?? 'Pengguna')[0]) ?> 👋</h2>
        <p class="text-sm text-slate-500 mt-1">Berikut ringkasan sistem PLTS hari ini · <?= $laporanHariIni ?> laporan masuk hari ini.</p>
    </div>
    <div class="flex gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-600 shadow-sm">
            <i class="bi bi-calendar3 text-primary-600"></i> <?= date('d M Y') ?>
        </span>
    </div>
</div>

<!-- ============ KPI CARDS dengan PERSEN NAIK/TURUN ============ -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
<?php
$kpi = [
    [
        'label'  => 'Proyek Baru',
        'value'  => $proyekBulanIni,
        'sub'    => "bulan ini",
        'icon'   => 'bi-diagram-3',
        'accent' => '#4f46e5',
        'light'  => 'bg-indigo-50 text-indigo-600',
        'persen' => hitungPersen($proyekBulanIni, $proyekBulanLalu),
    ],
    [
        'label'  => 'Laporan Lapangan',
        'value'  => $laporanBulanIni,
        'sub'    => "bulan ini",
        'icon'   => 'bi-file-earmark-text',
        'accent' => '#0ea5e9',
        'light'  => 'bg-sky-50 text-sky-600',
        'persen' => hitungPersen($laporanBulanIni, $laporanBulanLalu),
    ],
    [
        'label'  => 'Material Terpakai',
        'value'  => number_format($materialBulanIni, 0, ',', '.'),
        'sub'    => "unit bulan ini",
        'icon'   => 'bi-boxes',
        'accent' => '#f59e0b',
        'light'  => 'bg-amber-50 text-amber-600',
        'persen' => hitungPersen($materialBulanIni, $materialBulanLalu),
    ],
    [
        'label'  => 'Proyek Selesai',
        'value'  => $proyekSelesaiBulanIni,
        'sub'    => "bulan ini",
        'icon'   => 'bi-check-circle',
        'accent' => '#10b981',
        'light'  => 'bg-emerald-50 text-emerald-600',
        'persen' => hitungPersen($proyekSelesaiBulanIni, $proyekSelesaiBulanLalu),
    ],
];
foreach ($kpi as $k):
    $naik = $k['persen'] >= 0;
    $warnaPersen = $naik ? 'text-emerald-600 bg-emerald-50' : 'text-red-600 bg-red-50';
    $iconPersen  = $naik ? 'bi-arrow-up-right' : 'bi-arrow-down-right';
?>
<div class="card card-hover stat-card p-5" style="--stat-accent: <?= $k['accent'] ?>">
    <div class="flex items-start justify-between mb-3">
        <div class="w-11 h-11 <?= $k['light'] ?> rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="bi <?= $k['icon'] ?> text-lg"></i>
        </div>
        <span class="inline-flex items-center gap-0.5 px-2 py-1 rounded-lg text-[11px] font-bold <?= $warnaPersen ?>">
            <i class="bi <?= $iconPersen ?> text-xs"></i>
            <?= abs($k['persen']) ?>%
        </span>
    </div>
    <p class="text-[13px] font-medium text-slate-500 mb-1"><?= $k['label'] ?></p>
    <p class="text-[28px] leading-none font-extrabold text-slate-900 tracking-tight"><?= $k['value'] ?></p>
    <p class="text-[11px] text-slate-400 mt-2">vs bulan lalu · <?= $k['sub'] ?></p>
</div>
<?php endforeach; ?>
</div>

<!-- ============ CHART BAR TREN 6 BULAN + PIE STATUS ============ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <div class="card p-5 lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-bold text-slate-800 tracking-tight">Tren Proyek 6 Bulan Terakhir</h3>
                <p class="text-xs text-slate-400 mt-0.5">Jumlah proyek baru yang dibuat per bulan</p>
            </div>
            <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <i class="bi bi-graph-up"></i>
            </span>
        </div>
        <div class="h-64"><canvas id="chartTren"></canvas></div>
    </div>

    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-slate-800 tracking-tight">Distribusi Status</h3>
            <span class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <i class="bi bi-pie-chart"></i>
            </span>
        </div>
        <div class="flex items-center justify-center h-64"><canvas id="chartStatus"></canvas></div>
    </div>
</div>

<!-- ============ HEATMAP AKTIVITAS 30 HARI ============ -->
<div class="card p-5 mb-4">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-sm font-bold text-slate-800 tracking-tight">Heatmap Aktivitas — 30 Hari Terakhir</h3>
            <p class="text-xs text-slate-400 mt-0.5">Intensitas aktivitas sistem per hari</p>
        </div>
        <div class="flex items-center gap-2 text-[11px] text-slate-500">
            <span>Sedikit</span>
            <div class="flex gap-0.5">
                <span class="w-3 h-3 rounded bg-slate-100"></span>
                <span class="w-3 h-3 rounded bg-primary-200"></span>
                <span class="w-3 h-3 rounded bg-primary-400"></span>
                <span class="w-3 h-3 rounded bg-primary-600"></span>
                <span class="w-3 h-3 rounded bg-primary-800"></span>
            </div>
            <span>Banyak</span>
        </div>
    </div>
    <div class="grid grid-cols-15 gap-1.5">
        <?php
        $maxAktivitas = max(array_column($heatmap, 'total') ?: [1]);
        foreach ($heatmap as $h):
            $intensitas = $maxAktivitas > 0 ? $h['total'] / $maxAktivitas : 0;
            if ($h['total'] == 0) $bg = 'bg-slate-100';
            elseif ($intensitas < 0.25) $bg = 'bg-primary-200';
            elseif ($intensitas < 0.5)  $bg = 'bg-primary-400';
            elseif ($intensitas < 0.75) $bg = 'bg-primary-600';
            else $bg = 'bg-primary-800';
        ?>
        <div class="group relative">
            <div class="aspect-square rounded <?= $bg ?> hover:ring-2 hover:ring-primary-500 transition-all cursor-help"></div>
            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block z-30 pointer-events-none">
                <div class="bg-slate-900 text-white text-xs rounded-lg px-2.5 py-1.5 shadow-xl whitespace-nowrap">
                    <p class="font-bold"><?= formatTanggal($h['tgl']) ?></p>
                    <p class="text-slate-300"><?= $h['total'] ?> aktivitas</p>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-slate-900 rotate-45 -mt-1"></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <style>.grid-cols-15{grid-template-columns:repeat(15,minmax(0,1fr));}</style>
</div>

<!-- ============ ACTIVITY LOG TERKINI + PROYEK TERBARU ============ -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    <!-- Activity Log -->
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2">
                <i class="bi bi-activity text-primary-600"></i>
                Aktivitas Terkini
            </h3>
            <?php if ($isAdmin): ?>
            <a href="<?= BASE_URL ?>/pages/activity-log/index.php" class="text-xs text-primary-600 hover:text-primary-700 font-semibold inline-flex items-center gap-1">
                Lihat Semua <i class="bi bi-arrow-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <div class="divide-y divide-slate-100 max-h-[480px] overflow-y-auto">
            <?php if (empty($activityLog)): ?>
            <div class="px-5 py-10 text-center">
                <span class="empty-icon mb-3"><i class="bi bi-activity"></i></span>
                <p class="text-slate-400 text-sm">Belum ada aktivitas</p>
            </div>
            <?php else: ?>
                <?php foreach ($activityLog as $a):
                    [$icon, $bg] = getActivityIcon($a['tipe']);
                ?>
                <div class="px-5 py-3 hover:bg-slate-50/70 transition-colors">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg <?= $bg ?> flex items-center justify-center flex-shrink-0">
                            <i class="bi <?= $icon ?> text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-slate-700 leading-snug">
                                <span class="font-semibold text-slate-900"><?= sanitize($a['nama']) ?></span>
                                <span class="text-slate-500">— <?= sanitize($a['deskripsi']) ?></span>
                            </p>
                            <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-400">
                                <span class="inline-flex items-center gap-1"><i class="bi bi-clock"></i> <?= timeAgo($a['created_at']) ?></span>
                                <?php if ($a['modul']): ?>
                                <span class="text-slate-300">·</span>
                                <span class="px-1.5 py-0.5 bg-slate-100 rounded text-[10px] font-medium uppercase"><?= sanitize($a['modul']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Proyek Terbaru -->
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-800 tracking-tight">Proyek Terbaru</h3>
            <?php if (isAdminOrManajer()): ?>
            <a href="<?= BASE_URL ?>/pages/proyek/index.php" class="text-xs text-primary-600 hover:text-primary-700 font-semibold inline-flex items-center gap-1">
                Lihat Semua <i class="bi bi-arrow-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <div class="divide-y divide-slate-100 max-h-[480px] overflow-y-auto">
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
                        <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $p['id'] ?>" class="text-sm font-semibold text-slate-800 hover:text-primary-600 truncate block transition-colors">
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

<!-- ============ LAPORAN TERBARU + STOK KRITIS ============ -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
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
                            <p class="text-sm font-semibold text-slate-800 truncate"><?= sanitize($l['judul_laporan']) ?></p>
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
// ============ CHART TREN 6 BULAN ============
const trenData = <?= json_encode($trenProyek) ?>;
new Chart(document.getElementById('chartTren'), {
    type: 'bar',
    data: {
        labels: trenData.map(d => d.label),
        datasets: [{
            label: 'Proyek Baru',
            data: trenData.map(d => d.total),
            backgroundColor: 'rgba(99, 102, 241, 0.85)',
            hoverBackgroundColor: '#4f46e5',
            borderRadius: 8,
            borderSkipped: false,
            barThickness: 32,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                padding: 12,
                titleFont: { size: 12, weight: 'bold' },
                bodyFont: { size: 12 },
                cornerRadius: 8,
                callbacks: {
                    label: ctx => `${ctx.parsed.y} proyek`
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, font: { size: 11 }, color: '#94a3b8' },
                grid: { color: '#f1f5f9' }
            },
            x: {
                ticks: { font: { size: 11 }, color: '#64748b' },
                grid: { display: false }
            }
        }
    }
});

// ============ CHART STATUS ============
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
                labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle', font: { size: 11 }, padding: 12, color: '#64748b' }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
