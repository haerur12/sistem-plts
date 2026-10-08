<?php
$pageTitle   = 'Edit Pengguna';
$currentPage = 'pengguna';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT id, nama, email, role, status FROM users WHERE id = ?');
$stmt->execute([$id]);
$pengguna = $stmt->fetch();
if (!$pengguna) {
    setFlash('error', 'Pengguna tidak ditemukan.');
    redirect(BASE_URL . '/pages/pengguna/index.php');
}
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
            <h2 class="text-lg font-bold text-slate-900">Edit Pengguna</h2>
            <p class="text-sm text-slate-400"><?= sanitize($pengguna['nama']) ?></p>
        </div>
    </div>
    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/pengguna/update.php">
            <input type="hidden" name="id" value="<?= (int)$pengguna['id'] ?>">
            <div class="space-y-4">
                <div>
                    <label class="form-label" for="nama">Nama <span class="text-red-500">*</span></label>
                    <input id="nama" type="text" name="nama" class="form-input"
                        value="<?= sanitize($pengguna['nama']) ?>" required maxlength="150">
                </div>
                <div>
                    <label class="form-label" for="email">Email <span class="text-red-500">*</span></label>
                    <input id="email" type="email" name="email" class="form-input"
                        value="<?= sanitize($pengguna['email']) ?>" required maxlength="150">
                </div>
                <div>
                    <label class="form-label" for="password">Password Baru</label>
                    <input id="password" type="password" name="password" class="form-input"
                        minlength="8" autocomplete="new-password" placeholder="Kosongkan jika tidak diganti">
                </div>
                <div>
                    <label class="form-label" for="role">Role <span class="text-red-500">*</span></label>
                    <select id="role" name="role" class="form-select" required>
                        <?php foreach ($roles as $value => $label): ?>
                        <option value="<?= sanitize($value) ?>" <?= $pengguna['role'] === $value ? 'selected' : '' ?>>
                            <?= sanitize($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="aktif" <?= $pengguna['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= $pengguna['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <a href="<?= BASE_URL ?>/pages/pengguna/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                    <button type="submit" class="btn-primary flex-1 justify-center"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>