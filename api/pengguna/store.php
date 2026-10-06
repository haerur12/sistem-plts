<?php // api/pengguna/store.php
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth.php';
requireRole(['admin']);
foreach(['nama','email','password','role'] as $f){
    if(empty($_POST[$f])){setFlash('error','Field wajib diisi.');redirect(BASE_URL.'/pages/pengguna/create.php');}
}
// Cek email unik
$cek=db()->prepare("SELECT COUNT(*) FROM users WHERE email=?");$cek->execute([$_POST['email']]);
if($cek->fetchColumn()>0){setFlash('error','Email sudah digunakan.');redirect(BASE_URL.'/pages/pengguna/create.php');}
$stmt=db()->prepare("INSERT INTO users(nama,email,password,role) VALUES(?,?,?,?)");
$stmt->execute([trim($_POST['nama']),trim($_POST['email']),password_hash($_POST['password'],PASSWORD_BCRYPT),$_POST['role']]);
setFlash('success','Pengguna berhasil ditambahkan.');redirect(BASE_URL.'/pages/pengguna/index.php');