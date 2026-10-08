<?php
$pageTitle   = 'Tambah Pengguna';
$currentPage = 'pengguna';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$roles = [
    'admin' => 'Admin',
    'manajer_proyek' => 'Manajer Proyek',
    'teknisi_lapangan' => 'Teknisi Lapangan',
    'gudang' => 'Gudang',
];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-lg mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/pengguna/index.php" class="p-2 hover:bg-slate-100 rounded-lg">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-bold text-slate-900">Tambah Pengguna</h2>
            <p class="text-sm text-slate-400">Buat akun pengguna baru</p>
        </div>
    </div>
    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/pengguna/store.php">
            <div class="space-y-4">
                <div>
                    <label class="form-label" for="nama">Nama <span class="text-red-500">*</span></label>
                    <input id="nama" type="text" name="nama" class="form-input" required maxlength="150">
                </div>
                <div>
                    <label class="form-label" for="email">Email <span class="text-red-500">*</span></label>
                    <input id="email" type="email" name="email" class="form-input" required maxlength="150">
                </div>
                <div>
                    <label class="form-label" for="password">Password <span class="text-red-500">*</span></label>
                    <input id="password" type="password" name="password" class="form-input" required minlength="8" autocomplete="new-password">
                </div>
                <div>
                    <label class="form-label" for="role">Role <span class="text-red-500">*</span></label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="">-- Pilih role --</option>
                        <?php foreach ($roles as $value => $label): ?>
                        <option value="<?= sanitize($value) ?>"><?= sanitize($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <a href="<?= BASE_URL ?>/pages/pengguna/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                    <button type="submit" class="btn-primary flex-1 justify-center"><i class="bi bi-check-lg"></i> Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>