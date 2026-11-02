<?php
// pages/profil/index.php — PENGATURAN PROFIL & PASSWORD PENGGUNA
$pageTitle   = 'Profil Saya';
$currentPage = 'profil';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/activity.php';
requireLogin();

$db   = db();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('error', 'Token keamanan tidak valid.');
        redirect(BASE_URL . '/pages/profil/index.php');
    }

    $nama   = trim($_POST['nama'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $pwBaru = $_POST['password_baru'] ?? '';

    if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Nama dan format email wajib valid.');
        redirect(BASE_URL . '/pages/profil/index.php');
    }

    if ($pwBaru !== '') {
        if (strlen($pwBaru) < 6) {
            setFlash('error', 'Password baru minimal 6 karakter.');
            redirect(BASE_URL . '/pages/profil/index.php');
        }
        $db->prepare("UPDATE users SET nama = ?, email = ?, password = ? WHERE id = ?")
           ->execute([$nama, $email, password_hash($pwBaru, PASSWORD_BCRYPT), $user['id']]);
    } else {
        $db->prepare("UPDATE users SET nama = ?, email = ? WHERE id = ?")
           ->execute([$nama, $email, $user['id']]);
    }

    $_SESSION['user']['nama']  = $nama;
    $_SESSION['user']['email'] = $email;

    logActivity('update_profil', 'update', 'Memperbarui profil akun pribadi', 'profil');
    setFlash('success', 'Profil Anda berhasil diperbarui.');
    redirect(BASE_URL . '/pages/profil/index.php');
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$profil = $stmt->fetch();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-xl mx-auto">
    <div class="mb-5">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Profil & Keamanan Akun</h2>
        <p class="text-xs text-slate-500 mt-0.5">Kelola informasi identitas dan kata sandi akun Anda</p>
    </div>

    <div class="card p-6">
        <form method="POST" class="space-y-4">
            <?= csrfField() ?>
            <div>
                <label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" name="nama" class="form-input" value="<?= sanitize($profil['nama']) ?>" required>
            </div>

            <div>
                <label class="form-label">Alamat Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" class="form-input" value="<?= sanitize($profil['email']) ?>" required>
            </div>

            <div>
                <label class="form-label">Role / Jabatan Sistem</label>
                <input type="text" class="form-input bg-slate-50 text-slate-500 cursor-not-allowed"
                       value="<?= strtoupper(str_replace('_', ' ', $profil['role'])) ?>" disabled>
            </div>

            <div class="pt-3 border-t border-slate-100">
                <label class="form-label">Password Baru <span class="text-slate-400 font-normal">(Kosongkan jika tidak ingin mengubah)</span></label>
                <input type="password" name="password_baru" class="form-input" placeholder="Minimal 6 karakter...">
            </div>

            <div class="pt-3 flex justify-end">
                <button type="submit" class="btn-primary">
                    <i class="bi bi-check-lg"></i> Simpan Perubahan Profil
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

