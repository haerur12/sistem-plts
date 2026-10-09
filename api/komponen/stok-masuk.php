<?php
// api/komponen/stok-masuk.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','gudang']);
verifyCsrf();

$id          = (int)($_POST['komponen_id']  ?? 0);
$jumlah      = (int)($_POST['jumlah_masuk'] ?? 0);
$keterangan  = trim($_POST['keterangan']    ?? '');

if (!$id || $jumlah < 1) {
    setFlash('error', 'Data tidak valid. Pilih komponen dan masukkan jumlah.');
    redirect(BASE_URL . '/pages/komponen/index.php');
}

$db  = db();
$cek = $db->prepare("SELECT * FROM komponen WHERE id = ?");
$cek->execute([$id]);
$k   = $cek->fetch();

if (!$k) {
    setFlash('error', 'Komponen tidak ditemukan.');
    redirect(BASE_URL . '/pages/komponen/index.php');
}

// Tambahkan stok
$db->prepare("UPDATE komponen SET stok_tersedia = stok_tersedia + ? WHERE id = ?")
   ->execute([$jumlah, $id]);

// Catat di activity log
logActivity('STOK_MASUK', 'komponen', $id, "Stok masuk {$jumlah} {$k['satuan']} — {$keterangan}");

// Notif admin
$user = currentUser();
kirimNotifikasiKeRole('admin', 'Stok Masuk', "{$user['nama']} menambahkan stok \"{$k['nama_komponen']}\" sebanyak {$jumlah} {$k['satuan']}.");

setFlash('success', "Stok \"{$k['nama_komponen']}\" berhasil ditambah {$jumlah} {$k['satuan']}.");
redirect(BASE_URL . '/pages/komponen/index.php');