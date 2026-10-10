<?php
// api/export/material-csv.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin', 'gudang']);

$parseDate = static function (string $key): ?string {
    $value = $_GET[$key] ?? '';
    if (!is_string($value) || $value === '') {
        return null;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        setFlash('error', 'Rentang tanggal untuk export tidak valid.');
        redirect(BASE_URL . '/pages/material/index.php');
    }

    return $value;
};

$search = $_GET['search'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$proyekId = $_GET['proyek_id'] ?? '';
$dari = $parseDate('dari');
$sampai = $parseDate('sampai');

if (!is_string($search) || !is_string($kategori) || !is_string($proyekId)) {
    setFlash('error', 'Filter export tidak valid.');
    redirect(BASE_URL . '/pages/material/index.php');
}
if ($dari && $sampai && $dari > $sampai) {
    setFlash('error', 'Tanggal awal export tidak boleh melewati tanggal akhir.');
    redirect(BASE_URL . '/pages/material/index.php');
}

$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = 'k.nama_komponen LIKE ?';
    $params[] = '%' . $search . '%';
}
if ($kategori !== '') {
    $where[] = 'k.kategori = ?';
    $params[] = $kategori;
}
if ($proyekId !== '') {
    $validatedProjectId = filter_var($proyekId, FILTER_VALIDATE_INT);
    if ($validatedProjectId === false || $validatedProjectId < 1) {
        setFlash('error', 'Filter proyek untuk export tidak valid.');
        redirect(BASE_URL . '/pages/material/index.php');
    }
    $where[] = 'pm.proyek_id = ?';
    $params[] = $validatedProjectId;
}
if ($dari !== null) {
    $where[] = 'pm.tanggal_penggunaan >= ?';
    $params[] = $dari;
}
if ($sampai !== null) {
    $where[] = 'pm.tanggal_penggunaan <= ?';
    $params[] = $sampai;
}

$stmt = db()->prepare("
    SELECT pm.*, k.nama_komponen, k.satuan, k.kategori,
           p.nama_proyek, u.nama AS nama_user
    FROM penggunaan_material pm
    JOIN komponen k ON pm.komponen_id = k.id
    JOIN proyek p ON pm.proyek_id = p.id
    JOIN users u ON pm.user_id = u.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY pm.tanggal_penggunaan DESC, pm.created_at DESC
");
$stmt->execute($params);
$materials = $stmt->fetchAll();

logActivity(
    'export_material',
    'export',
    'Export ' . count($materials) . ' data penggunaan material ke CSV',
    'material',
    ['dari' => $dari, 'sampai' => $sampai, 'proyek_id' => $proyekId]
);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="penggunaan_material_' . date('Y-m-d_His') . '.csv"');
header('Cache-Control: no-store, no-cache, must-revalidate');

$output = fopen('php://output', 'w');
if ($output === false) {
    throw new RuntimeException('Tidak dapat membuka output CSV.');
}

fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
$writeCell = static function ($value): string {
    $value = (string)$value;
    return preg_match('/^[\x00-\x20]*[=+\-@]/', $value) ? "'" . $value : $value;
};

fputcsv($output, [
    'No', 'Tanggal Penggunaan', 'Nama Komponen', 'Kategori', 'Nama Proyek',
    'Jumlah Digunakan', 'Satuan', 'Dicatat Oleh', 'Keterangan', 'Tanggal Pencatatan',
], ';', '"', '\\');

foreach ($materials as $index => $material) {
    fputcsv($output, array_map($writeCell, [
        $index + 1,
        formatTanggalShort($material['tanggal_penggunaan']),
        $material['nama_komponen'],
        $material['kategori'],
        $material['nama_proyek'],
        $material['jumlah_digunakan'],
        $material['satuan'],
        $material['nama_user'],
        $material['keterangan'] ?? '-',
        $material['created_at'],
    ]), ';', '"', '\\');
}

fclose($output);
exit;
