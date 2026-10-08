<?php
// pages/analitik/index.php — LAPORAN & ANALITIK
$pageTitle   = 'Laporan & Analitik';
$currentPage = 'analitik';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);

$db = db();

// ============ TREN PROYEK PER BULAN (12 bulan) ============
$trenBulanan = [];
for ($i = 11; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $label = date('M', strtotime("-$i months"));

    $stmt = $db->prepare("SELECT COUNT(*) FROM proyek WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$bulan]);
    $baru = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM proyek WHERE status='selesai' AND DATE_FORMAT(tanggal_selesai, '%Y-%m') = ?");
    $stmt->execute([$bulan]);
    $selesai = (int) $stmt->fetchColumn();

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

// ============ STATISTIK RINGKASAN ============
$totalProyek     = (int) $db->query("SELECT COUNT(*) FROM proyek")->fetchColumn();
$totalLaporan    = (int) $db->query("SELECT COUNT(*) FROM laporan_lapangan")->fetchColumn();
$rataProgres     = (int) $db->query("SELECT COALESCE(AVG(progres),0) FROM proyek WHERE status != 'selesai'")->fetchColumn();
$teknisiAktif    = (int) $db->query("SELECT COUNT(DISTINCT user_id) FROM laporan_lapangan WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-start justify-between mb-5 flex-wrap gap-3">
    <div class="flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white shadow-lg shadow-amber-500/25">
            <i class="bi bi-graph-up-arrow text-lg"></i>
        </span>
        <div>
            <h2 class="text-lg font-bold text-slate-900 tracking-tight">Laporan & Analitik</h2>
            <p class="text-[13px] text-slate-500 mt-0.5">Insight performa sistem PLTS 12 bulan terakhir</p>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php
    $summary = [
        ['Total Proyek', $totalProyek, 'bi-diagram-3', '#4f46e5', 'bg-indigo-50 text-indigo-600'],
        ['Total Laporan', $totalLaporan, 'bi-file-earmark-text', '#0ea5e9', 'bg-sky-50 text-sky-600'],
        ['Rata-rata Progres', $rataProgres.'%', 'bi-speedometer2', '#f59e0b', 'bg-amber-50 text-amber-600'],
        ['Teknisi Aktif (30d)', $teknisiAktif, 'bi-people', '#10b981', 'bg-emerald-50 text-emerald-600'],
    ];
    foreach ($summary as $s): ?>
    <div class="card p-5 stat-card" style="--stat-accent: <?= $s[3] ?>">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-slate-500 mb-1"><?= $s[0] ?></p>
                <p class="text-[28px] leading-none font-extrabold text-slate-900 tracking-tight"><?= $s[1] ?></p>
            </div>
            <div class="w-11 h-11 <?= $s[4] ?> rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="bi <?= $s[2] ?> text-lg"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Chart: Tren Bulanan + Pie Status -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <div class="card p-5 lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-bold text-slate-800 tracking-tight">Tren Proyek 12 Bulan</h3>
                <p class="text-xs text-slate-400 mt-0.5">Proyek baru dibuat vs proyek selesai</p>
            </div>
            <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <i class="bi bi-graph-up"></i>
            </span>
        </div>
        <div class="h-72"><canvas id="chartTren"></canvas></div>
    </div>

    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-slate-800 tracking-tight">Distribusi Status</h3>
            <span class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <i class="bi bi-pie-chart"></i>
            </span>
        </div>
        <div class="h-72 flex items-center justify-center"><canvas id="chartStatus"></canvas></div>
    </div>
</div>

<!-- Produktivitas Teknisi + Top Komponen -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <!-- Produktivitas Teknisi -->
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-bold text-slate-800 tracking-tight">Produktivitas Teknisi</h3>
                <p class="text-xs text-slate-400 mt-0.5">Jumlah laporan lapangan per teknisi</p>
            </div>
            <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i class="bi bi-people-fill"></i>
            </span>
        </div>
        <?php if (empty($produktivitas)): ?>
        <div class="py-10 text-center">
            <p class="text-sm text-slate-400">Belum ada data teknisi</p>
        </div>
        <?php else: ?>
        <div class="h-72"><canvas id="chartTeknisi"></canvas></div>
        <?php endif; ?>
    </div>

    <!-- Top 5 Komponen -->
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-bold text-slate-800 tracking-tight">Top 5 Komponen Terpakai</h3>
                <p class="text-xs text-slate-400 mt-0.5">Komponen paling sering digunakan</p>
            </div>
            <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <i class="bi bi-trophy-fill"></i>
            </span>
        </div>
        <?php if (empty($topKomponen)): ?>
        <div class="py-10 text-center">
            <p class="text-sm text-slate-400">Belum ada data penggunaan</p>
        </div>
        <?php else: ?>
        <div class="space-y-3">
            <?php
            $maxPakai = max(array_column($topKomponen, 'total_pakai'));
            $medals = ['🥇','🥈','🥉','4️⃣','5️⃣'];
            foreach ($topKomponen as $i => $k):
                $pct = $maxPakai > 0 ? round(($k['total_pakai'] / $maxPakai) * 100) : 0;
            ?>
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        <span class="text-lg"><?= $medals[$i] ?? '•' ?></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-800 truncate"><?= sanitize($k['nama_komponen']) ?></p>
                            <p class="text-[11px] text-slate-400"><?= sanitize($k['kategori']) ?> · <?= $k['jumlah_proyek'] ?> proyek</p>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0 ml-3">
                        <p class="text-sm font-bold text-slate-900"><?= number_format($k['total_pakai']) ?></p>
                        <p class="text-[10px] text-slate-400"><?= sanitize($k['satuan']) ?></p>
                    </div>
                </div>
                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-amber-400 to-orange-500 rounded-full transition-all" style="width: <?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// ============ CHART TREN BULANAN ============
const trenData = <?= json_encode($trenBulanan) ?>;
new Chart(document.getElementById('chartTren'), {
    type: 'line',
    data: {
        labels: trenData.map(d => d.label),
        datasets: [
            {
                label: 'Proyek Baru',
                data: trenData.map(d => d.baru),
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#6366f1',
                pointHoverRadius: 6,
            },
            {
                label: 'Proyek Selesai',
                data: trenData.map(d => d.selesai),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#10b981',
                pointHoverRadius: 6,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle', font: { size: 11 }, padding: 12, color: '#64748b' } },
            tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 8 }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 }, color: '#94a3b8' }, grid: { color: '#f1f5f9' } },
            x: { ticks: { font: { size: 11 }, color: '#64748b' }, grid: { display: false } }
        }
    }
});

