<?php
// pages/komponen/status.php — OVERVIEW STOK (View-only untuk semua role)
$pageTitle   = 'Status Material';
$currentPage = 'status-material';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$db = db();

// Summary stok
$summary = $db->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN stok_tersedia > stok_minimum * 1.5 THEN 1 ELSE 0 END) as aman,
        SUM(CASE WHEN stok_tersedia > stok_minimum AND stok_tersedia <= stok_minimum * 1.5 THEN 1 ELSE 0 END) as rendah,
        SUM(CASE WHEN stok_tersedia > 0 AND stok_tersedia <= stok_minimum THEN 1 ELSE 0 END) as kritis,
        SUM(CASE WHEN stok_tersedia = 0 THEN 1 ELSE 0 END) as habis,
        COALESCE(SUM(stok_tersedia), 0) as total_unit
    FROM komponen
")->fetch();

// Komponen kritis & habis (prioritas atas)
$komponenPerhatian = $db->query("
    SELECT * FROM komponen
    WHERE stok_tersedia <= stok_minimum
    ORDER BY stok_tersedia ASC, nama_komponen ASC
")->fetchAll();

// Stok per kategori
$perKategori = $db->query("
    SELECT kategori,
           COUNT(*) as jumlah_item,
           SUM(stok_tersedia) as total_stok,
           SUM(CASE WHEN stok_tersedia <= stok_minimum THEN 1 ELSE 0 END) as kritis
    FROM komponen
    GROUP BY kategori
    ORDER BY total_stok DESC
")->fetchAll();

// Komponen dengan stok terbanyak (top 5)
$topStok = $db->query("
    SELECT * FROM komponen
    WHERE stok_tersedia > 0
    ORDER BY stok_tersedia DESC
    LIMIT 5
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div class="flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/25">
            <i class="bi bi-boxes text-lg"></i>
        </span>
        <div>
            <h2 class="text-lg font-bold text-slate-900 tracking-tight">Status Material</h2>
            <p class="text-[13px] text-slate-500 mt-0.5">Monitoring stok komponen PLTS secara real-time</p>
        </div>
    </div>
    <?php if (isAdminOrGudang()): ?>
    <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="btn-primary">
        <i class="bi bi-gear"></i> Kelola Komponen
    </a>
    <?php endif; ?>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
    <?php
    $cards = [
        ['val' => (int)$summary['total'],      'label' => 'Total Komponen', 'icon' => 'bi-grid-3x3-gap',           'color' => 'bg-slate-600',   'border' => 'border-slate-200'],
        ['val' => (int)$summary['aman'],       'label' => 'Stok Aman',      'icon' => 'bi-check-circle-fill',       'color' => 'bg-emerald-500', 'border' => 'border-emerald-200'],
        ['val' => (int)$summary['rendah'],     'label' => 'Stok Rendah',    'icon' => 'bi-dash-circle-fill',        'color' => 'bg-amber-500',   'border' => 'border-amber-200'],
        ['val' => (int)$summary['kritis'],     'label' => 'Stok Kritis',    'icon' => 'bi-exclamation-circle-fill', 'color' => 'bg-orange-500',  'border' => 'border-orange-200'],
        ['val' => (int)$summary['habis'],      'label' => 'Stok Habis',     'icon' => 'bi-x-circle-fill',           'color' => 'bg-red-500',     'border' => 'border-red-200'],
    ];
    foreach ($cards as $c): ?>
    <div class="card p-4 border <?= $c['border'] ?> card-hover">
        <div class="w-9 h-9 <?= $c['color'] ?> rounded-xl flex items-center justify-center mb-3 shadow-sm">
            <i class="bi <?= $c['icon'] ?> text-white"></i>
        </div>
        <div class="text-2xl font-bold text-slate-800"><?= number_format($c['val']) ?></div>
        <div class="text-xs text-slate-500 mt-0.5"><?= $c['label'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Alert jika ada stok habis/kritis -->
<?php if ((int)$summary['habis'] > 0 || (int)$summary['kritis'] > 0): ?>
<div class="card p-4 mb-5 border-2 border-red-200 bg-gradient-to-r from-red-50 to-orange-50">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="bi bi-exclamation-triangle-fill text-red-500 text-lg"></i>
        </div>
        <div class="flex-1">
            <h4 class="text-sm font-bold text-red-800">
                ⚠️ <?= (int)$summary['habis'] + (int)$summary['kritis'] ?> Komponen Perlu Perhatian
            </h4>
            <p class="text-xs text-red-600 mt-0.5">
                <?php if ((int)$summary['habis'] > 0): ?>
                <strong><?= (int)$summary['habis'] ?> stok habis</strong>
                <?php endif; ?>
                <?php if ((int)$summary['habis'] > 0 && (int)$summary['kritis'] > 0): ?> dan <?php endif; ?>
                <?php if ((int)$summary['kritis'] > 0): ?>
                <strong><?= (int)$summary['kritis'] ?> stok kritis</strong>
                <?php endif; ?>
                — segera lakukan pengisian ulang.
            </p>
        </div>
        <?php if (isAdminOrGudang()): ?>
        <a href="<?= BASE_URL ?>/pages/komponen/index.php?status=kritis" class="btn-danger btn-sm flex-shrink-0">
            Lihat Detail
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <!-- Komponen Perlu Perhatian -->
    <div class="card overflow-hidden lg:col-span-2">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2">
                <i class="bi bi-exclamation-triangle text-red-500"></i>
                Komponen Perlu Perhatian
            </h3>
            <span class="text-xs text-slate-400"><?= count($komponenPerhatian) ?> item</span>
        </div>
        <?php if (empty($komponenPerhatian)): ?>
        <div class="px-5 py-12 text-center">
            <i class="bi bi-check-circle text-4xl text-emerald-300 block mb-2"></i>
            <p class="text-sm text-slate-500">Semua stok dalam kondisi aman!</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-100 max-h-[480px] overflow-y-auto">
            <?php foreach ($komponenPerhatian as $k):
                [$cls, $label, $statusKey] = stokStatus($k['stok_tersedia'], $k['stok_minimum']);
                $pct = $k['stok_minimum'] > 0 ? min(100, round($k['stok_tersedia'] / $k['stok_minimum'] * 100)) : 0;
                $barColor = $statusKey === 'habis' ? 'bg-slate-400' : 'bg-red-500';
            ?>
            <div class="px-5 py-3.5 hover:bg-slate-50/70 transition-colors">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div class="w-10 h-10 rounded-xl bg-red-50 text-red-500 flex items-center justify-center flex-shrink-0">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-800 truncate"><?= sanitize($k['nama_komponen']) ?></p>
                            <p class="text-xs text-slate-400"><?= sanitize($k['kategori']) ?> · Min: <?= $k['stok_minimum'] ?> <?= sanitize($k['satuan']) ?></p>
                        </div>
                    </div>
                    <span class="badge-modern inline-flex items-center rounded-full text-xs font-semibold <?= $cls ?> flex-shrink-0">
                        <?= $label ?>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full <?= $barColor ?> rounded-full" style="width: <?= $pct ?>%"></div>
                    </div>
                    <span class="text-xs font-bold <?= $statusKey === 'habis' ? 'text-slate-500' : 'text-red-600' ?> w-20 text-right">
                        <?= $k['stok_tersedia'] ?> / <?= $k['stok_minimum'] ?> <?= sanitize($k['satuan']) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Stok Per Kategori -->
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2">
                <i class="bi bi-pie-chart text-purple-600"></i>
                Stok Per Kategori
            </h3>
        </div>
        <div class="p-5">
            <div style="height: 180px;">
                <canvas id="chartKategori"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                <?php foreach ($perKategori as $k): ?>
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        <span class="text-sm"><?= ['Panel Surya'=>'☀️','Inverter'=>'⚡','Baterai'=>'🔋','Kabel'=>'🔌','Mounting'=>'🔩','Proteksi'=>'🛡️','Monitoring'=>'📡','Lainnya'=>'📦'][$k['kategori']] ?? '📦' ?></span>
                        <span class="text-slate-600 truncate"><?= sanitize($k['kategori']) ?></span>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="font-semibold text-slate-700"><?= number_format($k['total_stok']) ?></span>
                        <?php if ($k['kritis'] > 0): ?>
                        <span class="text-[10px] bg-red-100 text-red-700 px-1.5 py-0.5 rounded-full font-bold"><?= $k['kritis'] ?> kritis</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Top Stok Terbanyak -->
<div class="card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2">
            <i class="bi bi-trophy text-amber-500"></i>
            Komponen dengan Stok Terbanyak
        </h3>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
        <?php foreach ($topStok as $i => $k): ?>
        <div class="p-4 text-center">
            <div class="text-3xl mb-1"><?= ['🥇','🥈','🥉','4️⃣','5️⃣'][$i] ?? '•' ?></div>
            <p class="text-lg font-bold text-slate-800"><?= number_format($k['stok_tersedia']) ?></p>
            <p class="text-xs text-slate-400 mb-1"><?= sanitize($k['satuan']) ?></p>
            <p class="text-xs font-medium text-slate-600 truncate"><?= sanitize($k['nama_komponen']) ?></p>
            <p class="text-[10px] text-slate-400 mt-0.5"><?= sanitize($k['kategori']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Chart Stok Per Kategori
const chartData = <?= json_encode([
    'labels' => array_column($perKategori, 'kategori'),
    'data'   => array_map('intval', array_column($perKategori, 'total_stok')),
]) ?>;

const ctx = document.getElementById('chartKategori');
if (ctx) {
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: chartData.labels,
            datasets: [{
                data: chartData.data,
                backgroundColor: ['#4f46e5','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#64748b'],
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    cornerRadius: 8,
                }
            }
        }
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>