<?php // api/jadwal/store.php
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);
foreach(['proyek_id','tanggal_mulai','tanggal_selesai'] as $f) {
    if(empty($_POST[$f])){setFlash('error','Field wajib diisi.');redirect(BASE_URL.'/pages/jadwal/create.php');}
}
$stmt=db()->prepare("INSERT INTO jadwal(proyek_id,tanggal_mulai,tanggal_selesai,keterangan) VALUES(?,?,?,?)");
$stmt->execute([(int)$_POST['proyek_id'],$_POST['tanggal_mulai'],$_POST['tanggal_selesai'],trim($_POST['keterangan']??'')]);
$p=db()->prepare("SELECT nama_proyek FROM proyek WHERE id=?");$p->execute([(int)$_POST['proyek_id']]);
kirimNotifikasiKeRole('manajer_proyek','Jadwal Baru',"Jadwal ditambahkan untuk proyek \"{$p->fetchColumn()}\".");
setFlash('success','Jadwal berhasil ditambahkan.');redirect(BASE_URL.'/pages/jadwal/index.php');