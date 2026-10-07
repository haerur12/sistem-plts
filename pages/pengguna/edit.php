<?php
$pageTitle   = 'Edit Pengguna';
$currentPage = 'pengguna';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$id   = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$u = $stmt->fetch();
if (!$u) { setFlash('error','Pengguna tidak ditemukan.'); redirect(BASE_URL.'/pages/pengguna/index.php'); }

$isSelf = $id === (int)currentUser()['id'];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-lg mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/pengguna/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Edit Pengguna</h2>
            <p class="text-sm text-slate-400"><?= sanitize($u['nama']) ?></p>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/pengguna/update.php">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="space-y-4">

                <div>
                    <label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" class="form-input"
                        value="<?= sanitize($u['nama']) ?>" required>
                </div>

                <div>
                    <label class="form-label">Email <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <i class="bi bi-envelope absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="email" name="email" class="form-input pl-9"
                            value="<?= sanitize($u['email']) ?>" required>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" <?= $isSelf ? 'disabled' : '' ?>>
                            <option value="admin"            <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                            <option value="manajer_proyek"   <?= $u['role']==='manajer_proyek'?'selected':'' ?>>Manajer Proyek</option>
                            <option value="teknisi_lapangan" <?= $u['role']==='teknisi_lapangan'?'selected':'' ?>>Teknisi Lapangan</option>
                            <option value="gudang"           <?= $u['role']==='gudang'?'selected':'' ?>>Gudang</option>
                        </select>
                        <?php if ($isSelf): ?>
                        <input type="hidden" name="role" value="<?= $u['role'] ?>">
                        <p class="text-xs text-slate-400 mt-1">Tidak dapat mengubah role sendiri</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" <?= $isSelf ? 'disabled' : '' ?>>
                            <option value="aktif"    <?= $u['status']==='aktif'?'selected':'' ?>>Aktif</option>
                            <option value="nonaktif" <?= $u['status']==='nonaktif'?'selected':'' ?>>Nonaktif</option>
                        </select>
                        <?php if ($isSelf): ?>
                        <input type="hidden" name="status" value="<?= $u['status'] ?>">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ganti Password (opsional) -->
                <div class="bg-slate-50 rounded-xl p-4 space-y-3">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Ganti Password (opsional)</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="relative">
                            <input type="password" name="password" id="pw1" class="form-input pr-10 text-sm"
                                placeholder="Password baru">
                            <button type="button" onclick="togglePw('pw1','eye1')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <i class="bi bi-eye text-sm" id="eye1"></i>
                            </button>
                        </div>
                        <div class="relative">
                            <input type="password" name="password_confirmation" id="pw2" class="form-input pr-10 text-sm"
                                placeholder="Konfirmasi password baru">
                            <button type="button" onclick="togglePw('pw2','eye2')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <i class="bi bi-eye text-sm" id="eye2"></i>
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400">Kosongkan jika tidak ingin mengganti password</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <a href="<?= BASE_URL ?>/pages/pengguna/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="bi bi-check-lg"></i> Simpan Perubahan
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