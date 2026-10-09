<?php
// api/proyek/store.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(BASE_URL . '/pages/proyek/index.php');

$fields = ['pelanggan_id','nama_proyek','lokasi','tanggal_mulai','tanggal_selesai','deskripsi'];
foreach (['pelanggan_id','nama_proyek','lokasi','tanggal_mulai','tanggal_selesai'] as $f) {
    if (empty($_POST[$f])) {
        setFlash('error', 'Semua field wajib diisi.');
        redirect(BASE_URL . '/pages/proyek/create.php');
    }
}

if ($_POST['tanggal_selesai'] <= $_POST['tanggal_mulai']) {
    setFlash('error', 'Tanggal selesai harus setelah tanggal mulai.');
    redirect(BASE_URL . '/pages/proyek/create.php');
}

$latitude = trim($_POST['latitude'] ?? '');
$longitude = trim($_POST['longitude'] ?? '');
if (($latitude === '') !== ($longitude === '')) {
    setFlash('error', 'Latitude dan longitude harus diisi bersama.');
    redirect(BASE_URL . '/pages/proyek/create.php');
}
if ($latitude !== '' && (
    !is_numeric($latitude) || !is_numeric($longitude)
    || (float)$latitude < -90 || (float)$latitude > 90
    || (float)$longitude < -180 || (float)$longitude > 180
)) {
    setFlash('error', 'Koordinat tidak valid.');
    redirect(BASE_URL . '/pages/proyek/create.php');
}

$stmt = db()->prepare("
INSERT INTO proyek (pelanggan_id, nama_proyek, lokasi, latitude, longitude, tanggal_mulai, tanggal_selesai, deskripsi)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    (int)$_POST['pelanggan_id'],
    trim($_POST['nama_proyek']),
    trim($_POST['lokasi']),
    !empty($_POST['latitude']) ? $_POST['latitude'] : null,
    !empty($_POST['longitude']) ? $_POST['longitude'] : null,
    $_POST['tanggal_mulai'],
    $_POST['tanggal_selesai'],
    trim($_POST['deskripsi'] ?? ''),
]);

$proyekId = db()->lastInsertId();
$proyek   = db()->prepare("SELECT nama_proyek FROM proyek WHERE id = ?");
$proyek->execute([$proyekId]);
$namaProyek = $proyek->fetchColumn();

kirimNotifikasiKeRole('manajer_proyek', 'Proyek Baru Ditambahkan', "Proyek \"$namaProyek\" telah ditambahkan ke sistem.");

require_once __DIR__ . '/../../includes/activity.php';
logActivity('create_proyek', 'create', "Membuat proyek baru: " . trim($_POST['nama_proyek']), 'proyek');
setFlash('success', 'Proyek berhasil ditambahkan.');
redirect(BASE_URL . '/pages/proyek/index.php');