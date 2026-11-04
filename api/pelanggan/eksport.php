<?php
// api/pelanggan/eksport.php — EXPORT DATA PELANGGAN KE CSV
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin']);

$db = db();

if (!empty($_GET['all'])) {
    $pelanggans = $db->query("
        SELECT pl.*, COUNT(p.id) AS total_proyek
        FROM pelanggan pl
        LEFT JOIN proyek p ON p.pelanggan_id = pl.id
        GROUP BY pl.id
        ORDER BY pl.nama_pelanggan ASC
    ")->fetchAll();
} else {
    $ids = isset($_GET['ids']) ? array_filter(array_map('intval', explode(',', $_GET['ids']))) : [];
    if (empty($ids)) {
        redirect(BASE_URL . '/pages/pelanggan/index.php');
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("
        SELECT pl.*, COUNT(p.id) AS total_proyek
        FROM pelanggan pl
        LEFT JOIN proyek p ON p.pelanggan_id = pl.id
        WHERE pl.id IN ($placeholders)
        GROUP BY pl.id
        ORDER BY pl.nama_pelanggan ASC
    ");
    $stmt->execute($ids);
    $pelanggans = $stmt->fetchAll();
}

logActivity('export_pelanggan', 'export', "Export " . count($pelanggans) . " pelanggan ke CSV", 'pelanggan');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="data_pelanggan_' . date('Y-m-d_His') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM untuk Excel

fputcsv($output, ['ID', 'Nama Pelanggan', 'Alamat', 'No. Telepon', 'Email', 'Total Proyek', 'Tanggal Terdaftar'], ';');

foreach ($pelanggans as $p) {
    fputcsv($output, [
        $p['id'],
        $p['nama_pelanggan'],
        $p['alamat'],
        $p['no_telp'],
        $p['email'] ?: '-',
        $p['total_proyek'],
        $p['created_at'],
    ], ';');
}

fclose($output);
exit;