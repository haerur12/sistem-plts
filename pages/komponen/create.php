<?php
// pages/komponen/create.php
$pageTitle   = 'Tambah Komponen';
$currentPage = 'komponen';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','gudang']);
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-lg mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Tambah Komponen PLTS</h2>
            <p class="text-sm text-slate-400">Data master komponen dan stok awal</p>
        </div>
    </div>
    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/komponen/store.php">
            <div class="space-y-4">
                <div>
                    <label class="form-label">Nama Komponen <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_komponen" class="form-input"
                        placeholder="Contoh: Panel Surya 500Wp Monocrystalline" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Kategori <span class="text-red-500">*</span></label>
                        <select name="kategori" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <?php foreach (['Panel Surya','Inverter','Baterai','Kabel','Mounting','Proteksi','Monitoring','Lainnya'] as $k): ?>
                            <option value="<?= $k ?>"><?= $k ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Satuan <span class="text-red-500">*</span></label>
                        <select name="satuan" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <?php foreach (['unit','buah','meter','set','rol','lembar','kg'] as $s): ?>
                            <option value="<?= $s ?>"><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Stok Tersedia <span class="text-red-500">*</span></label>
                        <input type="number" name="stok_tersedia" class="form-input" value="0" min="0" required>
                    </div>
                    <div>
                        <label class="form-label">Stok Minimum <span class="text-red-500">*</span></label>
                        <input type="number" name="stok_minimum" class="form-input" value="5" min="0" required>
                        <p class="text-xs text-slate-400 mt-1">Notifikasi saat stok ≤ nilai ini</p>
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