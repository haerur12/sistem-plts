<?php
// api/pengguna/delete.php — HAPUS PENGGUNA DENGAN PROTEKSI CSRF
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf()) {
    setFlash('error', 'Permintaan tidak valid.');
    redirect(BASE_URL . '/pages/pengguna/index.php');
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    setFlash('error', 'ID tidak valid.');
    redirect(BASE_URL . '/pages/pengguna/index.php');
}

$user = currentUser();
if ($id === (int)$user['id']) {
    setFlash('error', 'Tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
    redirect(BASE_URL . '/pages/pengguna/index.php');
}

$stmt = db()->prepare("SELECT nama, email FROM users WHERE id = ?");
$stmt->execute([$id]);
$target = $stmt->fetch();

if ($target) {
    db()->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    logActivity('delete_user', 'delete', "Menghapus pengguna: {$target['nama']} ({$target['email']})", 'pengguna');
    setFlash('success', 'Pengguna berhasil dihapus.');
}

redirect(BASE_URL . '/pages/pengguna/index.php');