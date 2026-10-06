<?php // api/pelanggan/update.php
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth.php';
requireRole(['admin']);
$id=(int)($_POST['id']??0);
if(!$id){setFlash('error','ID tidak valid.');redirect(BASE_URL.'/pages/pelanggan/index.php');}
$stmt=db()->prepare("UPDATE pelanggan SET nama_pelanggan=?,alamat=?,no_telp=?,email=? WHERE id=?");
$stmt->execute([trim($_POST['nama_pelanggan']),trim($_POST['alamat']),trim($_POST['no_telp']),trim($_POST['email']??''),$id]);
setFlash('success','Pelanggan berhasil diperbarui.');redirect(BASE_URL.'/pages/pelanggan/index.php');