<?php
// File: api/notifikasi/count.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user']['id'])) {
    echo json_encode([
        'authenticated' => false,
        'count'         => 0
    ]);
    exit;
}

$userId = (int)$_SESSION['user']['id'];
$count  = countNotifBelumDibaca($userId);

echo json_encode([
    'authenticated' => true,
    'count'         => $count
]);