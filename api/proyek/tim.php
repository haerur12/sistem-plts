<?php
// api/proyek/tim.php — TAMBAH / HAPUS PERSONEL TIM DARI DETAIL PROYEK
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/activity.php';

requireRole(['admin', 'manajer_proyek']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/proyek/index.php');
}

$proyek_id = (int)($_POST['proyek_id'] ?? 0);
if (!$proyek_id) {
    redirect(BASE_URL . '/pages/proyek/index.php');
}

if (!verifyCsrf()) {
    setFlash('error', 'Token keamanan tidak valid.');
    redirect(BASE_URL . '/pages/proyek/show.php?id=' . $proyek_id);
}

$db   = db();
$aksi = $_POST['aksi'] ?? 'tambah';

if ($aksi === 'hapus') {
    $tim_id = (int)($_POST['tim_id'] ?? 0);
    $stmt = $db->prepare("SELECT nama_personel, peran_proyek FROM proyek_tim WHERE id = ? AND proyek_id = ?");
    $stmt->execute([$tim_id, $proyek_id]);
    $row = $stmt->fetch();

    if ($row) {
        $db->prepare("DELETE FROM proyek_tim WHERE id = ? AND proyek_id = ?")->execute([$tim_id, $proyek_id]);
        logActivity('hapus_tim_proyek', 'delete', "Mengeluarkan {$row['nama_personel']} ({$row['peran_proyek']}) dari proyek #$proyek_id", 'proyek');
        setFlash('success', "Personel {$row['nama_personel']} berhasil dihapus dari tim proyek.");
    }
    redirect(BASE_URL . '/pages/proyek/show.php?id=' . $proyek_id);
}

// Aksi: Tambah Personel ke Proyek
$tipe_input = $_POST['tipe_personel'] ?? 'akun';
$peran      = trim($_POST['peran_proyek'] ?? 'Teknisi Lapangan');
$no_hp      = trim($_POST['no_hp'] ?? '');

$stmtProyek = $db->prepare("SELECT nama_proyek FROM proyek WHERE id = ?");
$stmtProyek->execute([$proyek_id]);
$namaProyek = $stmtProyek->fetchColumn() ?: "Proyek #$proyek_id";

if ($tipe_input === 'akun') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if (!$user_id) {
        setFlash('error', 'Pilih akun staf/teknisi terlebih dahulu.');
        redirect(BASE_URL . '/pages/proyek/show.php?id=' . $proyek_id);
    }

    // Cek apakah sudah terdaftar di proyek ini
    $cek = $db->prepare("SELECT COUNT(*) FROM proyek_tim WHERE proyek_id = ? AND user_id = ?");
    $cek->execute([$proyek_id, $user_id]);
    if ($cek->fetchColumn() > 0) {
        setFlash('error', 'Staf tersebut sudah terdaftar di tim proyek ini.');
        redirect(BASE_URL . '/pages/proyek/show.php?id=' . $proyek_id);
    }

    $uStmt = $db->prepare("SELECT nama FROM users WHERE id = ?");
    $uStmt->execute([$user_id]);
    $namaPersonel = $uStmt->fetchColumn();

    if ($namaPersonel) {
        $db->prepare("INSERT INTO proyek_tim (proyek_id, user_id, nama_personel, peran_proyek, no_hp) VALUES (?, ?, ?, ?, ?)")
           ->execute([$proyek_id, $user_id, $namaPersonel, $peran, $no_hp !== '' ? $no_hp : null]);

        kirimNotifikasi($user_id, 'Penempatan Proyek', "Anda telah ditempatkan sebagai {$peran} pada proyek \"{$namaProyek}\".");
        logActivity('tambah_tim_proyek', 'create', "Menempatkan $namaPersonel sebagai $peran di proyek $namaProyek", 'proyek');
        setFlash('success', "$namaPersonel berhasil ditambahkan ke tim proyek.");
    }
} else {
    $namaManual = trim($_POST['nama_personel'] ?? '');
    if ($namaManual === '') {
        setFlash('error', 'Nama personel wajib diisi.');
        redirect(BASE_URL . '/pages/proyek/show.php?id=' . $proyek_id);
    }

    $db->prepare("INSERT INTO proyek_tim (proyek_id, user_id, nama_personel, peran_proyek, no_hp) VALUES (?, NULL, ?, ?, ?)")
       ->execute([$proyek_id, $namaManual, $peran, $no_hp !== '' ? $no_hp : null]);

    logActivity('tambah_tim_proyek', 'create', "Menambahkan personel $namaManual ($peran) di proyek $namaProyek", 'proyek');
    setFlash('success', "Personel $namaManual berhasil ditambahkan ke tim proyek.");
}

redirect(BASE_URL . '/pages/proyek/show.php?id=' . $proyek_id);