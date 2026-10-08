<?php
// api/pengguna/delete.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin']);

$id = (int)($_POST['id'] ?? 0);
if (!$id) { setFlash('error','ID tidak valid.'); redirect(BASE_URL.'/pages/pengguna/index.php'); }

$user = currentUser();
if ($id === $user['id']) {
    setFlash('error','Tidak dapat menghapus akun sendiri.');
    redirect(BASE_URL.'/pages/pengguna/index.php');
}

$stmt = db()->prepare("SELECT nama FROM users WHERE id = ?");
$stmt->execute([$id]);
$nama = $stmt->fetchColumn();

db()->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
logActivity('delete_user', 'delete', "Menghapus pengguna: $nama", 'pengguna');

setFlash('success','Pengguna berhasil dihapus.');
redirect(BASE_URL.'/pages/pengguna/index.php');