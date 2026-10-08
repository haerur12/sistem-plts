<?php
// api/proyek/export.php — Export CSV
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin','manajer_proyek']);

$ids = isset($_GET['ids']) ? array_map('intval', explode(',', $_GET['ids'])) : [];
if (empty($ids)) { redirect(BASE_URL . '/pages/proyek/index.php'); }

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare("
    SELECT p.*, pl.nama_pelanggan
    FROM proyek p
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    WHERE p.id IN ($placeholders)
    ORDER BY p.created_at DESC
");
$stmt->execute($ids);
$proyeks = $stmt->fetchAll();

logActivity('export_proyek', 'export', "Export " . count($proyeks) . " proyek ke CSV", 'proyek');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=proyek_' . date('Y-m-d_His') . '.csv');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

fputcsv($output, ['ID','Nama Proyek','Pelanggan','Lokasi','Tanggal Mulai','Tanggal Selesai','Status','Progres (%)','Deskripsi','Dibuat']);

foreach ($proyeks as $p) {
    fputcsv($output, [
        $p['id'],
        $p['nama_proyek'],
        $p['nama_pelanggan'],
        $p['lokasi'],
        $p['tanggal_mulai'],
        $p['tanggal_selesai'],
        $p['status'],
        $p['progres'],
        $p['deskripsi'],
        $p['created_at'],
    ]);
}

fclose($output);
exit;