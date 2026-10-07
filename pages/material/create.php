<?php
// pages/material/create.php
$pageTitle   = 'Catat Penggunaan Material';
$currentPage = 'material';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','gudang']);

$db       = db();
$proyeks  = $db->query("SELECT * FROM proyek WHERE status IN ('survei','perencanaan','instalasi') ORDER BY nama_proyek")->fetchAll();
$komponen = $db->query("SELECT * FROM komponen WHERE stok_tersedia > 0 ORDER BY nama_komponen")->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/material/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-bold text-slate-900 tracking-tight">Catat Penggunaan Material</h2>
            <p class="text-sm text-slate-400">Stok otomatis berkurang setelah dicatat</p>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/material/store.php" id="formMaterial">
            <div class="space-y-5">

                <div>
                    <label class="form-label">Proyek <span class="text-red-500">*</span></label>
                    <select name="proyek_id" class="form-select" required>
                        <option value="">-- Pilih Proyek --</option>
                        <?php foreach ($proyeks as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= sanitize($p['nama_proyek']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Komponen <span class="text-red-500">*</span></label>
                    <select name="komponen_id" class="form-select" id="komponenSel" required onchange="updateStokInfo(this)">
                        <option value="">-- Pilih Komponen --</option>
                        <?php foreach ($komponen as $k): ?>
                        <option value="<?= $k['id'] ?>"
                            data-stok="<?= $k['stok_tersedia'] ?>"
                            data-satuan="<?= sanitize($k['satuan']) ?>"
                            data-min="<?= $k['stok_minimum'] ?>">
                            <?= sanitize($k['nama_komponen']) ?> — Stok: <?= $k['stok_tersedia'] ?> <?= sanitize($k['satuan']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <!-- Info Stok Real-time -->
                    <div id="stokInfo" class="hidden mt-2 p-3 rounded-lg border text-sm" id="stokBox"></div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Jumlah Digunakan <span class="text-red-500">*</span></label>
                        <div class="flex">
                            <input type="number" name="jumlah_digunakan" id="jumlahInput" class="form-input rounded-r-none" value="1" min="1" required>
                            <span class="px-3 border border-l-0 border-slate-200 bg-slate-50 rounded-r-lg flex items-center text-sm text-slate-500" id="satuanSpan">unit</span>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Tanggal <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_penggunaan" class="form-input" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div>
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="keterangan" class="form-input" placeholder="Contoh: Untuk pemasangan panel blok A">
                </div>

                <div class="flex gap-3 pt-2">
                    <a href="<?= BASE_URL ?>/pages/material/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                    <button type="submit" class="btn-primary flex-1 justify-center" id="submitBtn">
                        <i class="bi bi-save"></i> Simpan Catatan
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabel stok saat ini -->
    <div class="card mt-4 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-800 tracking-tight">Stok Komponen Tersedia</h3>
        </div>
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="table-header">Komponen</th>
                    <th class="table-header">Stok</th>
                    <th class="table-header">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($komponen as $k):
                    [$cls, $label] = stokStatus($k['stok_tersedia'], $k['stok_minimum']);
                ?>
                <tr class="table-row">
                    <td class="table-cell">
                        <span class="font-medium text-sm"><?= sanitize($k['nama_komponen']) ?></span>
                        <span class="text-xs text-slate-400 ml-1">(<?= sanitize($k['kategori']) ?>)</span>
                    </td>
                    <td class="table-cell"><span class="font-semibold"><?= $k['stok_tersedia'] ?></span> <span class="text-xs text-slate-400"><?= sanitize($k['satuan']) ?></span></td>
                    <td class="table-cell"><span class="badge-modern inline-flex items-center rounded-full text-xs font-semibold <?= $cls ?>"><?= $label ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function updateStokInfo(sel) {
    const opt    = sel.options[sel.selectedIndex];
    const stok   = parseInt(opt.dataset.stok   || 0);
    const satuan = opt.dataset.satuan || 'unit';
    const min    = parseInt(opt.dataset.min    || 0);
    const info   = document.getElementById('stokInfo');
    const span   = document.getElementById('satuanSpan');
    const input  = document.getElementById('jumlahInput');

    span.textContent = satuan;
    input.max        = stok;

    if (sel.value) {
        info.classList.remove('hidden');
        const isKritis = stok <= min;
        info.className = `mt-2 p-3 rounded-lg border text-sm flex items-center gap-2 ${isKritis ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'}`;
        info.innerHTML = `<i class="bi ${isKritis ? 'bi-exclamation-triangle' : 'bi-check-circle'}"></i>
            Stok tersedia: <strong>${stok} ${satuan}</strong>${isKritis ? ' — Stok sudah kritis!' : ' — Stok aman'}`;
    } else {
        info.classList.add('hidden');
    }
}

document.getElementById('formMaterial').addEventListener('submit', function(e) {
    const sel   = document.getElementById('komponenSel');
    const stok  = parseInt(sel.options[sel.selectedIndex].dataset.stok || 0);
    const jml   = parseInt(document.getElementById('jumlahInput').value || 0);
    if (jml > stok) {
        e.preventDefault();
        alert(`Jumlah (${jml}) melebihi stok tersedia (${stok})!`);
        return;
    }
    document.getElementById('submitBtn').innerHTML = '<i class="bi bi-arrow-repeat"></i> Menyimpan...';
    document.getElementById('submitBtn').disabled  = true;
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>