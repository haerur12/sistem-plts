<?php
// api/proyek/bulk.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';

$action = $_POST['action'] ?? '';
$ids    = $_POST['ids'] ?? [];

if (empty($ids) || !is_array($ids)) {
    setFlash('error', 'Tidak ada proyek dipilih.');
    redirect(BASE_URL . '/pages/proyek/index.php');
}

$ids = array_map('intval', $ids);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$db = db();

if ($action === 'delete') {
    requireRole(['admin']);
    $stmt = $db->prepare("DELETE FROM proyek WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    logActivity('bulk_delete', 'bulk', "Menghapus " . count($ids) . " proyek sekaligus", 'proyek', ['ids' => $ids]);
    setFlash('success', count($ids) . ' proyek berhasil dihapus.');

} elseif ($action === 'status') {
    requireRole(['admin']);
    $status = $_POST['status'] ?? '';
    if (!in_array($status, ['survei','perencanaan','instalasi','selesai','ditunda'])) {
        setFlash('error', 'Status tidak valid.');
        redirect(BASE_URL . '/pages/proyek/index.php');
    }
    $stmt = $db->prepare("UPDATE proyek SET status = ? WHERE id IN ($placeholders)");
    $stmt->execute(array_merge([$status], $ids));
    logActivity('bulk_status', 'bulk', "Mengubah status " . count($ids) . " proyek menjadi '$status'", 'proyek', ['ids' => $ids, 'status' => $status]);
    setFlash('success', count($ids) . " proyek diubah menjadi status '$status'.");

} else {
    setFlash('error', 'Aksi tidak valid.');
}

redirect(BASE_URL . '/pages/proyek/index.php');