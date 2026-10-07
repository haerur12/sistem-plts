<?php
$pageTitle   = 'Tambah Pelanggan';
$currentPage = 'pelanggan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-lg mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/pelanggan/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Tambah Pelanggan</h2>
            <p class="text-sm text-slate-400">Data pelanggan proyek instalasi PLTS</p>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/pelanggan/store.php">
            <div class="space-y-4">
                <div>
                    <label class="form-label">Nama Pelanggan / Perusahaan <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_pelanggan" class="form-input"
                        placeholder="Contoh: PT Surya Abadi atau Bapak Budi Santoso" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">No. Telepon <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <i class="bi bi-telephone absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="text" name="no_telp" class="form-input pl-9"
                                placeholder="0812xxxxxxxx" required>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <div class="relative">
                            <i class="bi bi-envelope absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="email" name="email" class="form-input pl-9"
                                placeholder="email@example.com">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="form-label">Alamat Lengkap <span class="text-red-500">*</span></label>
                    <textarea name="alamat" class="form-input" rows="3"
                        placeholder="Jl. Sudirman No. 10, Kec. X, Jakarta Selatan 12190" required></textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <a href="<?= BASE_URL ?>/pages/pelanggan/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="bi bi-check-lg"></i> Simpan Pelanggan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>