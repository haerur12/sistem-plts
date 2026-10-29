<?php
// api/jadwal/update.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';

requireRole(['admin', 'manajer_proyek']);

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    setFlash('error', 'ID jadwal tidak valid.');
    redirect(BASE_URL . '/pages/jadwal/index.php');
}

if ($_POST['tanggal_selesai'] < $_POST['tanggal_mulai']) {
    setFlash('error', 'Tanggal selesai tidak boleh mendahului tanggal mulai.');
    redirect(BASE_URL . '/pages/jadwal/edit.php?id=' . $id);
}

$stmt = db()->prepare("UPDATE jadwal SET proyek_id=?, tanggal_mulai=?, tanggal_selesai=?, keterangan=?, status=? WHERE id=?");
$stmt->execute([
    (int)$_POST['proyek_id'],
    $_POST['tanggal_mulai'],
    $_POST['tanggal_selesai'],
    trim($_POST['keterangan'] ?? ''),
    $_POST['status'] ?? 'terjadwal',
    $id
]);

logActivity('update_jadwal', 'update', "Memperbarui jadwal #$id (Status: {$_POST['status']})", 'jadwal');
setFlash('success', 'Jadwal instalasi berhasil diperbarui.');
redirect(BASE_URL . '/pages/jadwal/index.php');
