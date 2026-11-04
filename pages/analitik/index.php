<?php
// pages/analitik/index.php — LAPORAN & ANALITIK EKSEKUTIF V2
$pageTitle   = 'Laporan & Analitik';
$currentPage = 'analitik';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'manajer_proyek']);

$db = db();

// ============ TREN PROYEK PER BULAN (12 bulan) ============
$trenBulanan = [];
for ($i = 11; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));

    $stmt = $db->prepare("SELECT COUNT(*) FROM proyek WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$bulan]);
    $baru = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM proyek WHERE status='selesai' AND DATE_FORMAT(tanggal_selesai, '%Y-%m') = ?");
    $stmt->execute([$bulan]);
    $selesai = (int)$stmt->fetchColumn();

    $trenBulanan[] = ['label' => $label, 'baru' => $baru, 'selesai' => $selesai];
}

// ============ DISTRIBUSI STATUS ============
$distribusi = $db->query("SELECT status, COUNT(*) as total FROM proyek GROUP BY status")->fetchAll();

// ============ PRODUKTIVITAS TEKNISI ============
$produktivitas = $db->query("
    SELECT u.nama, u.id, COUNT(l.id) as total_laporan,
           COUNT(DISTINCT l.proyek_id) as total_proyek
    FROM users u
    LEFT JOIN laporan_lapangan l ON l.user_id = u.id
    WHERE u.role = 'teknisi_lapangan'
    GROUP BY u.id, u.nama
    ORDER BY total_laporan DESC
    LIMIT 8
")->fetchAll();

// ============ TOP 5 KOMPONEN TERPAKAI ============
$topKomponen = $db->query("
    SELECT k.nama_komponen, k.kategori, k.satuan,
           SUM(pm.jumlah_digunakan) as total_pakai,
           COUNT(DISTINCT pm.proyek_id) as jumlah_proyek
    FROM penggunaan_material pm
    JOIN komponen k ON pm.komponen_id = k.id
    GROUP BY k.id, k.nama_komponen, k.kategori, k.satuan
    ORDER BY total_pakai DESC
    LIMIT 5
")->fetchAll();

// ============ EVALUASI KESEHATAN TIMELINE PROYEK ============
$proyekOverdue = (int)$db->query("
    SELECT COUNT(*) FROM proyek 
    WHERE status IN ('survei','perencanaan','instalasi') 
      AND tanggal_selesai < CURDATE()
")->fetchColumn();

$proyekOnTrack = (int)$db->query("
    SELECT COUNT(*) FROM proyek 
    WHERE status IN ('survei','perencanaan','instalasi') 
      AND tanggal_selesai >= CURDATE()
")->fetchColumn();

// ============ STATISTIK RINGKASAN ============
$totalProyek  = (int)$db->query("SELECT COUNT(*) FROM proyek")->fetchColumn();
$totalLaporan = (int)$db->query("SELECT COUNT(*) FROM laporan_lapangan")->fetchColumn();
$rataProgres  = (int)$db->query("SELECT COALESCE(ROUND(AVG(progres)), 0) FROM proyek WHERE status != 'selesai'")->fetchColumn();
$teknisiAktif = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM laporan_lapangan WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Laporan & Analitik Eksekutif</span>
    </nav>

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3.5">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-600 to-indigo-700 flex items-center justify-center text-white shadow-lg shadow-primary-600/25">
                <i class="bi bi-graph-up-arrow text-lg"></i>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Laporan & Analitik Performa Sistem</h2>
                <p class="text-xs text-slate-500 mt-0.5">Evaluasi tren penyelesaian proyek PLTS, produktivitas tim teknis, dan konsumsi material 12 bulan terakhir</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="btn-secondary btn-sm">
                <i class="bi bi-printer"></i> Cetak Ringkasan
            </button>
            <a href="<?= BASE_URL ?>/pages/laporan/stok.php" class="btn-primary btn-sm">
                <i class="bi bi-file-bar-graph"></i> Analitik Stok Gudang
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5 mb-6">
        <div class="card p-4 stat-card" style="--stat-accent: #4f46e5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Proyek</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= $totalProyek ?></p>
                </div>
                <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="bi bi-diagram-3-fill"></i>
                </span>
            </div>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: #10b981">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Proyek On-Track</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= $proyekOnTrack ?></p>
                </div>
                <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="bi bi-check-circle-fill"></i>
                </span>
            </div>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: <?= $proyekOverdue > 0 ? '#ef4444' : '#64748b' ?>">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Lewat Estimasi</p>
                    <p class="text-2xl font-extrabold <?= $proyekOverdue > 0 ? 'text-red-600' : 'text-slate-900' ?> mt-1"><?= $proyekOverdue ?></p>
                </div>
                <span class="w-9 h-9 rounded-xl <?= $proyekOverdue > 0 ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-500' ?> flex items-center justify-center">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                </span>
            </div>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: #0ea5e9">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Laporan Masuk</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= $totalLaporan ?></p>
                </div>
                <span class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </span>
            </div>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: #f59e0b">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Rata-rata Progres</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= $rataProgres ?>%</p>
                </div>
                <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="bi bi-speedometer2"></i>
                </span>
            </div>
        </div>
    </div>

    <!-- Chart: Tren Bulanan + Pie Status -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
        <div class="card p-5 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 tracking-tight">Tren Proyek 12 Bulan Terakhir</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Perbandingan proyek baru dimulai vs proyek selesai per bulan</p>
                </div>
                <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="bi bi-graph-up"></i>
                </span>
            </div>
            <div class="h-72"><canvas id="chartTren"></canvas></div>
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 tracking-tight">Komposisi Status Proyek</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Proporsi tahapan seluruh proyek</p>
                </div>
                <span class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i class="bi bi-pie-chart-fill"></i>
                </span>
            </div>
            <div class="h-72 flex items-center justify-center"><canvas id="chartStatus"></canvas></div>
        </div>
    </div>

    <!-- Produktivitas Teknisi + Top 5 Komponen (Dengan Rank Badges Profesional Tanpa Emoji) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Produktivitas Teknisi -->
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 tracking-tight">Produktivitas Teknisi Lapangan</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Jumlah laporan harian vs keterlibatan proyek per teknisi (<?= $teknisiAktif ?> aktif 30 hari terakhir)</p>
                </div>
                <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="bi bi-people-fill"></i>
                </span>
            </div>
            <?php if (empty($produktivitas)): ?>
            <div class="py-12 text-center text-sm text-slate-400">Belum ada data teknisi lapangan</div>
            <?php else: ?>
            <div class="h-72"><canvas id="chartTeknisi"></canvas></div>
            <?php endif; ?>
        </div>

        <!-- Top 5 Komponen Terpakai -->
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 tracking-tight">Top 5 Komponen PLTS Paling Banyak Dipakai</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Akumulasi pemakaian material tertinggi lintas proyek</p>
                </div>
                <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="bi bi-award-fill"></i>
                </span>
            </div>

            <?php if (empty($topKomponen)): ?>
            <div class="py-12 text-center text-sm text-slate-400">Belum ada catatan penggunaan komponen</div>
            <?php else: ?>
            <div class="space-y-4">
                <?php
                $maxPakai = max(array_column($topKomponen, 'total_pakai'));
                $rankBadgeStyles = [
                    0 => 'bg-amber-500 text-white shadow-sm shadow-amber-500/30',
                    1 => 'bg-slate-400 text-white',
                    2 => 'bg-amber-700 text-white',
                ];
                foreach ($topKomponen as $i => $k):
                    $pct = $maxPakai > 0 ? round(($k['total_pakai'] / $maxPakai) * 100) : 0;
                    $rCls = $rankBadgeStyles[$i] ?? 'bg-slate-100 text-slate-600 border border-slate-200';
                ?>
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <span class="w-7 h-7 rounded-lg text-xs font-extrabold flex items-center justify-center flex-shrink-0 <?= $rCls ?>">
                                #<?= $i + 1 ?>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-slate-800 truncate"><?= sanitize($k['nama_komponen']) ?></p>
                                <p class="text-[11px] text-slate-400"><?= sanitize($k['kategori']) ?> · Digunakan pada <?= $k['jumlah_proyek'] ?> proyek</p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 ml-3">
                            <span class="text-sm font-extrabold text-slate-900"><?= number_format((int)$k['total_pakai']) ?></span>
                            <span class="text-xs text-slate-400"> <?= sanitize($k['satuan']) ?></span>
                        </div>
                    </div>
                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-primary-600 to-indigo-500 rounded-full transition-all" style="width: <?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const trenData = <?= json_encode($trenBulanan) ?>;
new Chart(document.getElementById('chartTren'), {
    type: 'line',
    data: {
        labels: trenData.map(d => d.label),
        datasets: [
            {
                label: 'Proyek Baru',
                data: trenData.map(d => d.baru),
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#4f46e5',
            },
            {
                label: 'Proyek Selesai',
                data: trenData.map(d => d.selesai),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#10b981',
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle', font: { size: 11 }, padding: 14 } },
            tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 8 }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 }, color: '#94a3b8' }, grid: { color: '#f1f5f9' } },
            x: { ticks: { font: { size: 11 }, color: '#64748b' }, grid: { display: false } }
        }
    }
});

const statusData = <?= json_encode($distribusi) ?>;
const statusColors = {
    survei: '#94a3b8', perencanaan: '#38bdf8', instalasi: '#4f46e5',
    selesai: '#10b981', ditunda: '#f59e0b'
};
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: statusData.map(d => d.status.charAt(0).toUpperCase() + d.status.slice(1)),
        datasets: [{
            data: statusData.map(d => d.total),
            backgroundColor: statusData.map(d => statusColors[d.status] || '#94a3b8'),
            borderWidth: 2,
            borderColor: '#ffffff',
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle', font: { size: 11 }, padding: 12 } }
        }
    }
});

<?php if (!empty($produktivitas)): ?>
const teknisiData = <?= json_encode($produktivitas) ?>;
new Chart(document.getElementById('chartTeknisi'), {
    type: 'bar',
    data: {
        labels: teknisiData.map(d => d.nama.split(' ').slice(0, 2).join(' ')),
        datasets: [
            {
                label: 'Total Laporan',
                data: teknisiData.map(d => d.total_laporan),
                backgroundColor: '#10b981',
                borderRadius: 6,
                barThickness: 18,
            },
            {
                label: 'Proyek Ditangani',
                data: teknisiData.map(d => d.total_proyek),
                backgroundColor: '#0ea5e9',
                borderRadius: 6,
                barThickness: 18,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle', font: { size: 11 }, padding: 12 } }
        },
        scales: {
            x: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 }, color: '#94a3b8' }, grid: { color: '#f1f5f9' } },
            y: { ticks: { font: { size: 11 }, color: '#64748b' }, grid: { display: false } }
        }
    }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>