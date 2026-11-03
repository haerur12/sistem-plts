<?php
// pages/pengguna/edit.php — EDIT PENGGUNA & KELOLA TAUTAN GOOGLE OAUTH
$pageTitle   = 'Edit Pengguna';
$currentPage = 'pengguna';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$db = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$u = $stmt->fetch();

if (!$u) {
    setFlash('error', 'Data pengguna tidak ditemukan.');
    redirect(BASE_URL . '/pages/pengguna/index.php');
}

$roleOptions = [
    'teknisi_lapangan' => ['label' => 'Teknisi Lapangan', 'icon' => 'bi-tools',            'desc' => 'Input daily report & dokumentasi lapangan'],
    'manajer_proyek'   => ['label' => 'Manajer Proyek',   'icon' => 'bi-briefcase-fill',   'desc' => 'Kelola proyek, jadwal, & evaluasi laporan'],
    'gudang'           => ['label' => 'Staf Gudang',      'icon' => 'bi-box-seam-fill',    'desc' => 'Kelola stok komponen & material proyek'],
    'admin'            => ['label' => 'Administrator',    'icon' => 'bi-shield-lock-fill', 'desc' => 'Akses penuh seluruh fitur & data master'],
];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <a href="<?= BASE_URL ?>/pages/pengguna/index.php">Manajemen Pengguna</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Edit Pengguna</span>
    </nav>

    <!-- Header -->
    <div class="flex items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-3.5">
            <a href="<?= BASE_URL ?>/pages/pengguna/index.php"
               class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-colors shadow-2xs">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Edit Akun Pengguna</h2>
                <p class="text-xs text-slate-500 mt-0.5"><?= sanitize($u['nama']) ?> — <?= sanitize($u['email']) ?></p>
            </div>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/pengguna/update.php" class="space-y-5">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $u['id'] ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" class="form-input" value="<?= sanitize($u['nama']) ?>" required>
                </div>
                <div>
                    <label class="form-label">Alamat Email (Gmail / Perusahaan) <span class="text-red-500">*</span></label>
                    <input type="email" name="email" class="form-input" value="<?= sanitize($u['email']) ?>" required>
                </div>
            </div>

            <!-- Status Tautan Google OAuth -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-sky-600 shadow-2xs">
                        <i class="bi bi-google"></i>
                    </span>
                    <div>
                        <div class="text-xs font-bold text-slate-800">Status Integrasi Login Google</div>
                        <?php if (!empty($u['google_id'])): ?>
                        <div class="text-[11px] text-emerald-600 font-semibold mt-0.5">
                            ✓ Terhubung dengan Google ID (<code class="text-[10px]"><?= sanitize(substr($u['google_id'], 0, 10)) ?>...</code>)
                        </div>
                        <?php else: ?>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            Otomatis terhubung saat pengguna pertama kali login memakai tombol Google dengan email <strong><?= sanitize($u['email']) ?></strong>.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($u['google_id'])): ?>
                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-red-200 text-red-600 text-xs font-bold cursor-pointer hover:bg-red-50">
                    <input type="checkbox" name="unlink_google" value="1" class="w-3.5 h-3.5 rounded text-red-600">
                    <span>Lepas Tautan Google</span>
                </label>
                <?php endif; ?>
            </div>

            <!-- Pilihan Role Jabatan -->
            <div>
                <label class="form-label">Role Jabatan <span class="text-red-500">*</span></label>
                <input type="hidden" name="role" id="selectedRole" value="<?= sanitize($u['role']) ?>" required>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1.5">
                    <?php foreach ($roleOptions as $rKey => $rMeta):
                        $isSel = ($u['role'] === $rKey);
                    ?>
                    <button type="button" onclick="pilihRole('<?= $rKey ?>', this)"
                        class="role-card-btn p-3.5 rounded-xl border text-left transition-all flex items-start gap-3 <?= $isSel ? 'border-primary-600 bg-primary-50/50 ring-2 ring-primary-500/20' : 'border-slate-200 bg-white hover:bg-slate-50' ?>">
                        <span class="w-9 h-9 rounded-lg bg-primary-100 text-primary-700 flex items-center justify-center flex-shrink-0">
                            <i class="bi <?= $rMeta['icon'] ?>"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-900"><?= $rMeta['label'] ?></div>
                            <div class="text-[11px] text-slate-400 mt-0.5 leading-snug"><?= $rMeta['desc'] ?></div>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="form-label">Status Akun</label>
                    <select name="status" class="form-select">
                        <option value="aktif" <?= $u['status'] === 'aktif' ? 'selected' : '' ?>>Aktif (Diizinkan Login)</option>
                        <option value="nonaktif" <?= $u['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif (Akses Diblokir)</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Reset Password <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="password" name="password" class="form-input" placeholder="Kosongkan jika tidak diubah...">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                <a href="<?= BASE_URL ?>/pages/pengguna/index.php" class="btn-secondary px-5">Batal</a>
                <button type="submit" class="btn-primary px-6">
                    <i class="bi bi-check-lg"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function pilihRole(roleKey, btnEl) {
    document.getElementById('selectedRole').value = roleKey;
    document.querySelectorAll('.role-card-btn').forEach(b => {
        b.className = 'role-card-btn p-3.5 rounded-xl border text-left transition-all flex items-start gap-3 border-slate-200 bg-white hover:bg-slate-50';
    });
    btnEl.className = 'role-card-btn p-3.5 rounded-xl border text-left transition-all flex items-start gap-3 border-primary-600 bg-primary-50/50 ring-2 ring-primary-500/20';
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>