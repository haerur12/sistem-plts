<?php
// api/pelanggan/update.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf()) {
    setFlash('error', 'Token keamanan tidak valid.');
    redirect(BASE_URL . '/pages/pelanggan/index.php');
}

$id   = (int)($_POST['id'] ?? 0);
$nama = trim($_POST['nama_pelanggan'] ?? '');
if (!$id || $nama === '') {
    setFlash('error', 'Data pelanggan tidak valid.');
    redirect(BASE_URL . '/pages/pelanggan/index.php');
}

$stmt = db()->prepare("UPDATE pelanggan SET nama_pelanggan = ?, alamat = ?, no_telp = ?, email = ? WHERE id = ?");
$stmt->execute([$nama, trim($_POST['alamat'] ?? ''), trim($_POST['no_telp'] ?? ''), trim($_POST['email'] ?? ''), $id]);

logActivity('update_pelanggan', 'update', "Memperbarui data pelanggan: $nama", 'pelanggan');
setFlash('success', 'Data pelanggan berhasil diperbarui.');
redirect(BASE_URL . '/pages/pelanggan/index.php');