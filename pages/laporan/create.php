<?php
// pages/laporan/create.php
$pageTitle   = 'Unggah Laporan';
$currentPage = 'laporan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek','teknisi_lapangan']);

$db   = db();
$user = currentUser();

// Jika teknisi, hanya tampilkan proyek yang berstatus aktif
if (isTeknisi()) {
    $proyeks = $db->query("SELECT * FROM proyek WHERE status IN ('survei','perencanaan','instalasi') ORDER BY nama_proyek ASC")->fetchAll();
} else {
    $proyeks = $db->query("SELECT * FROM proyek WHERE status IN ('survei','perencanaan','instalasi') ORDER BY nama_proyek ASC")->fetchAll();
}

$selectedProyek = (int)($_GET['proyek_id'] ?? 0);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/laporan/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-bold text-slate-900 tracking-tight">Unggah Laporan Lapangan</h2>
            <p class="text-sm text-slate-400">Dokumentasi perkembangan pengerjaan proyek</p>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/laporan/store.php" enctype="multipart/form-data" id="formLaporan">
            <div class="space-y-5">

                <div>
                    <label class="form-label">Proyek <span class="text-red-500">*</span></label>
                    <select name="proyek_id" class="form-select" required>
                        <option value="">-- Pilih Proyek --</option>
                        <?php foreach ($proyeks as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $selectedProyek === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= sanitize($p['nama_proyek']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Judul Laporan <span class="text-red-500">*</span></label>
                    <input type="text" name="judul_laporan" class="form-input"
                        placeholder="Contoh: Laporan Survei Hari ke-1" required>
                </div>

                <div>
                    <label class="form-label">Catatan / Deskripsi Lapangan <span class="text-red-500">*</span></label>
                    <textarea name="deskripsi" class="form-input" rows="5"
                        placeholder="Tuliskan kondisi lapangan, pekerjaan yang dilakukan, kendala yang dihadapi..." required></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Status Pengerjaan <span class="text-red-500">*</span></label>
                        <select name="status_pengerjaan" class="form-select" required>
                            <option value="belum_mulai">Belum Mulai</option>
                            <option value="dalam_proses" selected>Dalam Proses</option>
                            <option value="selesai">Selesai</option>
                            <option value="terkendala">Terkendala</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Tanggal Laporan <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_laporan" class="form-input"
                            value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <!-- Upload Foto -->
                <div>
                    <label class="form-label">Foto Dokumentasi</label>
                    <div class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center hover:border-primary-400 transition-colors cursor-pointer"
                        onclick="document.getElementById('fotoInput').click()">
                        <i class="bi bi-cloud-upload text-3xl text-slate-300 block mb-2"></i>
                        <p class="text-sm text-slate-500">Klik untuk pilih foto atau drag & drop</p>
                        <p class="text-xs text-slate-400 mt-1">Format JPG/PNG, maksimal 5MB per foto</p>
                        <input type="file" name="foto[]" id="fotoInput" multiple accept="image/jpeg,image/png,image/jpg"
                            class="hidden" onchange="previewFoto(this)">
                    </div>
                    <!-- Preview -->
                    <div id="previewFoto" class="flex flex-wrap gap-2 mt-3"></div>
                </div>

                <div class="flex gap-3 pt-2">
                    <a href="<?= BASE_URL ?>/pages/laporan/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                    <button type="submit" class="btn-primary flex-1 justify-center" id="submitBtn">
                        <i class="bi bi-upload"></i> Unggah Laporan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function previewFoto(input) {
    const preview = document.getElementById('previewFoto');
    preview.innerHTML = '';
    [...input.files].forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
            const wrap = document.createElement('div');
            wrap.className = 'relative';
            wrap.innerHTML = `<img src="${e.target.result}" class="w-20 h-20 object-cover rounded-lg border border-slate-200">
                <span class="absolute -top-1 -right-1 w-5 h-5 bg-slate-800 rounded-full flex items-center justify-center text-white text-xs">${[...document.getElementById('fotoInput').files].indexOf(file)+1}</span>`;
            preview.appendChild(wrap);
        };
        reader.readAsDataURL(file);
    });
}

document.getElementById('formLaporan').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Mengunggah...';
    btn.disabled  = true;
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>