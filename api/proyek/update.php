<?php
// api/proyek/update.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(BASE_URL . '/pages/proyek/index.php');

$id = (int)($_POST['id'] ?? 0);
if (!$id) { setFlash('error', 'ID tidak valid.'); redirect(BASE_URL . '/pages/proyek/index.php'); }

// Cek proyek ada
$cek = db()->prepare("SELECT * FROM proyek WHERE id = ?");
$cek->execute([$id]);
$proyek = $cek->fetch();
if (!$proyek) { setFlash('error', 'Proyek tidak ditemukan.'); redirect(BASE_URL . '/pages/proyek/index.php'); }

// Admin bisa edit semua field, Manajer hanya status dan progres
if (isAdmin()) {
    $stmt = db()->prepare("
        UPDATE proyek SET pelanggan_id=?, nama_proyek=?, lokasi=?, tanggal_mulai=?,
        tanggal_selesai=?, status=?, progres=?, deskripsi=?
        WHERE id=?
    ");
    $stmt->execute([
        (int)$_POST['pelanggan_id'],
        trim($_POST['nama_proyek']),
        trim($_POST['lokasi']),
        $_POST['tanggal_mulai'],
        $_POST['tanggal_selesai'],
        $_POST['status'],
        (int)$_POST['progres'],
        trim($_POST['deskripsi'] ?? ''),
        $id,
    ]);
} else {
    $stmt = db()->prepare("UPDATE proyek SET status=?, progres=? WHERE id=?");
    $stmt->execute([$_POST['status'], (int)$_POST['progres'], $id]);
}

$namaProyek = trim($_POST['nama_proyek'] ?? $proyek['nama_proyek']);
kirimNotifikasiKeRole('admin', 'Proyek Diperbarui', "Proyek \"$namaProyek\" telah diperbarui.");

require_once __DIR__ . '/../../includes/activity.php';
logActivity('update_proyek', 'update', "Memperbarui proyek: " . ($proyek['nama_proyek'] ?? ''), 'proyek');
setFlash('success', 'Proyek berhasil diperbarui.');
redirect(BASE_URL . '/pages/proyek/show.php?id=' . $id);