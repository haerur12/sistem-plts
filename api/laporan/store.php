<?php
// api/laporan/store.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek','teknisi_lapangan']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(BASE_URL . '/pages/laporan/index.php');

$user = currentUser();
foreach (['proyek_id','judul_laporan','deskripsi','status_pengerjaan','tanggal_laporan'] as $f) {
    if (empty($_POST[$f])) {
        setFlash('error', 'Semua field wajib diisi.');
        redirect(BASE_URL . '/pages/laporan/create.php');
    }
}

$db = db();

// Simpan laporan
$stmt = $db->prepare("
    INSERT INTO laporan_lapangan (proyek_id, user_id, judul_laporan, deskripsi, status_pengerjaan, tanggal_laporan)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    (int)$_POST['proyek_id'],
    $user['id'],
    trim($_POST['judul_laporan']),
    trim($_POST['deskripsi']),
    $_POST['status_pengerjaan'],
    $_POST['tanggal_laporan'],
]);
$laporanId = $db->lastInsertId();

// Upload foto
if (!empty($_FILES['foto']['name'][0])) {
    $uploadDir = UPLOAD_PATH;
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    foreach ($_FILES['foto']['tmp_name'] as $i => $tmpName) {
        if ($_FILES['foto']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $ext      = strtolower(pathinfo($_FILES['foto']['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png'])) continue;
        if ($_FILES['foto']['size'][$i] > 5 * 1024 * 1024) continue;

        $namaFile = uniqid('foto_') . '.' . $ext;
        $pathFile = 'laporan/' . $namaFile;
        move_uploaded_file($tmpName, $uploadDir . $namaFile);

        $fotoStmt = $db->prepare("INSERT INTO foto_lapangan (laporan_id, nama_file, path_file) VALUES (?, ?, ?)");
        $fotoStmt->execute([$laporanId, $_FILES['foto']['name'][$i], $pathFile]);
    }
}

// Ambil nama proyek untuk notifikasi
$proyekStmt = $db->prepare("SELECT nama_proyek FROM proyek WHERE id = ?");
$proyekStmt->execute([(int)$_POST['proyek_id']]);
$namaProyek = $proyekStmt->fetchColumn();

kirimNotifikasiKeRole('manajer_proyek', 'Laporan Lapangan Baru', "Teknisi {$user['nama']} mengunggah laporan baru untuk proyek \"$namaProyek\".");
kirimNotifikasiKeRole('admin', 'Laporan Lapangan Baru', "Laporan baru diunggah untuk proyek \"$namaProyek\".");

setFlash('success', 'Laporan berhasil diunggah.');
redirect(BASE_URL . '/pages/laporan/index.php');