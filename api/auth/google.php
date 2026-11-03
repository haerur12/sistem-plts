<?php
// File: api/auth/google.php — VERIFIKASI LOGIN GOOGLE OAUTH 2.0
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/activity.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/auth/login.php');
}

// 1. Validasi keberadaan Credential Token dari Google
$idToken = trim($_POST['credential'] ?? '');
if ($idToken === '') {
    setFlash('error', 'Token otentikasi Google tidak ditemukan.');
    redirect(BASE_URL . '/pages/auth/login.php');
}

// 2. Verifikasi Token ke Endpoint Resmi Google OAuth2
$verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $verifyUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    setFlash('error', 'Gagal memverifikasi akun Google. Pastikan koneksi internet aktif.');
    redirect(BASE_URL . '/pages/auth/login.php');
}

$payload = json_decode($response, true);

// 3. Pastikan token ditujukan untuk aplikasi ini (Client ID cocok) & email terverifikasi
if (
    empty($payload['sub']) ||
    empty($payload['email']) ||
    ($payload['email_verified'] ?? '') !== 'true' ||
    ($payload['aud'] ?? '') !== GOOGLE_CLIENT_ID
) {
    setFlash('error', 'Token Google tidak valid atau tidak sesuai dengan kredensial aplikasi.');
    redirect(BASE_URL . '/pages/auth/login.php');
}

$googleId = $payload['sub'];
$email    = strtolower(trim($payload['email']));
$nama     = $payload['name'] ?? '';
$avatar   = $payload['picture'] ?? null;

$db = db();

// 4. Cari pengguna berdasarkan google_id atau alamat email di tabel users
$stmt = $db->prepare("SELECT * FROM users WHERE (google_id = ? OR LOWER(email) = ?) LIMIT 1");
$stmt->execute([$googleId, $email]);
$user = $stmt->fetch();

if (!$user) {
    // Keamanan Sistem Internal: Tolak jika email Google belum didaftarkan oleh Admin
    setFlash('error', "Email Google ($email) belum terdaftar di sistem PT Energy Lima Pilar. Hubungi Administrator.");
    redirect(BASE_URL . '/pages/auth/login.php');
}

if ($user['status'] !== 'aktif') {
    setFlash('error', 'Akun Anda sedang dinonaktifkan. Silakan hubungi Administrator.');
    redirect(BASE_URL . '/pages/auth/login.php');
}

// 5. Tautkan google_id & foto profil Google ke akun user jika belum tersimpan
$stmtUpdate = $db->prepare("UPDATE users SET google_id = ?, avatar = ?, last_seen = NOW(), is_online = 1 WHERE id = ?");
$stmtUpdate->execute([$googleId, $avatar, $user['id']]);

// 6. Buat session yang aman
session_regenerate_id(true);
$_SESSION['user'] = [
    'id'     => $user['id'],
    'nama'   => $user['nama'],
    'email'  => $user['email'],
    'role'   => $user['role'],
    'avatar' => $avatar,
];

logActivity('login_google', 'login', 'Masuk ke sistem menggunakan Akun Google', 'auth');
setFlash('success', 'Selamat datang kembali, ' . $user['nama'] . '!');
redirect(BASE_URL . '/pages/dashboard/index.php');