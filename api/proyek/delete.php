<?php
// api/proyek/delete.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(BASE_URL . '/pages/proyek/index.php');

$id = (int)($_POST['id'] ?? 0);
if (!$id) { setFlash('error', 'ID tidak valid.'); redirect(BASE_URL . '/pages/proyek/index.php'); }

$stmt = db()->prepare("DELETE FROM proyek WHERE id = ?");
$stmt->execute([$id]);

setFlash('success', 'Proyek berhasil dihapus.');
redirect(BASE_URL . '/pages/proyek/index.php');