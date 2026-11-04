<?php
// pages/pelanggan/create.php — FORM TAMBAH PELANGGAN
$pageTitle   = 'Tambah Pelanggan';
$currentPage = 'pelanggan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-xl mx-auto">
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <a href="<?= BASE_URL ?>/pages/pelanggan/index.php">Data Pelanggan</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Tambah Pelanggan</span>
    </nav>

    <div class="flex items-center gap-3.5 mb-5">
        <a href="<?= BASE_URL ?>/pages/pelanggan/index.php" class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-colors shadow-2xs">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Tambah Pelanggan / Klien Baru</h2>
            <p class="text-xs text-slate-500 mt-0.5">Masukkan identitas perusahaan atau pemilik lokasi instalasi PLTS</p>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/pelanggan/store.php" class="space-y-4">
            <?= csrfField() ?>
            <div>
                <label class="form-label">Nama Perusahaan / Pelanggan <span class="text-red-500">*</span></label>
                <input type="text" name="nama_pelanggan" class="form-input"
                       placeholder="Contoh: PT Yamaha Motor Parts Manufacturing Indonesia" required autofocus>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">No. Telepon / WhatsApp PIC <span class="text-red-500">*</span></label>
                    <input type="text" name="no_telp" class="form-input" placeholder="Contoh: 081234567890" required>
                </div>
                <div>
                    <label class="form-label">Alamat Email <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="email" name="email" class="form-input" placeholder="pic@perusahaan.co.id">
                </div>
            </div>

            <div>
                <label class="form-label">Alamat Lengkap Lokasi / Kawasan Industri <span class="text-red-500">*</span></label>
                <textarea name="alamat" rows="3" class="form-textarea"
                          placeholder="Contoh: Kawasan Industri KIIC, Jl. Harapan Raya Lot G, Karawang, Jawa Barat" required></textarea>
            </div>

            <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
                <a href="<?= BASE_URL ?>/pages/pelanggan/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="bi bi-check-lg"></i> Simpan Pelanggan
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>