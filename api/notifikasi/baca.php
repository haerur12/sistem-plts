<?php // api/notifikasi/baca.php
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth.php';
requireLogin();
$user=$_SESSION['user'];
$id=(int)($_POST['id']??0);
if($id){db()->prepare("UPDATE notifikasi SET status_baca='sudah_dibaca' WHERE id=? AND user_id=?")->execute([$id,$user['id']]);}
redirect(BASE_URL.'/pages/notifikasi/index.php');