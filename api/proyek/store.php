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

$stmt = db()->prepare("
    INSERT INTO proyek (pelanggan_id, nama_proyek, lokasi, tanggal_mulai, tanggal_selesai, deskripsi)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    (int)$_POST['pelanggan_id'],
    trim($_POST['nama_proyek']),
    trim($_POST['lokasi']),
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