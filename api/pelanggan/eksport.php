<?php
// api/pelanggan/export.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin']);

$ids = isset($_GET['ids']) ? array_map('intval', explode(',', $_GET['ids'])) : [];
if (empty($ids)) { redirect(BASE_URL . '/pages/pelanggan/index.php'); }

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare("SELECT * FROM pelanggan WHERE id IN ($placeholders) ORDER BY nama_pelanggan");
$stmt->execute($ids);
$pelanggans = $stmt->fetchAll();

logActivity('export_pelanggan', 'export', "Export " . count($pelanggans) . " pelanggan ke CSV", 'pelanggan');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=pelanggan_' . date('Y-m-d_His') . '.csv');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['ID','Nama Pelanggan','Alamat','No. Telepon','Email','Dibuat']);

foreach ($pelanggans as $p) {
    fputcsv($output, [
        $p['id'],
        $p['nama_pelanggan'],
        $p['alamat'],
        $p['no_telp'],
        $p['email'],
        $p['created_at'],
    ]);
}

fclose($output);
exit;