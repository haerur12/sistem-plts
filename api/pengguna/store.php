<?php
// api/pengguna/store.php — SIMPAN PENGGUNA BARU DENGAN PROTEKSI CSRF & DUKUNGAN GOOGLE LOGIN
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
    redirect(BASE_URL . '/pages/pengguna/create.php');
}

$nama  = trim($_POST['nama'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$role  = $_POST['role'] ?? 'teknisi_lapangan';
$isAutoPass = !empty($_POST['auto_password']);
$rawPassword = $_POST['password'] ?? '';

if ($nama === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Nama lengkap dan format alamat email wajib valid.');
    redirect(BASE_URL . '/pages/pengguna/create.php');
}

$validRoles = ['admin', 'manajer_proyek', 'teknisi_lapangan', 'gudang'];
if (!in_array($role, $validRoles, true)) {
    setFlash('error', 'Role jabatan tidak valid.');
    redirect(BASE_URL . '/pages/pengguna/create.php');
}

// Jika admin mencentang "Hanya Login via Google", buatkan random password kriptografis
if ($isAutoPass) {
    $rawPassword = bin2hex(random_bytes(16));
} elseif (strlen($rawPassword) < 6) {
    setFlash('error', 'Password minimal harus 6 karakter.');
    redirect(BASE_URL . '/pages/pengguna/create.php');
}

$db = db();
$cek = $db->prepare("SELECT COUNT(*) FROM users WHERE LOWER(email) = ?");
$cek->execute([$email]);
if ($cek->fetchColumn() > 0) {
    setFlash('error', "Alamat email $email sudah terdaftar di sistem.");
    redirect(BASE_URL . '/pages/pengguna/create.php');
}

$stmt = $db->prepare("INSERT INTO users (nama, email, password, role, status) VALUES (?, ?, ?, ?, 'aktif')");
$stmt->execute([
    $nama,
    $email,
    password_hash($rawPassword, PASSWORD_BCRYPT),
    $role
]);

logActivity('create_user', 'create', "Mendaftarkan pengguna baru: $nama ($email) sebagai $role", 'pengguna');
setFlash('success', "Pengguna $nama ($email) berhasil didaftarkan dan siap login menggunakan Google.");
redirect(BASE_URL . '/pages/pengguna/index.php');