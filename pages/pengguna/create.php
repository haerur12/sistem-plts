<?php
$pageTitle   = 'Tambah Pengguna';
$currentPage = 'pengguna';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-lg mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/pengguna/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Tambah Pengguna</h2>
            <p class="text-sm text-slate-400">Akun baru untuk mengakses sistem</p>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/pengguna/store.php">
            <div class="space-y-4">
                <div>
                    <label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" class="form-input"
                        placeholder="Nama lengkap pengguna" required>
                </div>

                <div>
                    <label class="form-label">Email <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <i class="bi bi-envelope absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="email" name="email" class="form-input pl-9"
                            placeholder="email@example.com" required>
                    </div>
                </div>

                <div>
                    <label class="form-label">Role / Jabatan <span class="text-red-500">*</span></label>
                    <select name="role" class="form-select" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="admin">Admin — Akses penuh semua fitur</option>
                        <option value="manajer_proyek">Manajer Proyek — Monitor dan kelola proyek</option>
                        <option value="teknisi_lapangan">Teknisi Lapangan — Input laporan lapangan</option>
                        <option value="gudang">Gudang — Kelola stok komponen PLTS</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="password" id="pw1" class="form-input pr-10"
                                placeholder="Min. 6 karakter" required>
                            <button type="button" onclick="togglePw('pw1','eye1')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <i class="bi bi-eye text-sm" id="eye1"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Konfirmasi Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="password_confirmation" id="pw2" class="form-input pr-10"
                                placeholder="Ulangi password" required>
                            <button type="button" onclick="togglePw('pw2','eye2')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <i class="bi bi-eye text-sm" id="eye2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Info hak akses -->
                <div class="bg-slate-50 rounded-xl p-4 space-y-2">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Hak Akses Per Role</p>
                    <div class="grid grid-cols-2 gap-2 text-xs text-slate-600">
                        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-red-500"></span> Admin — semua fitur</div>
                        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Manajer — kelola proyek</div>
                        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-green-500"></span> Teknisi — laporan lapangan</div>
                        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-purple-500"></span> Gudang — stok material</div>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <a href="<?= BASE_URL ?>/pages/pengguna/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="bi bi-person-check"></i> Tambah Pengguna
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function togglePw(id, eyeId) {
    const el  = document.getElementById(id);
    const eye = document.getElementById(eyeId);
    el.type   = el.type === 'password' ? 'text' : 'password';
    eye.className = el.type === 'password' ? 'bi bi-eye text-sm' : 'bi bi-eye-slash text-sm';
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>