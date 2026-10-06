<?php // api/pengguna/update.php
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth.php';
requireRole(['admin']);
$id=(int)($_POST['id']??0);
if(!$id){setFlash('error','ID tidak valid.');redirect(BASE_URL.'/pages/pengguna/index.php');}
$data=[trim($_POST['nama']),trim($_POST['email']),$_POST['role'],$_POST['status']??'aktif',$id];
$sql="UPDATE users SET nama=?,email=?,role=?,status=? WHERE id=?";
if(!empty($_POST['password'])){
    array_splice($data,2,0,[password_hash($_POST['password'],PASSWORD_BCRYPT)]);
    $sql="UPDATE users SET nama=?,email=?,password=?,role=?,status=? WHERE id=?";
}
db()->prepare($sql)->execute($data);
setFlash('success','Pengguna berhasil diperbarui.');redirect(BASE_URL.'/pages/pengguna/index.php');