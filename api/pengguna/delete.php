<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$id          = (int)($_POST['id'] ?? 0);
$statusSaatIni = $_POST['status_saat_ini'] ?? 'aktif';

if (!$id) {
    setFlash('error', 'ID tidak valid.');
    redirect(BASE_URL . '/pages/pengguna/index.php');
}

if ($id === (int)currentUser()['id']) {
    setFlash('error', 'Tidak dapat mengubah status akun yang sedang digunakan.');
    redirect(BASE_URL . '/pages/pengguna/index.php');
}

// Toggle status aktif/nonaktif
$statusBaru = $statusSaatIni === 'aktif' ? 'nonaktif' : 'aktif';
db()->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$statusBaru, $id]);

$pesan = $statusBaru === 'nonaktif' ? 'Pengguna berhasil dinonaktifkan.' : 'Pengguna berhasil diaktifkan kembali.';
setFlash('success', $pesan);
redirect(BASE_URL . '/pages/pengguna/index.php');