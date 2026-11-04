<?php
// pages/pelanggan/edit.php — FORM EDIT PELANGGAN & DAFTAR PROYEKNYA
$pageTitle   = 'Edit Pelanggan';
$currentPage = 'pelanggan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$db = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM pelanggan WHERE id = ?");
$stmt->execute([$id]);
$pelanggan = $stmt->fetch();

if (!$pelanggan) {
    setFlash('error', 'Data pelanggan tidak ditemukan.');
    redirect(BASE_URL . '/pages/pelanggan/index.php');
}

// Ambil daftar proyek milik pelanggan ini
$stmtProyek = $db->prepare("SELECT * FROM proyek WHERE pelanggan_id = ? ORDER BY created_at DESC");
$stmtProyek->execute([$id]);
$proyekKlien = $stmtProyek->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <a href="<?= BASE_URL ?>/pages/pelanggan/index.php">Data Pelanggan</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Edit Pelanggan</span>
    </nav>

    <div class="flex items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-3.5">
            <a href="<?= BASE_URL ?>/pages/pelanggan/index.php" class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-colors shadow-2xs">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Edit Data Pelanggan</h2>
                <p class="text-xs text-slate-500 mt-0.5"><?= sanitize($pelanggan['nama_pelanggan']) ?></p>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/pages/proyek/create.php?pelanggan_id=<?= $pelanggan['id'] ?>" class="btn-secondary btn-sm">
            <i class="bi bi-plus-circle text-primary-600"></i> Buat Proyek Klien Ini
        </a>
    </div>

    <div class="card p-6 mb-6">
        <form method="POST" action="<?= BASE_URL ?>/api/pelanggan/update.php" class="space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $pelanggan['id'] ?>">

            <div>
                <label class="form-label">Nama Perusahaan / Pelanggan <span class="text-red-500">*</span></label>
                <input type="text" name="nama_pelanggan" class="form-input" value="<?= sanitize($pelanggan['nama_pelanggan']) ?>" required>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">No. Telepon / WhatsApp PIC <span class="text-red-500">*</span></label>
                    <input type="text" name="no_telp" class="form-input" value="<?= sanitize($pelanggan['no_telp']) ?>" required>
                </div>
                <div>
                    <label class="form-label">Alamat Email</label>
                    <input type="email" name="email" class="form-input" value="<?= sanitize($pelanggan['email'] ?? '') ?>">
                </div>
            </div>

            <div>
                <label class="form-label">Alamat Lengkap Lokasi <span class="text-red-500">*</span></label>
                <textarea name="alamat" rows="3" class="form-textarea" required><?= sanitize($pelanggan['alamat']) ?></textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                <a href="<?= BASE_URL ?>/pages/pelanggan/index.php" class="btn-secondary px-5">Batal</a>
                <button type="submit" class="btn-primary px-6">
                    <i class="bi bi-check-lg"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <!-- Riwayat Proyek Milik Pelanggan Ini -->
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="bi bi-diagram-3 text-primary-600"></i> Daftar Proyek Milik Pelanggan Ini (<?= count($proyekKlien) ?>)
            </h3>
        </div>
        <?php if (empty($proyekKlien)): ?>
        <div class="p-6 text-center text-xs text-slate-400">Belum ada proyek yang terkait dengan pelanggan ini.</div>
        <?php else: ?>
        <div class="divide-y divide-slate-100">
            <?php foreach ($proyekKlien as $pk): ?>
            <div class="px-5 py-3.5 flex items-center justify-between gap-3 hover:bg-slate-50">
                <div>
                    <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $pk['id'] ?>" class="text-xs font-bold text-slate-800 hover:text-primary-600">
                        <?= sanitize($pk['nama_proyek']) ?>
                    </a>
                    <div class="text-[11px] text-slate-400 mt-0.5"><?= sanitize($pk['lokasi']) ?> · Progres <?= (int)$pk['progres'] ?>%</div>
                </div>
                <?= statusBadge($pk['status']) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>