<?php
// api/auth/login.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/auth/login.php');
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

if (!$email || !$password) {
    setFlash('error', 'Email dan password wajib diisi.');
    redirect(BASE_URL . '/pages/auth/login.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Format email tidak valid.');
    redirect(BASE_URL . '/pages/auth/login.php');
}

$stmt = db()->prepare("SELECT * FROM users WHERE email = ? AND status = 'aktif' LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    setFlash('error', 'Email atau password salah, atau akun tidak aktif.');
    redirect(BASE_URL . '/pages/auth/login.php');
}

// Set session
$_SESSION['user'] = [
    'id'    => $user['id'],
    'nama'  => $user['nama'],
    'email' => $user['email'],
    'role'  => $user['role'],
];

session_regenerate_id(true);
setFlash('success', 'Selamat datang, ' . $user['nama'] . '!');
redirect(BASE_URL . '/pages/dashboard/index.php');