<?php
// File: api/laporan/last-plan.php — AMBIL TIM PROYEK & NEXT PLAN TERAKHIR
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
requireRole(['admin', 'manajer_proyek', 'teknisi_lapangan']);

$proyek_id = (int)($_GET['proyek_id'] ?? 0);
if (!$proyek_id) {
    echo json_encode(['found' => false, 'team_manpower' => null]);
    exit;
}

$db = db();

// 1. Hitung komposisi Manpower default berdasarkan personel yang ditempatkan di tabel proyek_tim
$teamManpower = null;
$teamNames    = [];
try {
    $stmtTim = $db->prepare("SELECT nama_personel, peran_proyek FROM proyek_tim WHERE proyek_id = ?");
    $stmtTim->execute([$proyek_id]);
    $rowsTim = $stmtTim->fetchAll();

    if (!empty($rowsTim)) {
        $teamManpower = [
            'pm'         => 0,
            'sm'         => 0,
            'pengawas'   => 0,
            'admin'      => 0,
            'hse'        => 0,
            'scaffolder' => 0,
            'teknisi'    => 0,
            'helper'     => 0,
        ];
        foreach ($rowsTim as $r) {
            $peran = strtolower($r['peran_proyek']);
            $teamNames[] = $r['nama_personel'] . ' (' . $r['peran_proyek'] . ')';

            if (str_contains($peran, 'project manager')) $teamManpower['pm']++;
            elseif (str_contains($peran, 'site manager')) $teamManpower['sm']++;
            elseif (str_contains($peran, 'pengawas')) $teamManpower['pengawas']++;
            elseif (str_contains($peran, 'admin')) $teamManpower['admin']++;
            elseif (str_contains($peran, 'hse') || str_contains($peran, 'safety')) $teamManpower['hse']++;
            elseif (str_contains($peran, 'scaffold')) $teamManpower['scaffolder']++;
            elseif (str_contains($peran, 'teknisi')) $teamManpower['teknisi']++;
            else $teamManpower['helper']++;
        }
    }
} catch (Exception $e) {
    $teamManpower = null;
}

// 2. Ambil laporan terakhir untuk estafet Next Plan kemarin
$stmt = $db->prepare("
    SELECT id, tanggal_laporan, area_kerja, judul_laporan, manpower, activity_progress, next_plan
    FROM laporan_lapangan
    WHERE proyek_id = ?
    ORDER BY tanggal_laporan DESC, id DESC
    LIMIT 1
");
$stmt->execute([$proyek_id]);
$last = $stmt->fetch();

if (!$last) {
    echo json_encode([
        'found'         => false,
        'team_manpower' => $teamManpower,
        'team_names'    => $teamNames
    ]);
    exit;
}

$prev_activities = !empty($last['activity_progress']) ? json_decode($last['activity_progress'], true) : [];
$prev_next_plan  = !empty($last['next_plan']) ? json_decode($last['next_plan'], true) : [];
$prev_manpower   = !empty($last['manpower']) ? json_decode($last['manpower'], true) : null;

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
    'manpower'        => $teamManpower ?? $prev_manpower,
    'team_manpower'   => $teamManpower,
    'team_names'      => $teamNames,
    'items'           => $carry_over_items
]);