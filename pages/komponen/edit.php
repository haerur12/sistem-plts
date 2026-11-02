<?php
// pages/komponen/edit.php — FORM EDIT SKU KOMPONEN PLTS V2
$pageTitle   = 'Edit Komponen PLTS';
$currentPage = 'komponen';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'gudang']);

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM komponen WHERE id = ?");
$stmt->execute([$id]);
$k = $stmt->fetch();

if (!$k) {
    setFlash('error', 'Komponen tidak ditemukan.');
    redirect(BASE_URL . '/pages/komponen/index.php');
}

$katList = [
    'Panel Surya' => ['icon' => 'bi-sun-fill',          'desc' => 'PV Module Mono/Poly'],
    'Inverter'    => ['icon' => 'bi-cpu-fill',          'desc' => 'On-Grid / Hybrid / Off-Grid'],
    'Baterai'     => ['icon' => 'bi-battery-charging',  'desc' => 'LiFePO4 / VRLA Storage'],
    'Kabel'       => ['icon' => 'bi-bezier2',           'desc' => 'PV1-F DC / NYY AC / Ground'],
    'Mounting'    => ['icon' => 'bi-layers-half',       'desc' => 'Rail, Clamp, L-Feet, Bracket'],
    'Proteksi'    => ['icon' => 'bi-shield-fill-check', 'desc' => 'MCB, SPD, Fuse, Combiner'],
    'Monitoring'  => ['icon' => 'bi-broadcast-pin',     'desc' => 'Smart Meter, Dongle, Logger'],
    'Lainnya'     => ['icon' => 'bi-box-seam-fill',     'desc' => 'MC4, Aksesoris, Consumable'],
];

[$clsBadge, $lblBadge] = stokStatus((int)$k['stok_tersedia'], (int)$k['stok_minimum']);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <a href="<?= BASE_URL ?>/pages/komponen/index.php">Inventaris Komponen</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Edit SKU-<?= str_pad((string)$k['id'], 3, '0', STR_PAD_LEFT) ?></span>
    </nav>

    <!-- Header -->
    <div class="flex items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-3.5">
            <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-colors shadow-2xs">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Edit SKU-<?= str_pad((string)$k['id'], 3, '0', STR_PAD_LEFT) ?></h2>
                <p class="text-xs text-slate-500 mt-0.5"><?= sanitize($k['nama_komponen']) ?></p>
            </div>
        </div>
        <span class="badge-modern <?= $clsBadge ?>"><?= $lblBadge ?></span>
    </div>

    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/komponen/update.php" class="space-y-5">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $k['id'] ?>">

            <div>
                <label class="form-label">Nama & Spesifikasi Komponen <span class="text-red-500">*</span></label>
                <input type="text" name="nama_komponen" class="form-input" value="<?= sanitize($k['nama_komponen']) ?>" required>
            </div>

            <div>
                <label class="form-label">Kategori Komponen <span class="text-red-500">*</span></label>
                <input type="hidden" name="kategori" id="selectedKategori" value="<?= sanitize($k['kategori']) ?>" required>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mt-1.5">
                    <?php foreach ($katList as $katName => $meta):
                        $isSel = ($k['kategori'] === $katName);
                    ?>
                    <button type="button" onclick="pilihKategori('<?= $katName ?>', this)"
                        class="kat-card-btn p-3 rounded-xl border text-left transition-all <?= $isSel ? 'border-primary-600 bg-primary-50/50 ring-2 ring-primary-500/20' : 'border-slate-200 bg-white hover:bg-slate-50' ?>">
                        <i class="bi <?= $meta['icon'] ?> text-base text-primary-600 block mb-1"></i>
                        <div class="text-xs font-bold text-slate-800 truncate"><?= $katName ?></div>
                        <div class="text-[10px] text-slate-400 truncate"><?= $meta['desc'] ?></div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Satuan Ukur <span class="text-red-500">*</span></label>
                    <select name="satuan" class="form-select" required>
                        <?php foreach (['unit','buah','meter','set','rol','lembar','kg'] as $s): ?>
                        <option value="<?= $s ?>" <?= $k['satuan'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?> (<?= $s ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Stok Tersedia <span class="text-red-500">*</span></label>
                    <input type="number" name="stok_tersedia" class="form-input font-bold" value="<?= (int)$k['stok_tersedia'] ?>" min="0" required>
                </div>

                <div>
                    <label class="form-label">Batas Stok Minimum <span class="text-red-500">*</span></label>
                    <input type="number" name="stok_minimum" class="form-input font-bold" value="<?= (int)$k['stok_minimum'] ?>" min="0" required>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 pt-4 border-t border-slate-100">
                <button type="button"
                        onclick="confirmDelete('formHapusKomponen', 'Hapus komponen <?= addslashes(sanitize($k['nama_komponen'])) ?>?')"
                        class="btn-secondary text-red-600 hover:bg-red-50">
                    <i class="bi bi-trash"></i> Hapus SKU
                </button>

                <div class="flex gap-2">
                    <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-primary">
                        <i class="bi bi-check-lg"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>

        <form method="POST" action="<?= BASE_URL ?>/api/komponen/delete.php" id="formHapusKomponen" class="hidden">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $k['id'] ?>">
        </form>
    </div>
</div>

<script>
function pilihKategori(nama, btnEl) {
    document.getElementById('selectedKategori').value = nama;
    document.querySelectorAll('.kat-card-btn').forEach(b => {
        b.className = 'kat-card-btn p-3 rounded-xl border text-left transition-all border-slate-200 bg-white hover:bg-slate-50';
    });
    btnEl.className = 'kat-card-btn p-3 rounded-xl border text-left transition-all border-primary-600 bg-primary-50/50 ring-2 ring-primary-500/20';
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>