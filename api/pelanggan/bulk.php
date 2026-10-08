<?php
// api/pelanggan/bulk.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin']);

$action = $_POST['action'] ?? '';
$ids    = $_POST['ids'] ?? [];

if (empty($ids) || !is_array($ids)) {
    setFlash('error', 'Tidak ada pelanggan dipilih.');
    redirect(BASE_URL . '/pages/pelanggan/index.php');
}

$ids = array_map('intval', $ids);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$db = db();

if ($action === 'delete') {
    // Cek apakah ada proyek aktif
    $cek = $db->prepare("SELECT COUNT(*) FROM proyek WHERE pelanggan_id IN ($placeholders) AND status IN ('survei','perencanaan','instalasi')");
    $cek->execute($ids);
    if ($cek->fetchColumn() > 0) {
        setFlash('error', 'Tidak dapat menghapus pelanggan yang masih memiliki proyek aktif.');
        redirect(BASE_URL . '/pages/pelanggan/index.php');
    }

    $stmt = $db->prepare("DELETE FROM pelanggan WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    logActivity('bulk_delete', 'bulk', "Menghapus " . count($ids) . " pelanggan sekaligus", 'pelanggan', ['ids' => $ids]);
    setFlash('success', count($ids) . ' pelanggan berhasil dihapus.');
}

redirect(BASE_URL . '/pages/pelanggan/index.php');