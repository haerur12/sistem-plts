<?php // api/pelanggan/delete.php
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth.php';
requireRole(['admin']);
$id=(int)($_POST['id']??0);
// Cek proyek aktif
$cek=db()->prepare("SELECT COUNT(*) FROM proyek WHERE pelanggan_id=? AND status IN('survei','perencanaan','instalasi')");
$cek->execute([$id]);
if($cek->fetchColumn()>0){setFlash('error','Pelanggan tidak dapat dihapus karena masih memiliki proyek aktif.');redirect(BASE_URL.'/pages/pelanggan/index.php');}
db()->prepare("DELETE FROM pelanggan WHERE id=?")->execute([$id]);
setFlash('success','Pelanggan berhasil dihapus.');redirect(BASE_URL.'/pages/pelanggan/index.php');