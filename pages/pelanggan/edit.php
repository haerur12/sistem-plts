<?php
$pageTitle   = 'Edit Pelanggan';
$currentPage = 'pelanggan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM pelanggan WHERE id = ?');
$stmt->execute([$id]);
$pelanggan = $stmt->fetch();
if (!$pelanggan) {
    setFlash('error', 'Pelanggan tidak ditemukan.');
    redirect(BASE_URL . '/pages/pelanggan/index.php');
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/pelanggan/index.php" class="p-2 hover:bg-slate-100 rounded-lg">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-bold text-slate-900">Edit Pelanggan</h2>
            <p class="text-sm text-slate-400"><?= sanitize($pelanggan['nama_pelanggan']) ?></p>
        </div>
    </div>
    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/pelanggan/update.php">
            <input type="hidden" name="id" value="<?= (int)$pelanggan['id'] ?>">
            <div class="space-y-4">
                <div>
                    <label class="form-label" for="nama_pelanggan">Nama Pelanggan <span class="text-red-500">*</span></label>
                    <input id="nama_pelanggan" type="text" name="nama_pelanggan" class="form-input"
                        value="<?= sanitize($pelanggan['nama_pelanggan']) ?>" required maxlength="150">
                </div>
                <div>
                    <label class="form-label" for="alamat">Alamat <span class="text-red-500">*</span></label>
                    <textarea id="alamat" name="alamat" class="form-input" rows="3" required><?= sanitize($pelanggan['alamat'] ?? '') ?></textarea>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="no_telp">Nomor Telepon <span class="text-red-500">*</span></label>
                        <input id="no_telp" type="tel" name="no_telp" class="form-input"
                            value="<?= sanitize($pelanggan['no_telp'] ?? '') ?>" required maxlength="30">
                    </div>
                    <div>
                        <label class="form-label" for="email">Email</label>
                        <input id="email" type="email" name="email" class="form-input"
                            value="<?= sanitize($pelanggan['email'] ?? '') ?>" maxlength="150">
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <a href="<?= BASE_URL ?>/pages/pelanggan/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                    <button type="submit" class="btn-primary flex-1 justify-center"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>