<?php
// pages/jadwal/create.php
$pageTitle   = 'Tambah Jadwal';
$currentPage = 'jadwal';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);

$proyeks   = db()->query("SELECT * FROM proyek WHERE status IN ('survei','perencanaan','instalasi') ORDER BY nama_proyek")->fetchAll();
$selectedP = (int)($_GET['proyek_id'] ?? 0);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-lg mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/jadwal/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <h2 class="text-lg font-semibold text-slate-800">Tambah Jadwal Instalasi</h2>
    </div>
    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/jadwal/store.php">
            <div class="space-y-4">
                <div>
                    <label class="form-label">Proyek <span class="text-red-500">*</span></label>
                    <select name="proyek_id" class="form-select" required>
                        <option value="">-- Pilih Proyek --</option>
                        <?php foreach ($proyeks as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $selectedP === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= sanitize($p['nama_proyek']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Tanggal Mulai <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_mulai" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Tanggal Selesai <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_selesai" class="form-input" required>
                    </div>
                </div>
                <div>
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="keterangan" class="form-input"
                        placeholder="Contoh: Pemasangan panel dan inverter">
                </div>
                <div class="flex gap-3 pt-2">
                    <a href="<?= BASE_URL ?>/pages/jadwal/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                    <button type="submit" class="btn-primary flex-1 justify-center"><i class="bi bi-check-lg"></i> Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>