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

// Deteksi apakah ini quick update (hanya status & progres) atau full update
$isQuickUpdate = !isset($_POST['pelanggan_id']) && !isset($_POST['nama_proyek']);

if ($isQuickUpdate) {
    // Quick update: hanya status dan progres
    $stmt = db()->prepare("UPDATE proyek SET status=?, progres=? WHERE id=?");
    $stmt->execute([
        $_POST['status'] ?? $proyek['status'],
        (int)($_POST['progres'] ?? $proyek['progres']),
        $id
    ]);
    $namaProyek = $proyek['nama_proyek'];
} else {
    // Full update: semua field (hanya admin)
    if (!isAdmin()) {
        setFlash('error', 'Anda tidak memiliki izin untuk mengedit semua field.');
        redirect(BASE_URL . '/pages/proyek/show.php?id=' . $id);
    }
    
    // Validasi field wajib
    foreach (['pelanggan_id','nama_proyek','lokasi','tanggal_mulai','tanggal_selesai'] as $f) {
        if (empty($_POST[$f])) {
            setFlash('error', 'Field ' . str_replace('_', ' ', $f) . ' wajib diisi.');
            redirect(BASE_URL . '/pages/proyek/edit.php?id=' . $id);
        }
    }
    
    if ($_POST['tanggal_selesai'] <= $_POST['tanggal_mulai']) {
        setFlash('error', 'Tanggal selesai harus setelah tanggal mulai.');
        redirect(BASE_URL . '/pages/proyek/edit.php?id=' . $id);
    }
    
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
    $namaProyek = trim($_POST['nama_proyek']);
}

// Kirim notifikasi
kirimNotifikasiKeRole('admin', 'Proyek Diperbarui', "Proyek \"$namaProyek\" telah diperbarui.");

setFlash('success', 'Proyek berhasil diperbarui.');
redirect(BASE_URL . '/pages/proyek/show.php?id=' . $id);