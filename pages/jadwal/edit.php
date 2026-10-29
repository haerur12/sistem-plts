<?php
// pages/jadwal/edit.php — FORM EDIT JADWAL INSTALASI
$pageTitle   = 'Edit Jadwal Instalasi';
$currentPage = 'jadwal';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'manajer_proyek']);

$db = db();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    setFlash('error', 'ID jadwal tidak valid.');
    redirect(BASE_URL . '/pages/jadwal/index.php');
}

$stmt = $db->prepare("
    SELECT j.*, p.nama_proyek, pl.nama_pelanggan
    FROM jadwal j
    JOIN proyek p ON j.proyek_id = p.id
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    WHERE j.id = ?
");
$stmt->execute([$id]);
$jadwal = $stmt->fetch();

if (!$jadwal) {
    setFlash('error', 'Data jadwal tidak ditemukan.');
    redirect(BASE_URL . '/pages/jadwal/index.php');
}

$proyeks = $db->query("
    SELECT p.id, p.nama_proyek, pl.nama_pelanggan 
    FROM proyek p 
    JOIN pelanggan pl ON p.pelanggan_id = pl.id 
    ORDER BY p.nama_proyek ASC
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <a href="<?= BASE_URL ?>/pages/jadwal/index.php">Jadwal Instalasi</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Edit Jadwal #<?= $jadwal['id'] ?></span>
    </nav>

    <!-- Header -->
    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-3">
            <a href="<?= BASE_URL ?>/pages/jadwal/index.php" class="p-2 hover:bg-slate-100 rounded-xl transition-colors">
                <i class="bi bi-arrow-left text-slate-500"></i>
            </a>
            <div>
                <h2 class="text-lg font-bold text-slate-900 tracking-tight">Edit Jadwal Instalasi</h2>
                <p class="text-xs text-slate-400"><?= sanitize($jadwal['nama_proyek']) ?> — <?= sanitize($jadwal['nama_pelanggan']) ?></p>
            </div>
        </div>
        <?= statusBadge($jadwal['status']) ?>
    </div>

    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/jadwal/update.php" id="formEditJadwal">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $jadwal['id'] ?>">

            <div class="space-y-4">
                <div>
                    <label class="form-label">Proyek PLTS <span class="text-red-500">*</span></label>
                    <select name="proyek_id" class="form-select" required>
                        <option value="">-- Pilih Proyek --</option>
                        <?php foreach ($proyeks as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= (int)$jadwal['proyek_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= sanitize($p['nama_proyek']) ?> — <?= sanitize($p['nama_pelanggan']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Tanggal Mulai <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_mulai" id="tglMulai" class="form-input"
                               value="<?= sanitize($jadwal['tanggal_mulai']) ?>" required onchange="hitungDurasi()">
                    </div>
                    <div>
                        <label class="form-label">Tanggal Selesai <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_selesai" id="tglSelesai" class="form-input"
                               value="<?= sanitize($jadwal['tanggal_selesai']) ?>" required onchange="hitungDurasi()">
                    </div>
                </div>

                <!-- Info Durasi Otomatis -->
                <div class="px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500"><i class="bi bi-clock-history mr-1"></i> Estimasi Durasi Tahapan:</span>
                    <strong id="durasiLabel" class="text-primary-700">- hari</strong>
                </div>

                <div>
                    <label class="form-label">Status Pelaksanaan <span class="text-red-500">*</span></label>
                    <select name="status" class="form-select" required>
                        <?php
                        $statusList = [
                            'terjadwal' => 'Terjadwal (Belum Dimulai)',
                            'berjalan'  => 'Sedang Berjalan (On Progress)',
                            'selesai'   => 'Selesai (Tuntas)',
                            'ditunda'   => 'Ditunda (On Hold)',
                        ];
                        foreach ($statusList as $val => $lbl):
                        ?>
                        <option value="<?= $val ?>" <?= $jadwal['status'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Tahapan / Keterangan Pekerjaan</label>
                    <textarea name="keterangan" rows="3" class="form-textarea"
                              placeholder="Contoh: Pemetaan gedung dan instalasi mounting rooftop"><?= sanitize($jadwal['keterangan'] ?? '') ?></textarea>
                </div>

                <div class="flex items-center justify-between gap-3 pt-4 border-t border-slate-100">
                    <button type="button"
                            onclick="confirmDelete('formHapusJadwal', 'Yakin ingin menghapus jadwal ini secara permanen?')"
                            class="btn-secondary text-red-600 hover:bg-red-50">
                        <i class="bi bi-trash"></i> Hapus
                    </button>

                    <div class="flex gap-2">
                        <a href="<?= BASE_URL ?>/pages/jadwal/index.php" class="btn-secondary">Batal</a>
                        <button type="submit" class="btn-primary">
                            <i class="bi bi-check-lg"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Form Hapus Terpisah untuk Modal Konfirmasi -->
        <form method="POST" action="<?= BASE_URL ?>/api/jadwal/delete.php" id="formHapusJadwal" class="hidden">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $jadwal['id'] ?>">
        </form>
    </div>
</div>

<script>
function hitungDurasi() {
    const m = document.getElementById('tglMulai').value;
    const s = document.getElementById('tglSelesai').value;
    const lbl = document.getElementById('durasiLabel');
    if (!m || !s) return;
    const diff = Math.round((new Date(s) - new Date(m)) / (1000 * 60 * 60 * 24)) + 1;
    if (diff < 1) {
        lbl.textContent = 'Tanggal selesai tidak boleh sebelum tanggal mulai!';
        lbl.className = 'text-red-600 font-bold';
    } else {
        lbl.textContent = diff + ' hari kerja';
        lbl.className = 'text-primary-700 font-bold';
    }
}
document.addEventListener('DOMContentLoaded', hitungDurasi);
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>