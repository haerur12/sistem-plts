<?php
// api/komponen/update-stok.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/activity.php';

header('Content-Type: application/json; charset=utf-8');
requireRole(['admin', 'gudang']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak diizinkan.']);
    exit;
}

if (!verifyCsrf()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token keamanan tidak valid. Refresh halaman dan coba lagi.']);
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$stok = filter_var($_POST['stok_tersedia'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1 || $stok === false || $stok === null || $stok < 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Data stok tidak valid.']);
    exit;
}

$db = db();
$stmt = $db->prepare('SELECT id, nama_komponen, stok_tersedia, stok_minimum, satuan FROM komponen WHERE id = ?');
$stmt->execute([$id]);
$komponen = $stmt->fetch();

if (!$komponen) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Komponen tidak ditemukan.']);
    exit;
}

$db->prepare('UPDATE komponen SET stok_tersedia = ? WHERE id = ?')->execute([$stok, $id]);

if ($stok <= (int)$komponen['stok_minimum']) {
    kirimNotifikasiKeRole(
        'admin',
        'Peringatan Stok',
        "Stok \"{$komponen['nama_komponen']}\" diperbarui menjadi {$stok} {$komponen['satuan']} (minimum: {$komponen['stok_minimum']})."
    );
}

logActivity(
    'update_stok_inline',
    'update',
    "Stok \"{$komponen['nama_komponen']}\" diubah dari {$komponen['stok_tersedia']} menjadi {$stok}",
    'komponen',
    ['id' => $id, 'stok_lama' => (int)$komponen['stok_tersedia'], 'stok_baru' => $stok]
);

[, $label, $status] = stokStatus($stok, (int)$komponen['stok_minimum']);
echo json_encode([
    'success' => true,
    'stok_baru' => $stok,
    'status' => $status,
    'label' => $label,
    'message' => 'Stok berhasil diperbarui.',
]);
