<?php
// pages/komponen/index.php — ENTERPRISE INVENTORY MANAGEMENT
$pageTitle   = 'Komponen PLTS';
$currentPage = 'komponen';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$db           = db();
$search       = trim($_GET['search'] ?? '');
$kat          = trim($_GET['kategori'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$perPage      = 12;
$page         = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($page - 1) * $perPage;

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
if ($statusFilter === 'aman') {
    $where[] = 'stok_tersedia > stok_minimum * 1.5';
} elseif ($statusFilter === 'rendah') {
    $where[] = 'stok_tersedia > stok_minimum AND stok_tersedia <= stok_minimum * 1.5';
} elseif ($statusFilter === 'kritis') {
    $where[] = 'stok_tersedia > 0 AND stok_tersedia <= stok_minimum';
} elseif ($statusFilter === 'habis') {
    $where[] = 'stok_tersedia = 0';
}
$whereStr = implode(' AND ', $where);

$cntStmt = $db->prepare("SELECT COUNT(*) FROM komponen WHERE $whereStr");
$cntStmt->execute($params);
$totalRows  = (int)$cntStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $db->prepare("SELECT * FROM komponen WHERE $whereStr ORDER BY kategori ASC, nama_komponen ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$komponen = $stmt->fetchAll();

// Semua kategori + count
$kategoris = $db->query("
    SELECT kategori, COUNT(*) as total,
           SUM(CASE WHEN stok_tersedia <= stok_minimum THEN 1 ELSE 0 END) as kritis
    FROM komponen GROUP BY kategori ORDER BY kategori
")->fetchAll();

// Summary stok keseluruhan
$summary = $db->query("
    SELECT
        COUNT(*) as total_all,
        COALESCE(SUM(CASE WHEN stok_tersedia > stok_minimum * 1.5 THEN 1 ELSE 0 END), 0) as aman,
        COALESCE(SUM(CASE WHEN stok_tersedia > stok_minimum AND stok_tersedia <= stok_minimum * 1.5 THEN 1 ELSE 0 END), 0) as rendah,
        COALESCE(SUM(CASE WHEN stok_tersedia > 0 AND stok_tersedia <= stok_minimum THEN 1 ELSE 0 END), 0) as kritis,
        COALESCE(SUM(CASE WHEN stok_tersedia = 0 THEN 1 ELSE 0 END), 0) as habis
    FROM komponen
")->fetch();

// Pemetaan Ikon Vektor Profesional per Kategori
$katMeta = [
    'Panel Surya' => ['icon' => 'bi-sun-fill',            'cls' => 'bg-amber-50 text-amber-600 ring-amber-200/60'],
    'Inverter'    => ['icon' => 'bi-cpu-fill',            'cls' => 'bg-indigo-50 text-indigo-600 ring-indigo-200/60'],
    'Baterai'     => ['icon' => 'bi-battery-charging',    'cls' => 'bg-emerald-50 text-emerald-600 ring-emerald-200/60'],
    'Kabel'       => ['icon' => 'bi-bezier2',             'cls' => 'bg-sky-50 text-sky-600 ring-sky-200/60'],
    'Mounting'    => ['icon' => 'bi-layers-half',         'cls' => 'bg-slate-100 text-slate-700 ring-slate-200'],
    'Proteksi'    => ['icon' => 'bi-shield-fill-check',   'cls' => 'bg-rose-50 text-rose-600 ring-rose-200/60'],
    'Monitoring'  => ['icon' => 'bi-broadcast-pin',       'cls' => 'bg-violet-50 text-violet-600 ring-violet-200/60'],
    'Lainnya'     => ['icon' => 'bi-box-seam-fill',       'cls' => 'bg-teal-50 text-teal-600 ring-teal-200/60'],
];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header Halaman -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <div class="flex items-center gap-3.5">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-600 to-indigo-700 text-white flex items-center justify-center shadow-lg shadow-primary-600/25">
                <i class="bi bi-box-seam text-lg"></i>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Inventaris Komponen PLTS</h2>
                <p class="text-xs text-slate-500 mt-0.5">Manajemen stok perangkat, material instalasi, dan ambang batas minimum gudang</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Pengalih Tampilan Grid vs Tabel -->
            <div class="inline-flex bg-slate-200/70 p-1 rounded-xl">
                <button type="button" id="btnViewGrid" onclick="switchInventoryView('grid')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white text-slate-900 shadow-xs flex items-center gap-1.5">
                    <i class="bi bi-grid-3x3-gap"></i> Grid
                </button>
                <button type="button" id="btnViewTable" onclick="switchInventoryView('table')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900 flex items-center gap-1.5">
                    <i class="bi bi-table"></i> Tabel
                </button>
            </div>

            <?php if (isAdminOrGudang()): ?>
            <button type="button" onclick="openModalStok()" class="btn-secondary btn-sm">
                <i class="bi bi-box-arrow-in-down text-emerald-600"></i> <span class="hidden sm:inline">Stok Masuk</span>
            </button>
            <a href="<?= BASE_URL ?>/pages/komponen/create.php" class="btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Tambah Komponen
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Filter Cards (Klik untuk memfilter berdasarkan status stok) -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
        <?php
        $sCards = [
            ['key' => '',       'val' => (int)$summary['total_all'], 'label' => 'Semua Item',  'accent' => '#475569', 'icon' => 'bi-boxes',                   'cls' => 'bg-slate-100 text-slate-600'],
            ['key' => 'aman',   'val' => (int)$summary['aman'],      'label' => 'Stok Aman',   'accent' => '#10b981', 'icon' => 'bi-check-circle-fill',       'cls' => 'bg-emerald-50 text-emerald-600'],
            ['key' => 'rendah', 'val' => (int)$summary['rendah'],    'label' => 'Stok Rendah', 'accent' => '#f59e0b', 'icon' => 'bi-dash-circle-fill',        'cls' => 'bg-amber-50 text-amber-600'],
            ['key' => 'kritis', 'val' => (int)$summary['kritis'],    'label' => 'Stok Kritis', 'accent' => '#f97316', 'icon' => 'bi-exclamation-circle-fill', 'cls' => 'bg-orange-50 text-orange-600'],
            ['key' => 'habis',  'val' => (int)$summary['habis'],     'label' => 'Stok Habis',  'accent' => '#ef4444', 'icon' => 'bi-x-circle-fill',           'cls' => 'bg-red-50 text-red-600'],
        ];
        foreach ($sCards as $s):
            $isActive = ($statusFilter === $s['key']);
        ?>
        <button type="button" onclick="setStatusFilter('<?= $s['key'] ?>')"
            class="card p-4 text-left stat-card hover:shadow-md transition-all <?= $isActive ? 'ring-2 ring-primary-500 bg-primary-50/20' : '' ?>"
            style="--stat-accent: <?= $s['accent'] ?>">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider"><?= $s['label'] ?></p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1 leading-none"><?= $s['val'] ?></p>
                </div>
                <span class="w-9 h-9 rounded-xl <?= $s['cls'] ?> flex items-center justify-center">
                    <i class="bi <?= $s['icon'] ?> text-base"></i>
                </span>
            </div>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Filter Bar & Kategori Pills -->
    <div class="card p-4 mb-5">
        <form method="GET" id="filterForm" class="space-y-3.5">
            <input type="hidden" name="kategori" id="katInput" value="<?= sanitize($kat) ?>">
            <input type="hidden" name="status" id="statusInput" value="<?= sanitize($statusFilter) ?>">

            <div class="flex flex-wrap items-center gap-2.5">
                <div class="relative flex-1 min-w-[240px]">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" name="search" value="<?= sanitize($search) ?>"
                        placeholder="Cari nama komponen, spesifikasi, atau kategori..."
                        class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:bg-white transition-all">
                </div>
                <button type="submit" class="btn-primary py-2">
                    <i class="bi bi-funnel"></i> Cari
                </button>
                <?php if ($search !== '' || $kat !== '' || $statusFilter !== ''): ?>
                <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="btn-secondary py-2">
                    <i class="bi bi-x-lg"></i> Reset Filter
                </a>
                <?php endif; ?>
            </div>

            <!-- Filter Kategori dengan Vektor Ikon -->
            <div class="flex items-center gap-1.5 flex-wrap pt-1 border-t border-slate-100">
                <button type="button" onclick="setKategori('')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all border
                    <?= $kat === '' ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' ?>">
                    <i class="bi bi-grid-fill text-[11px]"></i> Semua Kategori
                    <span class="opacity-70">(<?= (int)$summary['total_all'] ?>)</span>
                </button>

                <?php foreach ($kategoris as $k):
                    $m = $katMeta[$k['kategori']] ?? $katMeta['Lainnya'];
                    $aktifKat = ($kat === $k['kategori']);
                ?>
                <button type="button" onclick="setKategori('<?= addslashes($k['kategori']) ?>')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all border relative
                    <?= $aktifKat ? 'bg-primary-600 text-white border-primary-600 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' ?>">
                    <i class="bi <?= $m['icon'] ?> <?= $aktifKat ? 'text-white' : 'text-primary-600' ?>"></i>
                    <span><?= sanitize($k['kategori']) ?></span>
                    <span class="opacity-70">(<?= $k['total'] ?>)</span>
                    <?php if ((int)$k['kritis'] > 0): ?>
                    <span class="ml-0.5 px-1.5 py-0.2 bg-red-500 text-white text-[10px] rounded-full font-extrabold" title="<?= $k['kritis'] ?> stok kritis/habis">
                        <?= $k['kritis'] ?>
                    </span>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>
        </form>
    </div>

    <?php if (empty($komponen)): ?>
    <!-- Empty State -->
    <div class="card py-16 px-6 text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl">
            <i class="bi bi-box-seam"></i>
        </div>
        <h3 class="text-base font-bold text-slate-800 mb-1">Komponen Tidak Ditemukan</h3>
        <p class="text-xs text-slate-400 max-w-sm mx-auto mb-4">Tidak ada item inventaris yang sesuai dengan kata kunci atau filter status yang Anda pilih.</p>
        <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="btn-secondary btn-sm inline-flex">
            <i class="bi bi-arrow-counterclockwise"></i> Tampilkan Semua Komponen
        </a>
    </div>
    <?php else: ?>

    <!-- ==========================================================
         VIEW 1: TAMPILAN GRID CARD PROFESIONAL
    =========================================================== -->
    <div id="inventoryGridView" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php foreach ($komponen as $k):
            [$cls, $label, $statusKey] = stokStatus((int)$k['stok_tersedia'], (int)$k['stok_minimum']);
            $targetAman = max(1, (int)ceil($k['stok_minimum'] * 1.5));
            $pct = min(100, (int)round(($k['stok_tersedia'] / $targetAman) * 100));
            $barColor = match($statusKey) {
                'habis'  => 'bg-slate-400',
                'kritis' => 'bg-red-500',
                'rendah' => 'bg-amber-500',
                default  => 'bg-emerald-500'
            };
            $meta = $katMeta[$k['kategori']] ?? $katMeta['Lainnya'];
            $isAlert = in_array($statusKey, ['kritis', 'habis'], true);
        ?>
        <div class="card p-5 flex flex-col justify-between border <?= $isAlert ? 'border-red-200/90' : 'border-slate-100' ?> card-hover">
            <div>
                <!-- Card Top: Category Icon & Status Badge -->
                <div class="flex items-start justify-between gap-2 mb-3.5">
                    <div class="flex items-center gap-2.5">
                        <span class="w-10 h-10 rounded-xl ring-1 <?= $meta['cls'] ?> flex items-center justify-center flex-shrink-0">
                            <i class="bi <?= $meta['icon'] ?> text-lg"></i>
                        </span>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <?= sanitize($k['kategori']) ?>
                        </span>
                    </div>
                    <span class="badge-modern <?= $cls ?>">
                        <?= $label ?>
                    </span>
                </div>

                <!-- Nama Komponen -->
                <h4 class="text-sm font-bold text-slate-900 leading-snug line-clamp-2 min-h-[40px]" title="<?= sanitize($k['nama_komponen']) ?>">
                    <?= sanitize($k['nama_komponen']) ?>
                </h4>

                <!-- Box Angka Stok & Inline Quick Edit -->
                <div class="mt-3 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Stok Tersedia</span>
                            <div class="flex items-baseline gap-1.5 mt-0.5" id="stokDisplayWrap_<?= $k['id'] ?>">
                                <span class="text-2xl font-extrabold <?= $isAlert ? 'text-red-600' : 'text-slate-900' ?>" id="stokDisplay_<?= $k['id'] ?>">
                                    <?= number_format((int)$k['stok_tersedia']) ?>
                                </span>
                                <span class="text-xs font-semibold text-slate-400"><?= sanitize($k['satuan']) ?></span>
                            </div>
                        </div>

                        <?php if (isAdminOrGudang()): ?>
                        <button type="button" onclick="toggleEdit(<?= $k['id'] ?>)"
                            class="w-8 h-8 rounded-lg bg-white border border-slate-200 hover:border-primary-400 text-slate-500 hover:text-primary-600 flex items-center justify-center transition-colors shadow-2xs"
                            title="Ubah angka stok secara cepat">
                            <i class="bi bi-pencil-square text-xs"></i>
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Form Inline Edit Stok -->
                    <div id="stokEdit_<?= $k['id'] ?>" class="hidden items-center gap-1.5 mt-2 pt-2 border-t border-slate-200/70">
                        <input type="number" id="stokInput_<?= $k['id'] ?>"
                            class="form-input py-1 px-2.5 text-sm font-bold text-center flex-1"
                            value="<?= (int)$k['stok_tersedia'] ?>" min="0">
                        <button type="button" onclick="saveStok(<?= $k['id'] ?>)"
                            class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-colors">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <button type="button" onclick="cancelEdit(<?= $k['id'] ?>)"
                            class="px-2.5 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-bold transition-colors">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Progress Bar terhadap Ambang Minimum -->
                    <div class="mt-3">
                        <div class="h-1.5 bg-slate-200/80 rounded-full overflow-hidden">
                            <div class="h-full <?= $barColor ?> rounded-full transition-all duration-500" style="width: <?= $pct ?>%"></div>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-400 mt-1.5">
                            <span>Batas Min: <strong class="text-slate-600"><?= (int)$k['stok_minimum'] ?> <?= sanitize($k['satuan']) ?></strong></span>
                            <span>ID #<?= $k['id'] ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Footer -->
            <?php if (isAdminOrGudang()): ?>
            <div class="flex items-center gap-1.5 pt-3.5 mt-3.5 border-t border-slate-100">
                <button type="button" onclick="openModalStok(<?= $k['id'] ?>)"
                    class="flex-1 btn-secondary btn-sm justify-center text-emerald-700 border-emerald-200 hover:bg-emerald-50">
                    <i class="bi bi-plus-circle-fill"></i> Restock
                </button>
                <a href="<?= BASE_URL ?>/pages/komponen/edit.php?id=<?= $k['id'] ?>"
                    class="btn-secondary btn-sm px-2.5" title="Edit Detail Komponen">
                    <i class="bi bi-sliders"></i>
                </a>
                <form method="POST" action="<?= BASE_URL ?>/api/komponen/delete.php" class="inline"
                    onsubmit="event.preventDefault(); confirmDelete(this, 'Hapus komponen <?= addslashes(sanitize($k['nama_komponen'])) ?>?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                    <button type="submit" class="btn-secondary btn-sm px-2.5 text-red-500 hover:bg-red-50" title="Hapus Komponen">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ==========================================================
         VIEW 2: TAMPILAN TABEL INVENTARIS PADAT
    =========================================================== -->
    <div id="inventoryTableView" class="card overflow-hidden hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="table-header">Nama Komponen</th>
                        <th class="table-header">Kategori</th>
                        <th class="table-header">Stok Tersedia</th>
                        <th class="table-header">Batas Minimum</th>
                        <th class="table-header">Status</th>
                        <?php if (isAdminOrGudang()): ?>
                        <th class="table-header text-right">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($komponen as $k):
                        [$cls, $label, $statusKey] = stokStatus((int)$k['stok_tersedia'], (int)$k['stok_minimum']);
                        $meta = $katMeta[$k['kategori']] ?? $katMeta['Lainnya'];
                    ?>
                    <tr class="table-row">
                        <td class="table-cell">
                            <div class="flex items-center gap-3">
                                <span class="w-9 h-9 rounded-xl ring-1 <?= $meta['cls'] ?> flex items-center justify-center flex-shrink-0">
                                    <i class="bi <?= $meta['icon'] ?>"></i>
                                </span>
                                <div>
                                    <div class="font-bold text-slate-800 text-sm"><?= sanitize($k['nama_komponen']) ?></div>
                                    <div class="text-[11px] text-slate-400">Kode #<?= $k['id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="table-cell">
                            <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg">
                                <?= sanitize($k['kategori']) ?>
                            </span>
                        </td>
                        <td class="table-cell">
                            <span class="text-sm font-extrabold text-slate-900"><?= number_format((int)$k['stok_tersedia']) ?></span>
                            <span class="text-xs text-slate-400 ml-0.5"><?= sanitize($k['satuan']) ?></span>
                        </td>
                        <td class="table-cell">
                            <span class="text-xs font-semibold text-slate-500"><?= number_format((int)$k['stok_minimum']) ?> <?= sanitize($k['satuan']) ?></span>
                        </td>
                        <td class="table-cell">
                            <span class="badge-modern <?= $cls ?>"><?= $label ?></span>
                        </td>
                        <?php if (isAdminOrGudang()): ?>
                        <td class="table-cell text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                <button type="button" onclick="openModalStok(<?= $k['id'] ?>)"
                                    class="btn-secondary btn-sm text-emerald-700 hover:bg-emerald-50">
                                    <i class="bi bi-plus-circle"></i> Restock
                                </button>
                                <a href="<?= BASE_URL ?>/pages/komponen/edit.php?id=<?= $k['id'] ?>" class="btn-secondary btn-sm">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form method="POST" action="<?= BASE_URL ?>/api/komponen/delete.php" class="inline"
                                    onsubmit="event.preventDefault(); confirmDelete(this, 'Hapus komponen <?= addslashes(sanitize($k['nama_komponen'])) ?>?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                    <button type="submit" class="btn-secondary btn-sm text-red-500 hover:bg-red-50">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-between mt-5">
        <p class="text-xs text-slate-400">
            Menampilkan <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalRows) ?> dari <?= $totalRows ?> komponen
        </p>
        <div class="flex gap-1.5">
            <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
            <a href="?page=<?= $pg ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kat) ?>&status=<?= urlencode($statusFilter) ?>"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition-colors
                <?= $pg === $page ? 'bg-primary-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
                <?= $pg ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (isAdminOrGudang()): ?>
<!-- MODAL STOK MASUK (RESTOCK CEPAT) -->
<div id="modalStokMasuk" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden items-center justify-center p-4"
     onclick="if(event.target===this) closeModalStok()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100" onclick="event.stopPropagation()">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="bi bi-box-arrow-in-down text-lg"></i>
                </span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Pencatatan Stok Masuk</h3>
                    <p class="text-xs text-slate-400">Tambahkan stok komponen yang baru diterima gudang</p>
                </div>
            </div>
            <button type="button" onclick="closeModalStok()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/api/komponen/stok-masuk.php" class="p-6 space-y-4">
            <?= csrfField() ?>
            <div>
                <label class="form-label">Pilih Komponen <span class="text-red-500">*</span></label>
                <select name="komponen_id" class="form-select" required id="modalKomponenSel" onchange="updateModalStokInfo(this)">
                    <option value="">-- Pilih Komponen PLTS --</option>
                    <?php
                    $allKomponen = $db->query("SELECT * FROM komponen ORDER BY nama_komponen")->fetchAll();
                    foreach ($allKomponen as $ak): ?>
                    <option value="<?= $ak['id'] ?>"
                        data-stok="<?= (int)$ak['stok_tersedia'] ?>"
                        data-satuan="<?= sanitize($ak['satuan']) ?>">
                        <?= sanitize($ak['nama_komponen']) ?> (Sisa: <?= (int)$ak['stok_tersedia'] ?> <?= sanitize($ak['satuan']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="modalStokInfo" class="hidden bg-emerald-50/70 border border-emerald-200 rounded-xl px-4 py-2.5 flex items-center justify-between text-xs">
                <span class="text-emerald-800 font-medium">Stok tersedia saat ini:</span>
                <strong id="modalStokCurrent" class="text-emerald-900 text-sm"></strong>
            </div>

            <div>
                <label class="form-label">Jumlah Masuk <span class="text-red-500">*</span></label>
                <div class="flex items-center gap-2.5">
                    <div class="flex-1 flex border border-slate-200 rounded-xl overflow-hidden">
                        <button type="button" onclick="adjustQty(-5)" class="px-3.5 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold text-xs">-5</button>
                        <button type="button" onclick="adjustQty(-1)" class="px-3 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold border-l border-slate-200">−</button>
                        <input type="number" name="jumlah_masuk" id="qtyInput"
                            class="flex-1 text-center border-x border-slate-200 py-2 text-sm font-extrabold focus:outline-none"
                            value="10" min="1" required>
                        <button type="button" onclick="adjustQty(1)" class="px-3 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold border-r border-slate-200">+</button>
                        <button type="button" onclick="adjustQty(5)" class="px-3.5 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold text-xs">+5</button>
                    </div>
                    <span class="text-xs font-bold text-slate-500 min-w-[40px]" id="modalSatuan">unit</span>
                </div>
            </div>

            <div>
                <label class="form-label">Catatan / Referensi PO <span class="text-slate-400 font-normal">(Opsional)</span></label>
                <input type="text" name="keterangan" class="form-input" placeholder="Contoh: Pengadaan PO-2026/10 dari Supplier">
            </div>

            <div class="flex gap-2.5 pt-2">
                <button type="button" onclick="closeModalStok()" class="btn-secondary flex-1 justify-center">Batal</button>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="bi bi-check2-circle"></i> Tambahkan Stok
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
const originalStok = {};

function setKategori(kat) {
    document.getElementById('katInput').value = kat;
    document.getElementById('filterForm').submit();
}

function setStatusFilter(status) {
    document.getElementById('statusInput').value = status;
    document.getElementById('filterForm').submit();
}

function switchInventoryView(mode) {
    const isGrid = (mode === 'grid');
    const gridEl = document.getElementById('inventoryGridView');
    const tblEl  = document.getElementById('inventoryTableView');
    if (gridEl && tblEl) {
        gridEl.classList.toggle('hidden', !isGrid);
        tblEl.classList.toggle('hidden', isGrid);
    }
    document.getElementById('btnViewGrid').className = isGrid
        ? 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white text-slate-900 shadow-xs flex items-center gap-1.5'
        : 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900 flex items-center gap-1.5';
    document.getElementById('btnViewTable').className = !isGrid
        ? 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white text-slate-900 shadow-xs flex items-center gap-1.5'
        : 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900 flex items-center gap-1.5';
    localStorage.setItem('plts_komponen_view', mode);
}

function toggleEdit(id) {
    const editEl = document.getElementById('stokEdit_' + id);
    const input  = document.getElementById('stokInput_' + id);
    originalStok[id] = parseInt(input.value) || 0;
    editEl.classList.toggle('hidden');
    editEl.classList.toggle('flex');
    if (!editEl.classList.contains('hidden')) {
        input.focus();
        input.select();
    }
}

function cancelEdit(id) {
    const editEl = document.getElementById('stokEdit_' + id);
    editEl.classList.add('hidden');
    editEl.classList.remove('flex');
    document.getElementById('stokInput_' + id).value = originalStok[id] || 0;
}

function saveStok(id) {
    const input = document.getElementById('stokInput_' + id);
    const val   = parseInt(input.value);
    if (isNaN(val) || val < 0) {
        Toast?.show('Jumlah stok tidak valid', 'error');
        return;
    }

    const fd = new FormData();
    fd.append('id', id);
    fd.append('stok_tersedia', val);
    fd.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');

    fetch('<?= BASE_URL ?>/api/komponen/update-stok.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('stokDisplay_' + id).textContent = val.toLocaleString('id-ID');
                cancelEdit(id);
                Toast?.show('Stok berhasil diperbarui', 'success');
                setTimeout(() => location.reload(), 900);
            } else {
                Toast?.show(data.message || 'Gagal memperbarui stok', 'error');
            }
        })
        .catch(() => Toast?.show('Koneksi gagal', 'error'));
}

function openModalStok(preselectId = null) {
    const modal = document.getElementById('modalStokMasuk');
    const sel   = document.getElementById('modalKomponenSel');
    if (preselectId && sel) {
        sel.value = preselectId;
        updateModalStokInfo(sel);
    }
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeModalStok() {
    const modal = document.getElementById('modalStokMasuk');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function updateModalStokInfo(sel) {
    const opt    = sel.options[sel.selectedIndex];
    const stok   = opt?.dataset.stok || 0;
    const satuan = opt?.dataset.satuan || 'unit';
    const info   = document.getElementById('modalStokInfo');
    info.classList.toggle('hidden', !sel.value);
    document.getElementById('modalStokCurrent').textContent = `${stok} ${satuan}`;
    document.getElementById('modalSatuan').textContent = satuan;
}

function adjustQty(delta) {
    const input = document.getElementById('qtyInput');
    input.value = Math.max(1, (parseInt(input.value) || 0) + delta);
}

document.addEventListener('DOMContentLoaded', () => {
    const savedMode = localStorage.getItem('plts_komponen_view') || 'grid';
    switchInventoryView(savedMode);
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>