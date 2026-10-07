<?php
// pages/komponen/edit.php
$pageTitle   = 'Edit Komponen';
$currentPage = 'komponen';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','gudang']);

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM komponen WHERE id = ?");
$stmt->execute([$id]);
$k = $stmt->fetch();
if (!$k) { setFlash('error','Komponen tidak ditemukan.'); redirect(BASE_URL.'/pages/komponen/index.php'); }

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-lg mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-bold text-slate-900 tracking-tight">Edit Komponen</h2>
            <p class="text-sm text-slate-400"><?= sanitize($k['nama_komponen']) ?></p>
        </div>
    </div>
    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/komponen/update.php">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="space-y-4">
                <div>
                    <label class="form-label">Nama Komponen <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_komponen" class="form-input" value="<?= sanitize($k['nama_komponen']) ?>" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Kategori</label>
                        <select name="kategori" class="form-select" required>
                            <?php foreach (['Panel Surya','Inverter','Baterai','Kabel','Mounting','Proteksi','Monitoring','Lainnya'] as $kat): ?>
                            <option value="<?= $kat ?>" <?= $k['kategori'] === $kat ? 'selected' : '' ?>><?= $kat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Satuan</label>
                        <select name="satuan" class="form-select" required>
                            <?php foreach (['unit','buah','meter','set','rol','lembar','kg'] as $s): ?>
                            <option value="<?= $s ?>" <?= $k['satuan'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Stok Tersedia</label>
                        <input type="number" name="stok_tersedia" class="form-input" value="<?= $k['stok_tersedia'] ?>" min="0" required>
                    </div>
                    <div>
                        <label class="form-label">Stok Minimum</label>
                        <input type="number" name="stok_minimum" class="form-input" value="<?= $k['stok_minimum'] ?>" min="0" required>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                    <button type="submit" class="btn-primary flex-1 justify-center"><i class="bi bi-check-lg"></i> Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>