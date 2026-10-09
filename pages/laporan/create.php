<?php
$pageTitle    = 'Unggah Laporan';
$pageSubtitle = 'Form Laporan';
$currentPage  = 'laporan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek','teknisi_lapangan']);

$db          = db();
$user        = currentUser();
$selectedP   = (int)($_GET['proyek_id'] ?? 0);
$proyeks     = $db->query("
    SELECT * FROM proyek
    WHERE status IN ('survei','perencanaan','instalasi')
    ORDER BY nama_proyek ASC
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/laporan/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Unggah Laporan Lapangan</h2>
            <p class="text-sm text-slate-400">Dokumentasi perkembangan pengerjaan proyek</p>
        </div>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/api/laporan/store.php"
        enctype="multipart/form-data" id="formLaporan" novalidate>
        <?= csrfField() ?>

        <!-- Step Indicator -->
        <div class="flex items-center gap-0 mb-6">
            <?php $steps = ['Info Laporan','Catatan Lapangan','Foto Dokumentasi']; ?>
            <?php foreach ($steps as $i => $step): ?>
            <div class="flex items-center <?= $i < count($steps)-1 ? 'flex-1' : '' ?>">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
                        step-circle transition-all"
                        id="stepCircle<?= $i+1 ?>"
                        style="<?= $i===0 ? 'background:#1a237e;color:#fff' : 'background:#f1f5f9;color:#94a3b8' ?>">
                        <?= $i+1 ?>
                    </div>
                    <span class="text-xs mt-1 font-medium step-label"
                        id="stepLabel<?= $i+1 ?>"
                        style="<?= $i===0 ? 'color:#1a237e' : 'color:#94a3b8' ?>">
                        <?= $step ?>
                    </span>
                </div>
                <?php if ($i < count($steps)-1): ?>
                <div class="flex-1 h-0.5 mx-2 mt-0 transition-all step-line" id="stepLine<?= $i+1 ?>"
                    style="background:#e2e8f0; margin-bottom:16px"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- STEP 1 — Info Laporan -->
        <div id="step1" class="card p-6 mb-4">
            <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center text-xs font-bold">1</span>
                Informasi Laporan
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="form-label">Proyek <span class="text-red-500">*</span></label>
                    <select name="proyek_id" id="proyekSelect" class="form-select" required>
                        <option value="">-- Pilih Proyek --</option>
                        <?php foreach ($proyeks as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $selectedP===$p['id']?'selected':'' ?>>
                            <?= sanitize($p['nama_proyek']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Judul Laporan <span class="text-red-500">*</span></label>
                    <input type="text" name="judul_laporan" id="judulInput"
                        class="form-input" placeholder="Contoh: Laporan Survei Lokasi Hari ke-1" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Status Pengerjaan <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <?php
                            $statuses = [
                                'belum_mulai'  => ['icon'=>'bi-hourglass',        'label'=>'Belum Mulai', 'color'=>'slate'],
                                'dalam_proses' => ['icon'=>'bi-play-circle',      'label'=>'Dalam Proses','color'=>'blue'],
                                'selesai'      => ['icon'=>'bi-check-circle',     'label'=>'Selesai',     'color'=>'green'],
                                'terkendala'   => ['icon'=>'bi-exclamation-circle','label'=>'Terkendala', 'color'=>'red'],
                            ];
                            foreach ($statuses as $val => $s): ?>
                            <label class="status-option flex items-center gap-2 p-2.5 border-2 rounded-xl cursor-pointer transition-all
                                <?= $val==='dalam_proses' ? 'border-blue-500 bg-blue-50' : 'border-slate-100 hover:border-slate-200' ?>">
                                <input type="radio" name="status_pengerjaan" value="<?= $val ?>"
                                    <?= $val==='dalam_proses'?'checked':'' ?> class="sr-only">
                                <i class="bi <?= $s['icon'] ?> text-<?= $s['color'] ?>-500 text-sm"></i>
                                <span class="text-xs font-medium text-slate-700"><?= $s['label'] ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Tanggal Laporan <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_laporan" class="form-input"
                            value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 2 — Catatan Lapangan -->
        <div id="step2" class="card p-6 mb-4">
            <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center text-xs font-bold">2</span>
                Catatan Lapangan
            </h3>
            <div>
                <label class="form-label">Deskripsi / Catatan <span class="text-red-500">*</span></label>
                <textarea name="deskripsi" id="deskripsiInput" class="form-input" rows="6"
                    placeholder="Tuliskan secara detail:&#10;• Kondisi lokasi saat ini&#10;• Pekerjaan yang sudah dilakukan&#10;• Kendala yang dihadapi&#10;• Rencana pekerjaan berikutnya"
                    required></textarea>
                <div class="flex justify-between mt-1.5">
                    <span class="text-xs text-slate-400">Minimal 20 karakter</span>
                    <span class="text-xs text-slate-400" id="charCount">0 karakter</span>
                </div>
            </div>
        </div>

        <!-- STEP 3 — Upload Foto -->
        <div id="step3" class="card p-6 mb-4">
            <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center text-xs font-bold">3</span>
                Foto Dokumentasi
                <span class="text-xs font-normal text-slate-400">(Opsional, max 10 foto)</span>
            </h3>

            <!-- Drop Zone -->
            <div id="dropZone"
                class="border-2 border-dashed border-slate-200 rounded-2xl p-8 text-center
                hover:border-primary-400 hover:bg-primary-50/30 transition-all cursor-pointer
                relative group"
                onclick="document.getElementById('fotoInput').click()">

                <div id="dropZoneContent">
                    <div class="w-16 h-16 bg-slate-100 group-hover:bg-primary-100 rounded-2xl flex items-center justify-center mx-auto mb-4 transition-colors">
                        <i class="bi bi-cloud-upload text-3xl text-slate-400 group-hover:text-primary-600 transition-colors"></i>
                    </div>
                    <h4 class="text-sm font-semibold text-slate-700 mb-1">Drag & drop foto di sini</h4>
                    <p class="text-xs text-slate-400 mb-3">atau klik untuk pilih dari galeri / kamera</p>
                    <div class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 font-medium shadow-sm">
                        <i class="bi bi-folder2-open text-primary-600"></i> Pilih Foto
                    </div>
                    <p class="text-xs text-slate-300 mt-3">JPG, PNG · Maks 5MB per foto · Maks 10 foto</p>
                </div>

                <!-- Drag overlay -->
                <div id="dropOverlay"
                    class="hidden absolute inset-0 rounded-2xl bg-primary-50 border-2 border-primary-400
                    flex flex-col items-center justify-center">
                    <i class="bi bi-cloud-upload text-4xl text-primary-500 mb-2"></i>
                    <p class="text-sm font-semibold text-primary-700">Lepaskan untuk upload!</p>
                </div>

                <input type="file" name="foto[]" id="fotoInput" multiple
                    accept="image/jpeg,image/png,image/jpg" class="hidden">
            </div>

            <!-- Preview Grid -->
            <div id="previewGrid" class="grid grid-cols-3 sm:grid-cols-4 gap-3 mt-4 hidden"></div>

            <!-- Upload Progress -->
            <div id="uploadProgress" class="hidden mt-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-slate-500" id="progressLabel">Memproses foto...</span>
                    <span class="text-xs font-semibold text-primary-700" id="progressPct">0%</span>
                </div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div id="progressBar" class="h-full bg-primary-600 rounded-full transition-all" style="width:0%"></div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex gap-3">
            <a href="<?= BASE_URL ?>/pages/laporan/index.php" class="btn-secondary flex-1 justify-center">
                Batal
            </a>
            <button type="submit" class="btn-primary flex-1 justify-center" id="submitBtn">
                <i class="bi bi-upload"></i> Unggah Laporan
            </button>
        </div>
    </form>
</div>

<style>
.status-option input:checked + * { display: contents; }
.status-option:has(input:checked) {
    border-color: #3b82f6 !important;
    background: #eff6ff !important;
}
.status-option:has(input[value="selesai"]:checked) { border-color:#22c55e !important; background:#f0fdf4 !important; }
.status-option:has(input[value="terkendala"]:checked) { border-color:#ef4444 !important; background:#fef2f2 !important; }
.status-option:has(input[value="belum_mulai"]:checked) { border-color:#94a3b8 !important; background:#f8fafc !important; }
</style>

<script>
// ============================================================
// CHARACTER COUNT
// ============================================================
const deskripsi  = document.getElementById('deskripsiInput');
const charCount  = document.getElementById('charCount');
deskripsi?.addEventListener('input', function() {
    const len = this.value.length;
    charCount.textContent = len + ' karakter';
    charCount.style.color = len < 20 ? '#ef4444' : '#22c55e';
});

// ============================================================
// DRAG & DROP UPLOAD
// ============================================================
const dropZone    = document.getElementById('dropZone');
const dropOverlay = document.getElementById('dropOverlay');
const fotoInput   = document.getElementById('fotoInput');
const previewGrid = document.getElementById('previewGrid');
let   uploadedFiles = [];

// Drag events
['dragenter','dragover'].forEach(ev => {
    dropZone.addEventListener(ev, e => {
        e.preventDefault();
        dropOverlay.classList.remove('hidden');
    });
});
['dragleave','dragend'].forEach(ev => {
    dropZone.addEventListener(ev, () => {
        dropOverlay.classList.add('hidden');
    });
});
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropOverlay.classList.add('hidden');
    const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
    addFiles(files);
});

// File input change
fotoInput.addEventListener('change', function() {
    addFiles(Array.from(this.files));
    this.value = ''; // Reset agar bisa pilih file sama lagi
});

// Tambahkan file ke preview
function addFiles(files) {
    const progress = document.getElementById('uploadProgress');
    const bar      = document.getElementById('progressBar');
    const pct      = document.getElementById('progressPct');
    const label    = document.getElementById('progressLabel');

    // Max 10 foto
    const remaining = 10 - uploadedFiles.length;
    files = files.slice(0, remaining);
    if (!files.length) return;

    progress.classList.remove('hidden');
    previewGrid.classList.remove('hidden');

    let loaded = 0;
    files.forEach((file, idx) => {
        // Cek ukuran (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            Toast?.show(`File "${file.name}" terlalu besar (maks 5MB)`, 'warning');
            loaded++;
            updateProgress(loaded, files.length, bar, pct, label, progress);
            return;
        }

        const reader = new FileReader();
        reader.onload = e => {
            uploadedFiles.push(file);
            renderPreview(e.target.result, file.name, uploadedFiles.length - 1);
            loaded++;
            updateProgress(loaded, files.length, bar, pct, label, progress);
        };
        reader.readAsDataURL(file);
    });

    // Update input files
    syncFilesInput();
}

function updateProgress(loaded, total, bar, pct, label, container) {
    const p = Math.round((loaded / total) * 100);
    bar.style.width   = p + '%';
    pct.textContent   = p + '%';
    label.textContent = loaded < total ? `Memproses ${loaded}/${total} foto...` : `${total} foto siap diunggah`;
    if (loaded >= total) {
        setTimeout(() => container.classList.add('hidden'), 1500);
    }
}

function renderPreview(src, name, idx) {
    const div = document.createElement('div');
    div.id        = 'preview_' + idx;
    div.className = 'relative group aspect-square rounded-xl overflow-hidden border border-slate-200 bg-slate-100';
    div.innerHTML = `
        <img src="${src}" alt="${name}" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all flex items-center justify-center">
            <button type="button" onclick="removeFile(${idx})"
                class="opacity-0 group-hover:opacity-100 transition-opacity
                w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center shadow-md">
                <i class="bi bi-x text-lg"></i>
            </button>
        </div>
        <div class="absolute bottom-1 left-1 right-1 bg-black/50 text-white text-xs px-1.5 py-0.5 rounded truncate">
            ${name}
        </div>`;
    previewGrid.appendChild(div);
}

function removeFile(idx) {
    uploadedFiles[idx] = null;
    document.getElementById('preview_' + idx)?.remove();
    syncFilesInput();
    if (uploadedFiles.filter(Boolean).length === 0) {
        previewGrid.classList.add('hidden');
    }
}

function syncFilesInput() {
    // Buat DataTransfer baru dengan file yang tersisa
    const dt    = new DataTransfer();
    const valid = uploadedFiles.filter(Boolean);
    valid.forEach(f => dt.items.add(f));
    fotoInput.files = dt.files;
}

// ============================================================
// FORM VALIDATION & SUBMIT
// ============================================================
document.getElementById('formLaporan').addEventListener('submit', function(e) {
    const proyek = document.getElementById('proyekSelect').value;
    const judul  = document.getElementById('judulInput').value.trim();
    const desk   = deskripsi.value.trim();

    if (!proyek) { e.preventDefault(); Toast?.show('Pilih proyek terlebih dahulu', 'error'); return; }
    if (!judul)  { e.preventDefault(); Toast?.show('Judul laporan wajib diisi', 'error'); return; }
    if (desk.length < 20) { e.preventDefault(); Toast?.show('Catatan lapangan minimal 20 karakter', 'error'); return; }

    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span> Mengunggah...';
    btn.disabled  = true;
});

// Status radio styling
document.querySelectorAll('.status-option').forEach(label => {
    label.addEventListener('click', function() {
        document.querySelectorAll('.status-option').forEach(l => {
            l.style.borderColor = '';
            l.style.background  = '';
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>