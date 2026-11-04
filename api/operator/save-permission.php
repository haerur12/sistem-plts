<?php
// api/operator/save-permissions.php — SIMPAN MATRIKS HAK AKSES ROLE
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
    setFlash('error', 'Token keamanan tidak valid.');
    redirect(BASE_URL . '/pages/operator/akses.php');
}

$targetRole = $_POST['target_role'] ?? '';
$validRoles = ['admin', 'manajer_proyek', 'teknisi_lapangan', 'gudang'];

if (!in_array($targetRole, $validRoles, true)) {
    setFlash('error', 'Role target tidak valid.');
    redirect(BASE_URL . '/pages/operator/akses.php');
}

$semuaModul = [
    'dashboard', 'activity-log', 'analitik', 'status-material',
    'proyek', 'jadwal', 'gantt', 'kanban', 'laporan',
    'pelanggan', 'pengguna', 'komponen', 'material', 'laporan-stok'
];

$inputPerm = $_POST['perm'] ?? [];
$db = db();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO role_permissions (role, modul, can_view, can_create, can_edit, can_delete)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            can_view   = VALUES(can_view),
            can_create = VALUES(can_create),
            can_edit   = VALUES(can_edit),
            can_delete = VALUES(can_delete)
    ");

    foreach ($semuaModul as $mod) {
        $v = !empty($inputPerm[$mod]['view']) ? 1 : 0;
        $c = !empty($inputPerm[$mod]['create']) ? 1 : 0;
        $e = !empty($inputPerm[$mod]['edit']) ? 1 : 0;
        $d = !empty($inputPerm[$mod]['delete']) ? 1 : 0;

        // Jika create/edit/delete dicentang, otomatis izinkan view
        if ($c || $e || $d) {
            $v = 1;
        }

        $stmt->execute([$targetRole, $mod, $v, $c, $e, $d]);
    }

    logActivity('update_permissions', 'update', "Operator memperbarui matriks hak akses untuk role: $targetRole", 'operator');
    $db->commit();

    setFlash('success', "Matriks hak akses untuk role \"" . strtoupper(str_replace('_', ' ', $targetRole)) . "\" berhasil diperbarui!");
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    setFlash('error', 'Gagal menyimpan pengaturan hak akses: ' . $e->getMessage());
}

redirect(BASE_URL . '/pages/operator/akses.php?role_tab=' . urlencode($targetRole));