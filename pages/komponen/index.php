<?php
// pages/komponen/index.php
$pageTitle   = 'Komponen PLTS';
$currentPage = 'komponen';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$db      = db();
$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search  = $_GET['search'] ?? '';
$kat     = $_GET['kategori'] ?? '';

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = 'nama_komponen LIKE ?'; $params[] = "%$search%"; }
if ($kat)    { $where[] = 'kategori = ?';          $params[] = $kat; }
$whereStr = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM komponen WHERE $whereStr");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("SELECT * FROM komponen WHERE $whereStr ORDER BY kategori, nama_komponen LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$komponen = $stmt->fetchAll();

$kategoris = $db->query("SELECT DISTINCT kategori FROM komponen ORDER BY kategori")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-bold text-slate-900 tracking-tight">Komponen PLTS</h2>
        <p class="text-[13px] text-slate-500 mt-1"><?= $totalRows ?> total komponen</p>
    </div>
    <?php if (isAdminOrGudang()): ?>
    <a href="<?= BASE_URL ?>/pages/komponen/create.php" class="btn-primary">
        <i class="bi bi-plus-lg"></i> Tambah Komponen
    </a>
    <?php endif; ?>
</div>

<!-- Filter -->
<div class="card p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3">
        <div class="flex-1 min-w-48">
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="<?= sanitize($search) ?>"
                    placeholder="Cari nama komponen..."
                    class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-4 focus:ring-primary-500/15 focus:border-primary-500 transition-all">
            </div>
        </div>
        <select name="kategori" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
            <option value="">Semua Kategori</option>
            <?php foreach ($kategoris as $k): ?>
            <option value="<?= sanitize($k) ?>" <?= $kat === $k ? 'selected' : '' ?>><?= sanitize($k) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary"><i class="bi bi-funnel"></i> Filter</button>
        <?php if ($search || $kat): ?>
        <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="btn-secondary"><i class="bi bi-x-lg"></i> Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Grid Komponen -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php if (empty($komponen)): ?>
    <div class="col-span-full card px-5 py-16 text-center">
        <span class="empty-icon mb-3 !w-16 !h-16"><i class="bi bi-grid-3x3-gap"></i></span>
        <p class="text-slate-400">Belum ada data komponen</p>
    </div>
    <?php else: ?>
    <?php foreach ($komponen as $k):
        [$cls, $label, $statusKey] = stokStatus($k['stok_tersedia'], $k['stok_minimum']);
        $bgCard = $statusKey === 'kritis' || $statusKey === 'habis' ? 'border-red-200 bg-red-50/30' : 'border-slate-200/70';
    ?>
    <div class="card card-hover p-5 border <?= $bgCard ?>">
        <div class="flex items-start justify-between mb-3">
            <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center">
                <i class="bi bi-cpu text-slate-500"></i>
            </div>
            <span class="badge-modern inline-flex items-center rounded-full text-xs font-semibold <?= $cls ?>"><?= $label ?></span>
        </div>
        <h4 class="text-sm font-semibold text-slate-800 mb-1 leading-tight"><?= sanitize($k['nama_komponen']) ?></h4>
        <p class="text-xs text-slate-400 mb-4"><?= sanitize($k['kategori']) ?></p>

        <div class="flex items-end justify-between">
            <div>
                <p class="text-2xl font-bold <?= $statusKey === 'kritis' || $statusKey === 'habis' ? 'text-red-600' : 'text-slate-800' ?>">
                    <?= $k['stok_tersedia'] ?>
                </p>
                <p class="text-xs text-slate-400"><?= sanitize($k['satuan']) ?> tersedia</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-slate-400">Min: <?= $k['stok_minimum'] ?> <?= sanitize($k['satuan']) ?></p>
                <?php if (isAdminOrGudang()): ?>
                <div class="flex gap-1 mt-2">
                    <a href="<?= BASE_URL ?>/pages/komponen/edit.php?id=<?= $k['id'] ?>"
                        class="p-1.5 rounded-lg hover:bg-amber-50 text-slate-400 hover:text-amber-600 transition-colors">
                        <i class="bi bi-pencil text-xs"></i>
                    </a>
                    <form method="POST" action="<?= BASE_URL ?>/api/komponen/delete.php" class="inline"
                        onsubmit="return confirm('Hapus komponen ini?')">
                        <input type="hidden" name="id" value="<?= $k['id'] ?>">
                        <button class="p-1.5 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-600 transition-colors">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Progress stok -->
        <?php
        $pct = $k['stok_minimum'] > 0 ? min(100, round($k['stok_tersedia'] / $k['stok_minimum'] * 100)) : 100;
        $barColor = $statusKey === 'habis' ? 'bg-gray-400' : ($statusKey === 'kritis' ? 'bg-red-500' : ($statusKey === 'rendah' ? 'bg-yellow-500' : 'bg-green-500'));
        ?>
        <div class="mt-3 h-1.5 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full <?= $barColor ?> rounded-full transition-all" style="width:<?= min(100,$pct) ?>%"></div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
<div class="flex items-center justify-between mt-4">
    <p class="text-xs text-slate-400">Menampilkan <?= $offset+1 ?>–<?= min($offset+$perPage,$totalRows) ?> dari <?= $totalRows ?></p>
    <div class="flex gap-1">
        <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
        <a href="?page=<?= $pg ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kat) ?>"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-xs transition-colors
            <?= $pg === $page ? 'bg-primary-600 text-white shadow-sm shadow-primary-600/30' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            <?= $pg ?>
        </a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>