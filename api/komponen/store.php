<?php
// api/komponen/store.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','gudang']);

foreach (['nama_komponen','kategori','satuan','stok_tersedia','stok_minimum'] as $f) {
    if (!isset($_POST[$f]) || $_POST[$f] === '') {
        setFlash('error','Semua field wajib diisi.');
        redirect(BASE_URL.'/pages/komponen/create.php');
    }
}

$stmt = db()->prepare("INSERT INTO komponen (nama_komponen,kategori,satuan,stok_tersedia,stok_minimum) VALUES (?,?,?,?,?)");
$stmt->execute([trim($_POST['nama_komponen']),$_POST['kategori'],$_POST['satuan'],(int)$_POST['stok_tersedia'],(int)$_POST['stok_minimum']]);

setFlash('success','Komponen berhasil ditambahkan.');
redirect(BASE_URL.'/pages/komponen/index.php');