<?php
// api/proyek/quick-update.php — AJAX quick update
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);

header('Content-Type: application/json');

$id    = (int)($_POST['id'] ?? 0);
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

if (!$id || !$field) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

$allowedFields = ['status', 'progres'];
if (!in_array($field, $allowedFields)) {
    echo json_encode(['success' => false, 'message' => 'Field tidak valid']);
    exit;
}

// Validasi value
if ($field === 'status') {
    $validStatus = ['survei','perencanaan','instalasi','selesai','ditunda'];
    if (!in_array($value, $validStatus)) {
        echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
        exit;
    }
} elseif ($field === 'progres') {
    $value = (int)$value;
    if ($value < 0 || $value > 100) {
        echo json_encode(['success' => false, 'message' => 'Progres harus 0-100']);
        exit;
    }
}

try {
    $stmt = db()->prepare("UPDATE proyek SET $field = ? WHERE id = ?");
    $stmt->execute([$value, $id]);
    echo json_encode(['success' => true, 'field' => $field, 'value' => $value]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}