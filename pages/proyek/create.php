<?php
// pages/proyek/create.php
$pageTitle   = 'Tambah Proyek';
$currentPage = 'proyek';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$pelanggans = db()->query("SELECT * FROM pelanggan ORDER BY nama_pelanggan ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/proyek/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Tambah Proyek Baru</h2>
            <p class="text-sm text-slate-400">Isi data proyek instalasi PLTS</p>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/proyek/store.php" id="formProyek">
            <div class="space-y-5">
                <div>
                    <label class="form-label">Pelanggan <span class="text-red-500">*</span></label>
                    <select name="pelanggan_id" class="form-select" required>
                        <option value="">-- Pilih Pelanggan --</option>
                        <?php foreach ($pelanggans as $pl): ?>
                        <option value="<?= $pl['id'] ?>"><?= sanitize($pl['nama_pelanggan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Nama Proyek <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_proyek" class="form-input"
                        placeholder="Contoh: PLTS Atap 50kWp Jakarta" required>
                </div>

                <div>
                    <label class="form-label">Lokasi <span class="text-red-500">*</span></label>
                    <textarea name="lokasi" class="form-input" rows="2"
                        placeholder="Alamat lengkap lokasi proyek" required></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Tanggal Mulai <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_mulai" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Target Selesai <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_selesai" class="form-input" required>
                    </div>
                </div>

                <div>
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" class="form-input" rows="3"
                        placeholder="Deskripsi singkat proyek instalasi..."></textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <a href="<?= BASE_URL ?>/pages/proyek/index.php" class="btn-secondary flex-1 justify-center">
                        Batal
                    </a>
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="bi bi-check-lg"></i> Simpan Proyek
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>