<?php
// api/pelanggan/store.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf()) {
    setFlash('error', 'Token keamanan tidak valid.');
    redirect(BASE_URL . '/pages/pelanggan/create.php');
}

foreach (['nama_pelanggan', 'alamat', 'no_telp'] as $f) {
    if (empty(trim($_POST[$f] ?? ''))) {
        setFlash('error', 'Nama pelanggan, alamat, dan nomor telepon wajib diisi.');
        redirect(BASE_URL . '/pages/pelanggan/create.php');
    }
}

$nama = trim($_POST['nama_pelanggan']);
$stmt = db()->prepare("INSERT INTO pelanggan (nama_pelanggan, alamat, no_telp, email) VALUES (?, ?, ?, ?)");
$stmt->execute([$nama, trim($_POST['alamat']), trim($_POST['no_telp']), trim($_POST['email'] ?? '')]);

logActivity('create_pelanggan', 'create', "Menambahkan pelanggan baru: $nama", 'pelanggan');
setFlash('success', 'Pelanggan berhasil ditambahkan.');
redirect(BASE_URL . '/pages/pelanggan/index.php');