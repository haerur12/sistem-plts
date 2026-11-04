<?php
// pages/komponen/status.php — OVERVIEW & KATALOG CEK STOK MATERIAL (UNTUK SEMUA ROLE)
$pageTitle   = 'Status Material';
$currentPage = 'status-material';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$db     = db();
$search = trim($_GET['search'] ?? '');
$kat    = trim($_GET['kategori'] ?? '');
$status = trim($_GET['status'] ?? '');

// Summary stok keseluruhan
$summary = $db->query("
    SELECT
        COUNT(*) as total,
        COALESCE(SUM(CASE WHEN stok_tersedia > stok_minimum * 1.5 THEN 1 ELSE 0 END), 0) as aman,
        COALESCE(SUM(CASE WHEN stok_tersedia > stok_minimum AND stok_tersedia <= stok_minimum * 1.5 THEN 1 ELSE 0 END), 0) as rendah,
        COALESCE(SUM(CASE WHEN stok_tersedia > 0 AND stok_tersedia <= stok_minimum THEN 1 ELSE 0 END), 0) as kritis,
        COALESCE(SUM(CASE WHEN stok_tersedia = 0 THEN 1 ELSE 0 END), 0) as habis,
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
           COALESCE(SUM(stok_tersedia), 0) as total_stok,
           COALESCE(SUM(CASE WHEN stok_tersedia <= stok_minimum THEN 1 ELSE 0 END), 0) as kritis
    FROM komponen
    GROUP BY kategori
    ORDER BY total_stok DESC
")->fetchAll();

// Filter untuk Tabel Katalog Lengkap (Read-Only untuk Teknisi/Manajer)
$where  = ['1=1'];
$params = [];
if ($search !== '') {
    $where[]  = '(nama_komponen LIKE ? OR kategori LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($kat !== '') {
    $where[]  = 'kategori = ?';
    $params[] = $kat;
}
if ($status === 'aman') {
    $where[] = 'stok_tersedia > stok_minimum * 1.5';
} elseif ($status === 'rendah') {
    $where[] = 'stok_tersedia > stok_minimum AND stok_tersedia <= stok_minimum * 1.5';
} elseif ($status === 'kritis') {
    $where[] = 'stok_tersedia > 0 AND stok_tersedia <= stok_minimum';
} elseif ($status === 'habis') {
    $where[] = 'stok_tersedia = 0';
}

$whereStr = implode(' AND ', $where);
$stmtKatalog = $db->prepare("SELECT * FROM komponen WHERE $whereStr ORDER BY kategori ASC, nama_komponen ASC");
$stmtKatalog->execute($params);
$katalogKomponen = $stmtKatalog->fetchAll();

// Ikon Vektor Profesional per Kategori (Tanpa Emoji)
$katMeta = [
    'Panel Surya' => ['icon' => 'bi-sun-fill',          'cls' => 'bg-amber-50 text-amber-600 ring-amber-200'],
    'Inverter'    => ['icon' => 'bi-cpu-fill',          'cls' => 'bg-indigo-50 text-indigo-600 ring-indigo-200'],
    'Baterai'     => ['icon' => 'bi-battery-charging',  'cls' => 'bg-emerald-50 text-emerald-600 ring-emerald-200'],
    'Kabel'       => ['icon' => 'bi-bezier2',           'cls' => 'bg-sky-50 text-sky-600 ring-sky-200'],
    'Mounting'    => ['icon' => 'bi-layers-half',       'cls' => 'bg-slate-100 text-slate-700 ring-slate-300'],
    'Proteksi'    => ['icon' => 'bi-shield-fill-check', 'cls' => 'bg-rose-50 text-rose-600 ring-rose-200'],
    'Monitoring'  => ['icon' => 'bi-broadcast-pin',     'cls' => 'bg-violet-50 text-violet-600 ring-violet-200'],
    'Lainnya'     => ['icon' => 'bi-box-seam-fill',     'cls' => 'bg-teal-50 text-teal-600 ring-teal-200'],
];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Status Ketersediaan Material</span>
    </nav>

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3.5">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-600 to-indigo-700 flex items-center justify-center text-white shadow-lg shadow-primary-600/25">
                <i class="bi bi-boxes text-lg"></i>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Status & Katalog Ketersediaan Material</h2>
                <p class="text-xs text-slate-500 mt-0.5">Pantau kondisi stok komponen PLTS secara *real-time* sebelum penjadwalan atau eksekusi lapangan</p>
            </div>
        </div>
        <?php if (isAdminOrGudang()): ?>
        <div class="flex items-center gap-2">
            <a href="<?= BASE_URL ?>/pages/material/create.php" class="btn-secondary btn-sm">
                <i class="bi bi-arrow-left-right text-primary-600"></i> Catat Penggunaan
            </a>
            <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="btn-primary btn-sm">
                <i class="bi bi-gear"></i> Kelola Inventaris
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- KPI Summary Cards (Klik untuk Filter Katalog di Bawah) -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
        <?php
        $cards = [
            ['key' => '',       'val' => (int)$summary['total'],  'label' => 'Total SKU',   'icon' => 'bi-layers-fill',             'accent' => '#475569', 'cls' => 'bg-slate-100 text-slate-600'],
            ['key' => 'aman',   'val' => (int)$summary['aman'],   'label' => 'Stok Aman',   'icon' => 'bi-shield-fill-check',       'accent' => '#10b981', 'cls' => 'bg-emerald-50 text-emerald-600'],
            ['key' => 'rendah', 'val' => (int)$summary['rendah'], 'label' => 'Stok Rendah', 'icon' => 'bi-exclamation-circle-fill', 'accent' => '#f59e0b', 'cls' => 'bg-amber-50 text-amber-600'],
            ['key' => 'kritis', 'val' => (int)$summary['kritis'], 'label' => 'Stok Kritis', 'icon' => 'bi-lightning-charge-fill',   'accent' => '#f97316', 'cls' => 'bg-orange-50 text-orange-600'],
            ['key' => 'habis',  'val' => (int)$summary['habis'],  'label' => 'Stok Habis',  'icon' => 'bi-x-octagon-fill',          'accent' => '#ef4444', 'cls' => 'bg-red-50 text-red-600'],
        ];
        foreach ($cards as $c):
            $isAct = ($status === $c['key']);
        ?>
        <a href="?status=<?= urlencode($c['key']) ?>&kategori=<?= urlencode($kat) ?>&search=<?= urlencode($search) ?>#katalogStok"
           class="card p-4 stat-card hover:shadow-md transition-all block <?= $isAct ? 'ring-2 ring-primary-500 bg-primary-50/20' : '' ?>"
           style="--stat-accent: <?= $c['accent'] ?>">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider"><?= $c['label'] ?></p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1 leading-none"><?= number_format($c['val']) ?></p>
                </div>
                <div class="w-9 h-9 <?= $c['cls'] ?> rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="bi <?= $c['icon'] ?> text-base"></i>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Komponen Perlu Perhatian + Distribusi Kategori -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 mb-6">
        <!-- Komponen Kritis/Habis -->
        <div class="card overflow-hidden lg:col-span-7">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-red-500"></i>
                    Komponen Perlu Perhatian (Stok Kritis & Habis)
                </h3>
                <span class="badge-modern bg-red-50 text-red-700 ring-1 ring-red-200"><?= count($komponenPerhatian) ?> SKU</span>
            </div>
            <?php if (empty($komponenPerhatian)): ?>
            <div class="px-5 py-14 text-center">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center mx-auto mb-2.5 text-xl">
                    <i class="bi bi-shield-check"></i>
                </div>
                <p class="text-sm font-bold text-slate-700">Seluruh Stok Material Aman</p>
                <p class="text-xs text-slate-400 mt-0.5">Tidak ada komponen yang berada di bawah batas minimum.</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-slate-100 max-h-[380px] overflow-y-auto">
                <?php foreach ($komponenPerhatian as $k):
                    [$cls, $label, $statusKey] = stokStatus((int)$k['stok_tersedia'], (int)$k['stok_minimum']);
                    $meta = $katMeta[$k['kategori']] ?? $katMeta['Lainnya'];
                    $pct = $k['stok_minimum'] > 0 ? min(100, (int)round(($k['stok_tersedia'] / $k['stok_minimum']) * 100)) : 0;
                    $barColor = $statusKey === 'habis' ? 'bg-slate-400' : 'bg-red-500';
                ?>
                <div class="px-5 py-3.5 hover:bg-slate-50/70 transition-colors">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="w-9 h-9 rounded-xl ring-1 <?= $meta['cls'] ?> flex items-center justify-center flex-shrink-0">
                                <i class="bi <?= $meta['icon'] ?>"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-slate-800 truncate"><?= sanitize($k['nama_komponen']) ?></p>
                                <p class="text-xs text-slate-400"><?= sanitize($k['kategori']) ?> · Batas Min: <?= $k['stok_minimum'] ?> <?= sanitize($k['satuan']) ?></p>
                            </div>
                        </div>
                        <span class="badge-modern <?= $cls ?> flex-shrink-0"><?= $label ?></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full <?= $barColor ?> rounded-full" style="width: <?= $pct ?>%"></div>
                        </div>
                        <span class="text-xs font-extrabold <?= $statusKey === 'habis' ? 'text-slate-500' : 'text-red-600' ?> w-24 text-right">
                            <?= $k['stok_tersedia'] ?> / <?= $k['stok_minimum'] ?> <?= sanitize($k['satuan']) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Stok Per Kategori (Dengan Vector Icons) -->
        <div class="card overflow-hidden lg:col-span-5">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2">
                    <i class="bi bi-pie-chart-fill text-indigo-600"></i>
                    Distribusi Stok Per Kategori
                </h3>
            </div>
            <div class="p-5">
                <div style="height: 165px;">
                    <canvas id="chartKategori"></canvas>
                </div>
                <div class="mt-4 space-y-2 max-h-44 overflow-y-auto pr-1">
                    <?php foreach ($perKategori as $k):
                        $meta = $katMeta[$k['kategori']] ?? $katMeta['Lainnya'];
                    ?>
                    <a href="?kategori=<?= urlencode($k['kategori']) ?>#katalogStok"
                       class="flex items-center justify-between p-2 rounded-xl hover:bg-slate-50 transition-colors text-xs">
                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                            <span class="w-7 h-7 rounded-lg ring-1 <?= $meta['cls'] ?> flex items-center justify-center flex-shrink-0">
                                <i class="bi <?= $meta['icon'] ?> text-xs"></i>
                            </span>
                            <span class="font-semibold text-slate-700 truncate"><?= sanitize($k['kategori']) ?></span>
                            <span class="text-[11px] text-slate-400">(<?= $k['jumlah_item'] ?> SKU)</span>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="font-extrabold text-slate-800"><?= number_format((int)$k['total_stok']) ?> unit</span>
                            <?php if ((int)$k['kritis'] > 0): ?>
                            <span class="text-[10px] bg-red-50 text-red-700 border border-red-200 px-1.5 py-0.5 rounded-md font-bold"><?= $k['kritis'] ?> kritis</span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================================
         KATALOG PENCARIAN CEK STOK MATERIAL (UNTUK SEMUA ROLE)
    =========================================================== -->
    <div id="katalogStok" class="card overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i class="bi bi-search text-primary-600"></i> Direktori Cek Ketersediaan Komponen Gudang
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Menampilkan <?= count($katalogKomponen) ?> komponen yang sesuai dengan filter pencarian</p>
                </div>
            </div>

            <form method="GET" action="#katalogStok" class="flex flex-wrap items-center gap-2.5">
                <div class="relative flex-1 min-w-[220px]">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" name="search" value="<?= sanitize($search) ?>"
                        placeholder="Ketik nama komponen yang ingin dicek stoknya (misal: Panel, Inverter, Kabel)..."
                        class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:bg-white transition-all">
                </div>

                <select name="kategori" class="py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    <?php foreach (array_keys($katMeta) as $katOpt): ?>
                    <option value="<?= $katOpt ?>" <?= $kat === $katOpt ? 'selected' : '' ?>><?= $katOpt ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="status" class="py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700" onchange="this.form.submit()">
                    <option value="">Semua Kondisi Stok</option>
                    <option value="aman" <?= $status === 'aman' ? 'selected' : '' ?>>Stok Aman</option>
                    <option value="rendah" <?= $status === 'rendah' ? 'selected' : '' ?>>Stok Rendah</option>
                    <option value="kritis" <?= $status === 'kritis' ? 'selected' : '' ?>>Stok Kritis</option>
                    <option value="habis" <?= $status === 'habis' ? 'selected' : '' ?>>Stok Habis</option>
                </select>

                <button type="submit" class="btn-primary btn-sm"><i class="bi bi-funnel"></i> Cari Stok</button>
                <?php if ($search !== '' || $kat !== '' || $status !== ''): ?>
                <a href="<?= BASE_URL ?>/pages/komponen/status.php" class="btn-secondary btn-sm text-red-600">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="table-header">Kode SKU & Nama Komponen</th>
                        <th class="table-header">Kategori</th>
                        <th class="table-header">Stok Tersedia</th>
                        <th class="table-header">Batas Minimum</th>
                        <th class="table-header">Status Ketersediaan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($katalogKomponen)): ?>
                    <tr>
                        <td colspan="5" class="table-cell text-center py-12 text-slate-400">
                            Tidak ditemukan komponen yang cocok dengan pencarian Anda.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($katalogKomponen as $item):
                        [$cls, $label, $statusKey] = stokStatus((int)$item['stok_tersedia'], (int)$item['stok_minimum']);
                        $meta = $katMeta[$item['kategori']] ?? $katMeta['Lainnya'];
                        $isUrgent = in_array($statusKey, ['kritis', 'habis']);
                    ?>
                    <tr class="table-row">
                        <td class="table-cell">
                            <div class="flex items-center gap-3">
                                <span class="w-9 h-9 rounded-xl ring-1 <?= $meta['cls'] ?> flex items-center justify-center flex-shrink-0">
                                    <i class="bi <?= $meta['icon'] ?>"></i>
                                </span>
                                <div>
                                    <div class="font-bold text-slate-900 text-sm"><?= sanitize($item['nama_komponen']) ?></div>
                                    <div class="text-[11px] text-slate-400">SKU-<?= str_pad((string)$item['id'], 3, '0', STR_PAD_LEFT) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="table-cell whitespace-nowrap">
                            <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg">
                                <?= sanitize($item['kategori']) ?>
                            </span>
                        </td>
                        <td class="table-cell whitespace-nowrap">
                            <span class="text-base font-extrabold <?= $isUrgent ? 'text-red-600' : 'text-slate-900' ?>">
                                <?= number_format((int)$item['stok_tersedia']) ?>
                            </span>
                            <span class="text-xs text-slate-500 ml-0.5"><?= sanitize($item['satuan']) ?></span>
                        </td>
                        <td class="table-cell whitespace-nowrap text-xs text-slate-500">
                            <?= (int)$item['stok_minimum'] ?> <?= sanitize($item['satuan']) ?>
                        </td>
                        <td class="table-cell whitespace-nowrap">
                            <span class="badge-modern <?= $cls ?>"><?= $label ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
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
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 8 }
            }
        }
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>