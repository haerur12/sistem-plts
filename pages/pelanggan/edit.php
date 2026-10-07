<?php
$pageTitle   = 'Edit Pelanggan';
$currentPage = 'pelanggan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$id   = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT p.*, COUNT(pr.id) as total_proyek FROM pelanggan p LEFT JOIN proyek pr ON p.id = pr.pelanggan_id WHERE p.id = ? GROUP BY p.id");
$stmt->execute([$id]);
$pelanggan = $stmt->fetch();
if (!$pelanggan) { setFlash('error','Pelanggan tidak ditemukan.'); redirect(BASE_URL.'/pages/pelanggan/index.php'); }

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-lg mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/pelanggan/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Edit Pelanggan</h2>
            <p class="text-sm text-slate-400"><?= sanitize($pelanggan['nama_pelanggan']) ?></p>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/pelanggan/update.php">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="space-y-4">
                <div>
                    <label class="form-label">Nama Pelanggan / Perusahaan <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_pelanggan" class="form-input"
                        value="<?= sanitize($pelanggan['nama_pelanggan']) ?>" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">No. Telepon <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <i class="bi bi-telephone absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="text" name="no_telp" class="form-input pl-9"
                                value="<?= sanitize($pelanggan['no_telp']) ?>" required>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <div class="relative">
                            <i class="bi bi-envelope absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="email" name="email" class="form-input pl-9"
                                value="<?= sanitize($pelanggan['email'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="form-label">Alamat Lengkap <span class="text-red-500">*</span></label>
                    <textarea name="alamat" class="form-input" rows="3" required><?= sanitize($pelanggan['alamat']) ?></textarea>
                </div>

                <!-- Info proyek terkait -->
                <?php if ($pelanggan['total_proyek'] > 0): ?>
                <div class="bg-blue-50 rounded-lg p-3 flex items-center gap-2 text-sm text-blue-700">
                    <i class="bi bi-info-circle flex-shrink-0"></i>
                    <span>Pelanggan ini memiliki <strong><?= $pelanggan['total_proyek'] ?> proyek</strong> yang terkait.</span>
                </div>
                <?php endif; ?>

                <div class="flex gap-3 pt-2">
                    <a href="<?= BASE_URL ?>/pages/pelanggan/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="bi bi-check-lg"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>