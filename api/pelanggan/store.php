<?php // api/pelanggan/store.php
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth.php';
requireRole(['admin']);
foreach(['nama_pelanggan','alamat','no_telp'] as $f){
    if(empty($_POST[$f])){setFlash('error','Field wajib diisi.');redirect(BASE_URL.'/pages/pelanggan/create.php');}
}
$stmt=db()->prepare("INSERT INTO pelanggan(nama_pelanggan,alamat,no_telp,email) VALUES(?,?,?,?)");
$stmt->execute([trim($_POST['nama_pelanggan']),trim($_POST['alamat']),trim($_POST['no_telp']),trim($_POST['email']??'')]);
setFlash('success','Pelanggan berhasil ditambahkan.');redirect(BASE_URL.'/pages/pelanggan/index.php');