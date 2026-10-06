<?php
// api/material/store.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','gudang']);

$user = currentUser();
foreach (['proyek_id','komponen_id','jumlah_digunakan','tanggal_penggunaan'] as $f) {
    if (empty($_POST[$f])) { setFlash('error','Field wajib diisi.'); redirect(BASE_URL.'/pages/material/create.php'); }
}

$db        = db();
$komponen  = $db->prepare("SELECT * FROM komponen WHERE id = ?");
$komponen->execute([(int)$_POST['komponen_id']]);
$k = $komponen->fetch();

if (!$k) { setFlash('error','Komponen tidak ditemukan.'); redirect(BASE_URL.'/pages/material/create.php'); }

$jml = (int)$_POST['jumlah_digunakan'];
if ($jml > $k['stok_tersedia']) {
    setFlash('error',"Jumlah ($jml) melebihi stok tersedia ({$k['stok_tersedia']} {$k['satuan']}).");
    redirect(BASE_URL.'/pages/material/create.php');
}

// Simpan catatan
$stmt = $db->prepare("INSERT INTO penggunaan_material (proyek_id,komponen_id,user_id,jumlah_digunakan,tanggal_penggunaan,keterangan) VALUES (?,?,?,?,?,?)");
$stmt->execute([(int)$_POST['proyek_id'],(int)$_POST['komponen_id'],$user['id'],$jml,$_POST['tanggal_penggunaan'],trim($_POST['keterangan']??'')]);

// Kurangi stok
$db->prepare("UPDATE komponen SET stok_tersedia = stok_tersedia - ? WHERE id = ?")->execute([$jml,$k['id']]);

// Cek stok minimum
$cek = $db->prepare("SELECT stok_tersedia, stok_minimum FROM komponen WHERE id = ?");
$cek->execute([$k['id']]);
$updated = $cek->fetch();
if ($updated['stok_tersedia'] <= $updated['stok_minimum']) {
    kirimNotifikasiKeRole('gudang','Peringatan Stok Minimum',"Stok \"{$k['nama_komponen']}\" tersisa {$updated['stok_tersedia']} {$k['satuan']} (minimum: {$k['stok_minimum']}).");
    kirimNotifikasiKeRole('admin','Peringatan Stok Minimum',"Stok \"{$k['nama_komponen']}\" perlu segera diisi ulang.");
}

setFlash('success','Penggunaan material berhasil dicatat dan stok diperbarui.');
redirect(BASE_URL.'/pages/material/index.php');