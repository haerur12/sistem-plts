<?php
// api/pengguna/update.php — UPDATE PENGGUNA & UNLINK GOOGLE ID
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/pengguna/index.php');
}

if (!verifyCsrf()) {
    setFlash('error', 'Token keamanan tidak valid.');
    redirect(BASE_URL . '/pages/pengguna/index.php');
}

$id     = (int)($_POST['id'] ?? 0);
$nama   = trim($_POST['nama'] ?? '');
$email  = strtolower(trim($_POST['email'] ?? ''));
$role   = $_POST['role'] ?? 'teknisi_lapangan';
$status = $_POST['status'] ?? 'aktif';
$unlinkGoogle = !empty($_POST['unlink_google']);

if (!$id || $nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Data pengguna tidak valid.');
    redirect(BASE_URL . '/pages/pengguna/index.php');
}

$db = db();

// Pastikan email tidak dipakai user lain
$cek = $db->prepare("SELECT COUNT(*) FROM users WHERE LOWER(email) = ? AND id != ?");
$cek->execute([$email, $id]);
if ($cek->fetchColumn() > 0) {
    setFlash('error', "Email $email sudah digunakan oleh akun lain.");
    redirect(BASE_URL . '/pages/pengguna/edit.php?id=' . $id);
}

// Jika email diubah atau opsi Lepas Tautan Google dicentang, kosongkan google_id agar bisa ditautkan ke email baru
if ($unlinkGoogle) {
    $db->prepare("UPDATE users SET google_id = NULL, avatar = NULL WHERE id = ?")->execute([$id]);
}

if (!empty($_POST['password'])) {
    if (strlen($_POST['password']) < 6) {
        setFlash('error', 'Password baru minimal 6 karakter.');
        redirect(BASE_URL . '/pages/pengguna/edit.php?id=' . $id);
    }
    $stmt = $db->prepare("UPDATE users SET nama = ?, email = ?, password = ?, role = ?, status = ? WHERE id = ?");
    $stmt->execute([$nama, $email, password_hash($_POST['password'], PASSWORD_BCRYPT), $role, $status, $id]);
} else {
    $stmt = $db->prepare("UPDATE users SET nama = ?, email = ?, role = ?, status = ? WHERE id = ?");
    $stmt->execute([$nama, $email, $role, $status, $id]);
}

logActivity('update_user', 'update', "Memperbarui akun pengguna: $nama ($email)", 'pengguna');
setFlash('success', "Data pengguna $nama berhasil diperbarui.");
redirect(BASE_URL . '/pages/pengguna/index.php');