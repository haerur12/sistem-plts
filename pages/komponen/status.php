<?php
$pageTitle   = 'Status Material';
$currentPage = 'status-material';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$komponen = db()->query("SELECT * FROM komponen ORDER BY kategori, nama_komponen")->fetchAll();

$byKategori = [];
foreach ($komponen as $k) {
    $byKategori[$k['kategori']][] = $k;
}

$totalKomponen = count($komponen);
$stokAman   = count(array_filter($komponen, fn($k) => stokStatus($k['stok_tersedia'],$k['stok_minimum'])[2]==='aman'));
$stokRendah = count(array_filter($komponen, fn($k) => stokStatus($k['stok_tersedia'],$k['stok_minimum'])[2]==='rendah'));
$stokKritis = count(array_filter($komponen, fn($k) => stokStatus($k['stok_tersedia'],$k['stok_minimum'])[2]==='kritis'));
$stokHabis  = count(array_filter($komponen, fn($k) => stokStatus($k['stok_tersedia'],$k['stok_minimum'])[2]==='habis'));

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">Status Ketersediaan Material</h2>
        <p class="text-sm text-slate-400 mt-0.5">Pantau stok komponen PLTS secara real-time</p>
    </div>
    <?php if (isAdminOrGudang()): ?>
    <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="btn-secondary">
        <i class="bi bi-gear"></i> Kelola Komponen
    </a>
    <?php endif; ?>
</div>

<!-- Stat Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <?php
    $cards = [
        ['val'=>$stokAman,   'label'=>'Stok Aman',   'bg'=>'bg-green-500',  'light'=>'bg-green-50',  'text'=>'text-green-700',  'icon'=>'bi-check-circle-fill'],
        ['val'=>$stokRendah, 'label'=>'Stok Rendah', 'bg'=>'bg-amber-500',  'light'=>'bg-amber-50',  'text'=>'text-amber-700',  'icon'=>'bi-exclamation-circle'],
        ['val'=>$stokKritis, 'label'=>'Stok Kritis', 'bg'=>'bg-red-500',    'light'=>'bg-red-50',    'text'=>'text-red-700',    'icon'=>'bi-exclamation-triangle-fill'],
        ['val'=>$stokHabis,  'label'=>'Stok Habis',  'bg'=>'bg-slate-400',  'light'=>'bg-slate-50',  'text'=>'text-slate-600',  'icon'=>'bi-x-circle-fill'],
    ];
    foreach ($cards as $c): ?>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 <?= $c['bg'] ?> rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="bi <?= $c['icon'] ?> text-white"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-800"><?= $c['val'] ?></div>
            <div class="text-xs <?= $c['text'] ?>"><?= $c['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Per Kategori -->
<?php foreach ($byKategori as $kategori => $items): ?>
<div class="card overflow-hidden mb-4">
    <div class="px-5 py-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
            <i class="bi bi-tag text-primary-600"></i>
            <?= sanitize($kategori) ?>
        </h3>
        <span class="text-xs text-slate-400"><?= count($items) ?> item</span>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-slate-50">
        <?php foreach ($items as $k):
            [$cls, $label, $statusKey] = stokStatus($k['stok_tersedia'], $k['stok_minimum']);
            $pct = $k['stok_minimum'] > 0 ? min(100, round($k['stok_tersedia'] / $k['stok_minimum'] * 100)) : 100;
            $barColor = match($statusKey) {
                'habis'  => 'bg-slate-300',
                'kritis' => 'bg-red-500',
                'rendah' => 'bg-amber-500',
                default  => 'bg-green-500'
            };
        ?>
        <div class="px-5 py-4">
            <div class="flex items-start justify-between mb-2">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 leading-tight"><?= sanitize($k['nama_komponen']) ?></p>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ml-2 flex-shrink-0 <?= $cls ?>"><?= $label ?></span>
            </div>
            <div class="flex items-end justify-between mb-2">
                <div>
                    <span class="text-2xl font-bold <?= $statusKey==='kritis'||$statusKey==='habis' ? 'text-red-600' : 'text-slate-800' ?>">
                        <?= $k['stok_tersedia'] ?>
                    </span>
                    <span class="text-xs text-slate-400 ml-1"><?= sanitize($k['satuan']) ?></span>
                </div>
                <div class="text-right text-xs text-slate-400">
                    Min: <?= $k['stok_minimum'] ?> <?= sanitize($k['satuan']) ?>
                </div>
            </div>
            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full <?= $barColor ?> rounded-full transition-all" style="width:<?= min(100,$pct) ?>%"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php if (empty($komponen)): ?>
<div class="card px-5 py-16 text-center">
    <i class="bi bi-boxes text-5xl text-slate-200 block mb-3"></i>
    <p class="text-slate-400">Belum ada data komponen</p>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>