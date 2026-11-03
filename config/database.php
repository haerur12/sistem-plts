<?php
// config/database.php
define('DB_HOST',     'localhost');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_NAME',     'sistem_plts');
define('BASE_URL',    'http://localhost/sistem-plts');
define('UPLOAD_PATH', __DIR__ . '/../uploads/laporan/');

// Ganti dengan Client ID dari Google Cloud Console Anda
// (APIs & Services -> Credentials -> Create OAuth 2.0 Client ID -> Web Application)
define('GOOGLE_CLIENT_ID', '817589836436-3u7qkl3i6dp68i0q2m9c3fg1hn5j4vs5.apps.googleusercontent.com');

// Pengamanan Cookie Session (HttpOnly & SameSite)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            error_log('Database Error: ' . $e->getMessage());
            http_response_code(500);
            die('Sistem sedang mengalami gangguan koneksi database.');
        }
    }
    return $pdo;
}