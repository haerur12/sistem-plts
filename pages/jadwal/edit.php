<?php
$pageTitle   = 'Edit Jadwal';
$currentPage = 'jadwal';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);

$id   = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT j.*, p.nama_proyek, pl.nama_pelanggan FROM jadwal j JOIN proyek p ON j.proyek_id = p.id JOIN pelanggan pl ON p.pelanggan_id = pl.id WHERE j.id = ?");
$stmt->execute([$id]);
$jadwal = $stmt->fetch();
if (!$jadwal) { setFlash('error','Jadwal tidak ditemukan.'); redirect(BASE_URL.'/pages/jadwal/index.php'); }

$proyeks = db()->query("SELECT * FROM proyek ORDER BY nama_proyek ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-lg mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/jadwal/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Edit Jadwal Instalasi</h2>
            <p class="text-sm text-slate-400"><?= sanitize($jadwal['nama_proyek']) ?></p>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/jadwal/update.php">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="space-y-4">

                <div>
                    <label class="form-label">Proyek <span class="text-red-500">*</span></label>
                    <select name="proyek_id" class="form-select" required>
                        <?php foreach ($proyeks as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $jadwal['proyek_id'] == $p['id'] ? 'selected' : '' ?>>
                            <?= sanitize($p['nama_proyek']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Tanggal Mulai <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_mulai" class="form-input"
                            value="<?= $jadwal['tanggal_mulai'] ?>" required>
                    </div>
                    <div>
                        <label class="form-label">Tanggal Selesai <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_selesai" class="form-input"
                            value="<?= $jadwal['tanggal_selesai'] ?>" required>
                    </div>
                </div>

                <div>
                    <label class="form-label">Status <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 gap-3">
                        <?php
                        $statuses = [
                            'terjadwal' => ['icon'=>'bi-calendar-check','label'=>'Terjadwal','color'=>'border-slate-300 text-slate-600'],
                            'berjalan'  => ['icon'=>'bi-play-circle',   'label'=>'Berjalan', 'color'=>'border-blue-400 text-blue-600'],
                            'selesai'   => ['icon'=>'bi-check-circle',  'label'=>'Selesai',  'color'=>'border-green-400 text-green-600'],
                            'ditunda'   => ['icon'=>'bi-pause-circle',  'label'=>'Ditunda',  'color'=>'border-amber-400 text-amber-600'],
                        ];
                        foreach ($statuses as $val => $s): ?>
                        <label class="flex items-center gap-2 p-3 border-2 rounded-xl cursor-pointer transition-all
                            <?= $jadwal['status'] === $val ? 'border-primary-600 bg-primary-50' : 'border-slate-100 hover:border-slate-200' ?>">
                            <input type="radio" name="status" value="<?= $val ?>"
                                <?= $jadwal['status'] === $val ? 'checked' : '' ?> class="sr-only">
                            <i class="bi <?= $s['icon'] ?> <?= $s['color'] ?>"></i>
                            <span class="text-sm font-medium text-slate-700"><?= $s['label'] ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="keterangan" class="form-input"
                        value="<?= sanitize($jadwal['keterangan'] ?? '') ?>"
                        placeholder="Deskripsi singkat kegiatan jadwal">
                </div>

                <!-- Info proyek -->
                <div class="bg-blue-50 rounded-xl p-3 flex items-center gap-3 text-sm">
                    <i class="bi bi-diagram-3 text-blue-500 flex-shrink-0"></i>
                    <div>
                        <div class="font-medium text-blue-800"><?= sanitize($jadwal['nama_proyek']) ?></div>
                        <div class="text-blue-600 text-xs"><?= sanitize($jadwal['nama_pelanggan']) ?></div>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <a href="<?= BASE_URL ?>/pages/jadwal/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="bi bi-check-lg"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Radio button styling
document.querySelectorAll('input[name="status"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('input[name="status"]').forEach(r => {
            r.closest('label').classList.remove('border-primary-600','bg-primary-50');
            r.closest('label').classList.add('border-slate-100');
        });
        this.closest('label').classList.add('border-primary-600','bg-primary-50');
        this.closest('label').classList.remove('border-slate-100');
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>