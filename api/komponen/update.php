<?php
// api/komponen/update-stok.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');
requireRole(['admin','gudang']);

$id   = (int)($_POST['id']            ?? 0);
$stok = (int)($_POST['stok_tersedia'] ?? -1);

if (!$id || $stok < 0) {
    echo json_encode(['success'=>false, 'message'=>'Data tidak valid']);
    exit;
}

$db   = db();
$cek  = $db->prepare("SELECT * FROM komponen WHERE id = ?");
$cek->execute([$id]);
$k    = $cek->fetch();

if (!$k) {
    echo json_encode(['success'=>false, 'message'=>'Komponen tidak ditemukan']);
    exit;
}

$db->prepare("UPDATE komponen SET stok_tersedia = ? WHERE id = ?")
   ->execute([$stok, $id]);

// Kirim notif jika stok kritis
if ($stok <= $k['stok_minimum']) {
    kirimNotifikasiKeRole('admin', 'Peringatan Stok', "Stok \"{$k['nama_komponen']}\" diperbarui menjadi {$stok} {$k['satuan']} (minimum: {$k['stok_minimum']}).");
}

logActivity('UPDATE_STOK_INLINE', 'komponen', $id, "Stok diubah dari {$k['stok_tersedia']} ke {$stok}");

echo json_encode([
    'success'      => true,
    'stok_baru'    => $stok,
    'status'       => stokStatus($stok, $k['stok_minimum'])[2],
    'message'      => 'Stok berhasil diperbarui'
]);