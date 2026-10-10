<?php
// api/komponen/update.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin', 'gudang']);

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$stok = filter_var($_POST['stok_tersedia'] ?? null, FILTER_VALIDATE_INT);
$stokMinimum = filter_var($_POST['stok_minimum'] ?? null, FILTER_VALIDATE_INT);
$namaInput = $_POST['nama_komponen'] ?? '';
$nama = is_string($namaInput) ? trim($namaInput) : '';
$kategori = $_POST['kategori'] ?? '';
$satuan = $_POST['satuan'] ?? '';
$kategoriValid = ['Panel Surya', 'Inverter', 'Baterai', 'Kabel', 'Mounting', 'Proteksi', 'Monitoring', 'Lainnya'];
$satuanValid = ['unit', 'buah', 'meter', 'set', 'rol', 'lembar', 'kg'];

if (!verifyCsrf()) {
    setFlash('error', 'Token keamanan tidak valid. Silakan refresh halaman dan coba lagi.');
    redirect(BASE_URL . '/pages/komponen/index.php');
}

if ($id === false || $id === null || $id < 1 ||
    $stok === false || $stok === null || $stok < 0 ||
    $stokMinimum === false || $stokMinimum === null || $stokMinimum < 0 ||
    $nama === '' || !is_string($kategori) || !in_array($kategori, $kategoriValid, true) ||
    !is_string($satuan) || !in_array($satuan, $satuanValid, true)) {
    setFlash('error', 'Data komponen tidak valid.');
    redirect(BASE_URL . '/pages/komponen/index.php');
}

$db = db();
$stmt = $db->prepare('SELECT nama_komponen FROM komponen WHERE id = ?');
$stmt->execute([$id]);
$komponen = $stmt->fetch();
if (!$komponen) {
    setFlash('error', 'Komponen tidak ditemukan.');
    redirect(BASE_URL . '/pages/komponen/index.php');
}

$db->prepare("
    UPDATE komponen
    SET nama_komponen = ?, kategori = ?, satuan = ?, stok_tersedia = ?, stok_minimum = ?
    WHERE id = ?
")->execute([$nama, $kategori, $satuan, $stok, $stokMinimum, $id]);

if ($stok <= $stokMinimum) {
    kirimNotifikasiKeRole(
        'admin',
        'Peringatan Stok',
        "Stok \"{$nama}\" diperbarui menjadi {$stok} {$satuan} (minimum: {$stokMinimum})."
    );
}

logActivity(
    'update_komponen',
    'update',
    "Memperbarui komponen \"{$komponen['nama_komponen']}\"",
    'komponen',
    ['id' => $id, 'stok_baru' => $stok, 'stok_minimum' => $stokMinimum]
);

setFlash('success', 'Komponen berhasil diperbarui.');
redirect(BASE_URL . '/pages/komponen/index.php');
