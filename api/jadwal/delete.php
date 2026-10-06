<?php // api/jadwal/delete.php
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);
$id=(int)($_POST['id']??0);
db()->prepare("DELETE FROM jadwal WHERE id=?")->execute([$id]);
setFlash('success','Jadwal berhasil dihapus.');redirect(BASE_URL.'/pages/jadwal/index.php');