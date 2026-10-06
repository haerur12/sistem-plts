<?php
// api/komponen/delete.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','gudang']);

$id = (int)($_POST['id'] ?? 0);
if (!$id) { setFlash('error','ID tidak valid.'); redirect(BASE_URL.'/pages/komponen/index.php'); }

db()->prepare("DELETE FROM komponen WHERE id = ?")->execute([$id]);
setFlash('success','Komponen berhasil dihapus.');
redirect(BASE_URL.'/pages/komponen/index.php');