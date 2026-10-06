<?php
// api/komponen/update.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','gudang']);

$id = (int)($_POST['id'] ?? 0);
if (!$id) { setFlash('error','ID tidak valid.'); redirect(BASE_URL.'/pages/komponen/index.php'); }

$stmt = db()->prepare("UPDATE komponen SET nama_komponen=?,kategori=?,satuan=?,stok_tersedia=?,stok_minimum=? WHERE id=?");
$stmt->execute([trim($_POST['nama_komponen']),$_POST['kategori'],$_POST['satuan'],(int)$_POST['stok_tersedia'],(int)$_POST['stok_minimum'],$id]);

// Cek stok minimum setelah update
$cek = db()->prepare("SELECT * FROM komponen WHERE id = ?");
$cek->execute([$id]);
$k = $cek->fetch();
if ($k && $k['stok_tersedia'] <= $k['stok_minimum']) {
    kirimNotifikasiKeRole('gudang','Peringatan Stok Minimum',"Stok \"{$k['nama_komponen']}\" tersisa {$k['stok_tersedia']} {$k['satuan']} (minimum: {$k['stok_minimum']}).");
    kirimNotifikasiKeRole('admin','Peringatan Stok Minimum',"Stok \"{$k['nama_komponen']}\" perlu segera diisi ulang.");
}

setFlash('success','Komponen berhasil diperbarui.');
redirect(BASE_URL.'/pages/komponen/index.php');