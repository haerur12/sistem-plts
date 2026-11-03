<?php
// api/auth/login.php — DENGAN BRUTE-FORCE RATE LIMITING & CSRF
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/auth/login.php');
}

if (!verifyCsrf()) {
    setFlash('error', 'Sesi formulir kedaluwarsa, silakan coba lagi.');
    redirect(BASE_URL . '/pages/auth/login.php');
}

// Cek apakah sedang terkunci karena terlalu banyak gagal login (Rate Limiting)
$lockoutTime = $_SESSION['login_lockout_until'] ?? 0;
if (time() < $lockoutTime) {
    $sisaDetik = $lockoutTime - time();
    $sisaMenit = ceil($sisaDetik / 60);
    setFlash('error', "Terlalu banyak percobaan gagal. Silakan tunggu {$sisaMenit} menit lagi.");
    redirect(BASE_URL . '/pages/auth/login.php');
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
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
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;

    if ($_SESSION['login_attempts'] >= 5) {
        $_SESSION['login_lockout_until'] = time() + 300; // Kunci selama 5 menit (300 detik)
        $_SESSION['login_attempts'] = 0;
        setFlash('error', 'Terlalu banyak percobaan login gagal. Akun dikunci sementara selama 5 menit.');
    } else {
        $sisa = 5 - $_SESSION['login_attempts'];
        setFlash('error', "Email atau password salah. Sisa percobaan: {$sisa}x.");
    }
    redirect(BASE_URL . '/pages/auth/login.php');
}

// Reset hitungan gagal jika berhasil login
unset($_SESSION['login_attempts'], $_SESSION['login_lockout_until']);

session_regenerate_id(true);
$_SESSION['user'] = [
    'id'     => $user['id'],
    'nama'   => $user['nama'],
    'email'  => $user['email'],
    'role'   => $user['role'],
    'avatar' => $user['avatar'] ?? null,
];

require_once __DIR__ . '/../../includes/activity.php';
updateLastSeen();
logActivity('login', 'login', 'Masuk ke sistem', 'auth');
setFlash('success', 'Selamat datang, ' . $user['nama'] . '!');
redirect(BASE_URL . '/pages/dashboard/index.php');