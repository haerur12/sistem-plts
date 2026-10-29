<?php
// File: api/laporan/last-plan.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
requireRole(['admin', 'manajer_proyek', 'teknisi_lapangan']);

$proyek_id = (int)($_GET['proyek_id'] ?? 0);
if (!$proyek_id) {
    echo json_encode(['found' => false]);
    exit;
}

$stmt = db()->prepare("
    SELECT id, tanggal_laporan, area_kerja, judul_laporan, manpower, activity_progress, next_plan
    FROM laporan_lapangan
    WHERE proyek_id = ?
    ORDER BY tanggal_laporan DESC, id DESC
    LIMIT 1
");
$stmt->execute([$proyek_id]);
$last = $stmt->fetch();

if (!$last) {
    echo json_encode(['found' => false]);
    exit;
}

$prev_activities = !empty($last['activity_progress']) ? json_decode($last['activity_progress'], true) : [];
$prev_next_plan  = !empty($last['next_plan']) ? json_decode($last['next_plan'], true) : [];
$prev_manpower   = !empty($last['manpower']) ? json_decode($last['manpower'], true) : null;

// Gabungkan item yang belum selesai kemarin (done == 0) + seluruh item Next Plan kemarin
$carry_over_items = [];
if (is_array($prev_activities)) {
    foreach ($prev_activities as $act) {
        if (empty($act['done']) && !empty($act['item'])) {
            $carry_over_items[] = [
                'item'   => $act['item'],
                'done'   => 0,
                'source' => 'pending_kemarin'
            ];
        }
    }
}

if (is_array($prev_next_plan)) {
    foreach ($prev_next_plan as $plan) {
        if (is_string($plan) && trim($plan) !== '') {
            $carry_over_items[] = [
                'item'   => trim($plan),
                'done'   => 1,
                'source' => 'next_plan_kemarin'
            ];
        }
    }
}

echo json_encode([
    'found'           => true,
    'tanggal_laporan' => formatTanggal($last['tanggal_laporan']),
    'area_kerja'      => $last['area_kerja'] ?? '',
    'judul_laporan'   => $last['judul_laporan'] ?? '',
    'manpower'        => $prev_manpower,
    'items'           => $carry_over_items
]);