<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','teknisi_lapangan','manajer_proyek']);

$id   = (int)($_POST['id'] ?? 0);
$user = currentUser();

if (!$id) {
    setFlash('error', 'ID tidak valid.');
    redirect(BASE_URL . '/pages/laporan/index.php');
}

$db   = db();
$stmt = $db->prepare("SELECT * FROM laporan_lapangan WHERE id = ?");
$stmt->execute([$id]);
$laporan = $stmt->fetch();

if (!$laporan) {
    setFlash('error', 'Laporan tidak ditemukan.');
    redirect(BASE_URL . '/pages/laporan/index.php');
}

// Teknisi hanya bisa hapus milik sendiri
if (isTeknisi() && $laporan['user_id'] !== (int)$user['id']) {
    setFlash('error', 'Anda tidak memiliki akses untuk menghapus laporan ini.');
    redirect(BASE_URL . '/pages/laporan/index.php');
}

// Hapus foto dari storage
$fotos = $db->prepare("SELECT * FROM foto_lapangan WHERE laporan_id = ?");
$fotos->execute([$id]);
foreach ($fotos->fetchAll() as $foto) {
    $filePath = UPLOAD_PATH . basename($foto['path_file']);
    if (file_exists($filePath)) unlink($filePath);
}

// Hapus record
$db->prepare("DELETE FROM foto_lapangan WHERE laporan_id = ?")->execute([$id]);
$db->prepare("DELETE FROM laporan_lapangan WHERE id = ?")->execute([$id]);

setFlash('success', 'Laporan berhasil dihapus beserta semua foto dokumentasi.');
redirect(BASE_URL . '/pages/laporan/index.php');