// ============ CHART STATUS ============
const statusData = <?= json_encode($distribusi) ?>;
const statusColors = {
    survei: '#94a3b8', perencanaan: '#38bdf8', instalasi: '#6366f1',
    selesai: '#10b981', ditunda: '#f59e0b'
};
new Chart(document.getElementById('chartStatus'), {
    type: 'pie',
    data: {
        labels: statusData.map(d => d.status.charAt(0).toUpperCase() + d.status.slice(1)),
        datasets: [{
            data: statusData.map(d => d.total),
            backgroundColor: statusData.map(d => statusColors[d.status] || '#94a3b8'),
            borderWidth: 2,
            borderColor: '#ffffff',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle', font: { size: 11 }, padding: 12, color: '#64748b' } }
        }
    }
});

// ============ CHART TEKNISI ============
<?php if (!empty($produktivitas)): ?>
const teknisiData = <?= json_encode($produktivitas) ?>;
new Chart(document.getElementById('chartTeknisi'), {
    type: 'bar',
    data: {
        labels: teknisiData.map(d => d.nama.split(' ').slice(0,2).join(' ')),
        datasets: [
            {
                label: 'Laporan',
                data: teknisiData.map(d => d.total_laporan),
                backgroundColor: '#10b981',
                borderRadius: 6,
                barThickness: 24,
            },
            {
                label: 'Proyek',
                data: teknisiData.map(d => d.total_proyek),
                backgroundColor: '#0ea5e9',
                borderRadius: 6,
                barThickness: 24,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle', font: { size: 11 }, padding: 12, color: '#64748b' } }
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