<?php
// api/komponen/stok-masuk.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin','gudang']);

if (!verifyCsrf()) {
    setFlash('error', 'Token keamanan tidak valid. Silakan refresh halaman dan coba lagi.');
    redirect(BASE_URL . '/pages/komponen/index.php');
}

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

$user = currentUser();
logActivity(
    'stok_masuk',
    'create',
    "Stok masuk \"{$k['nama_komponen']}\" sebanyak {$jumlah} {$k['satuan']}" .
        ($keterangan !== '' ? " — {$keterangan}" : ''),
    'komponen',
    ['id' => $id, 'jumlah' => $jumlah, 'keterangan' => $keterangan]
);

kirimNotifikasiKeRole('admin', 'Stok Masuk', "{$user['nama']} menambahkan stok \"{$k['nama_komponen']}\" sebanyak {$jumlah} {$k['satuan']}.");

setFlash('success', "Stok \"{$k['nama_komponen']}\" berhasil ditambah {$jumlah} {$k['satuan']}.");
redirect(BASE_URL . '/pages/komponen/index.php');