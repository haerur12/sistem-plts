<?php
// pages/komponen/create.php — FORM TAMBAH SKU KOMPONEN PLTS V2
$pageTitle   = 'Tambah Komponen PLTS';
$currentPage = 'komponen';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'gudang']);

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
        <span class="current">Tambah SKU Baru</span>
    </nav>

    <!-- Header -->
    <div class="flex items-center gap-3.5 mb-5">
        <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-colors shadow-2xs">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Tambah SKU Komponen PLTS</h2>
            <p class="text-xs text-slate-500 mt-0.5">Daftarkan master material baru beserta ambang batas stok minimum gudang</p>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/komponen/store.php" class="space-y-5">
            <?= csrfField() ?>

            <!-- Nama Komponen -->
            <div>
                <label class="form-label">Nama & Spesifikasi Komponen <span class="text-red-500">*</span></label>
                <input type="text" name="nama_komponen" class="form-input"
                       placeholder="Contoh: Panel Surya 550Wp Monocrystalline Tier-1" required autofocus>
                <p class="text-[11px] text-slate-400 mt-1">Sertakan kapasitas/ukuran (seperti Wp, kW, mm², atau Ampere) agar mudah dicari.</p>
            </div>

            <!-- Pilihan Kategori Visual -->
            <div>
                <label class="form-label">Kategori Komponen <span class="text-red-500">*</span></label>
                <input type="hidden" name="kategori" id="selectedKategori" value="Panel Surya" required>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mt-1.5">
                    <?php foreach ($katList as $katName => $meta): ?>
                    <button type="button" onclick="pilihKategori('<?= $katName ?>', this)"
                        class="kat-card-btn p-3 rounded-xl border text-left transition-all <?= $katName === 'Panel Surya' ? 'border-primary-600 bg-primary-50/50 ring-2 ring-primary-500/20' : 'border-slate-200 bg-white hover:bg-slate-50' ?>">
                        <i class="bi <?= $meta['icon'] ?> text-base text-primary-600 block mb-1"></i>
                        <div class="text-xs font-bold text-slate-800 truncate"><?= $katName ?></div>
                        <div class="text-[10px] text-slate-400 truncate"><?= $meta['desc'] ?></div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Satuan, Stok Awal & Stok Minimum -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Satuan Ukur <span class="text-red-500">*</span></label>
                    <select name="satuan" id="satuanInput" class="form-select" required onchange="hitungPreviewStok()">
                        <?php foreach (['unit','buah','meter','set','rol','lembar','kg'] as $s): ?>
                        <option value="<?= $s ?>"><?= ucfirst($s) ?> (<?= $s ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Stok Awal Tersedia <span class="text-red-500">*</span></label>
                    <input type="number" name="stok_tersedia" id="stokAwalInput" class="form-input font-bold"
                           value="20" min="0" required oninput="hitungPreviewStok()">
                </div>

                <div>
                    <label class="form-label">Batas Stok Minimum <span class="text-red-500">*</span></label>
                    <input type="number" name="stok_minimum" id="stokMinInput" class="form-input font-bold"
                           value="5" min="0" required oninput="hitungPreviewStok()">
                </div>
            </div>

            <!-- Live Simulasi Status Stok -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-4">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Simulasi Indikator Stok</span>
                    <p class="text-xs text-slate-600 mt-0.5" id="simulasiText">
                        Notifikasi peringatan otomatis dikirim jika stok menyentuh <strong>≤ 5 unit</strong>.
                    </p>
                </div>
                <span id="simulasiBadge" class="badge-modern bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 flex-shrink-0">
                    Stok Aman
                </span>
            </div>

            <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
                <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="bi bi-check-lg"></i> Simpan Komponen Baru
                </button>
            </div>
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

function hitungPreviewStok() {
    const stok = parseInt(document.getElementById('stokAwalInput').value) || 0;
    const min  = parseInt(document.getElementById('stokMinInput').value) || 0;
    const sat  = document.getElementById('satuanInput').value;
    const badge = document.getElementById('simulasiBadge');
    const text  = document.getElementById('simulasiText');

    text.innerHTML = `Notifikasi peringatan otomatis dikirim jika stok menyentuh <strong>≤ ${min} ${sat}</strong>.`;

    if (stok <= 0) {
        badge.className = 'badge-modern bg-slate-100 text-slate-600 ring-1 ring-slate-300 flex-shrink-0';
        badge.textContent = 'Habis';
    } else if (stok <= min) {
        badge.className = 'badge-modern bg-red-50 text-red-700 ring-1 ring-red-200 flex-shrink-0';
        badge.textContent = 'Stok Kritis';
    } else if (stok <= min * 1.5) {
        badge.className = 'badge-modern bg-amber-50 text-amber-700 ring-1 ring-amber-200 flex-shrink-0';
        badge.textContent = 'Stok Rendah';
    } else {
        badge.className = 'badge-modern bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 flex-shrink-0';
        badge.textContent = 'Stok Aman';
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>