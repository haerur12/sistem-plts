<?php
// api/operator/toggle-user.php — BEKUKAN ATAU AKTIFKAN AKSES PENGGUNA INSTAN
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/activity.php';

requireLogin();
if (!isOperator()) {
    http_response_code(403);
    exit('Akses ditolak');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf()) {
    redirect(BASE_URL . '/pages/operator/akses.php');
}

$targetId = (int)($_POST['user_id'] ?? 0);
if ($targetId === (int)currentUser()['id']) {
    setFlash('error', 'Anda tidak dapat membekukan akun Anda sendiri.');
    redirect(BASE_URL . '/pages/operator/akses.php');
}

$db = db();
$stmt = $db->prepare("SELECT id, nama, status FROM users WHERE id = ?");
$stmt->execute([$targetId]);
$u = $stmt->fetch();

if ($u) {
    $statusBaru = ($u['status'] === 'aktif') ? 'nonaktif' : 'aktif';
    $db->prepare("UPDATE users SET status = ?, is_online = 0 WHERE id = ?")->execute([$statusBaru, $targetId]);

    logActivity(
        'toggle_akses_user',
        'update',
        "Mengubah status akses {$u['nama']} menjadi $statusBaru",
        'operator'
    );
    setFlash('success', "Status akses {$u['nama']} berhasil diubah menjadi " . strtoupper($statusBaru) . ".");
}

redirect(BASE_URL . '/pages/operator/akses.php');