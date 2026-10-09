<?php
// api/jadwal/reschedule.php — AJAX reschedule via drag-drop
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);

header('Content-Type: application/json');

$id    = (int)($_POST['id'] ?? 0);
$start = $_POST['tanggal_mulai'] ?? '';
$end   = $_POST['tanggal_selesai'] ?? '';

if (!$id || !$start || !$end) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

if ($end < $start) {
    echo json_encode(['success' => false, 'message' => 'Tanggal selesai harus setelah tanggal mulai']);
    exit;
}

try {
    $stmt = db()->prepare("UPDATE jadwal SET tanggal_mulai = ?, tanggal_selesai = ? WHERE id = ?");
    $stmt->execute([$start, $end, $id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}