<?php // api/jadwal/update.php
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);
$id=(int)($_POST['id']??0);
if(!$id){setFlash('error','ID tidak valid.');redirect(BASE_URL.'/pages/jadwal/index.php');}
$stmt=db()->prepare("UPDATE jadwal SET proyek_id=?,tanggal_mulai=?,tanggal_selesai=?,keterangan=?,status=? WHERE id=?");
$stmt->execute([(int)$_POST['proyek_id'],$_POST['tanggal_mulai'],$_POST['tanggal_selesai'],trim($_POST['keterangan']??''),$_POST['status'],$id]);
setFlash('success','Jadwal berhasil diperbarui.');redirect(BASE_URL.'/pages/jadwal/index.php');