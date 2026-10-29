<?php
// File: api/laporan/store.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/activity.php';

requireRole(['admin', 'manajer_proyek', 'teknisi_lapangan']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/laporan/index.php');
}

if (!verifyCsrf()) {
    setFlash('error', 'Token keamanan tidak valid. Silakan coba lagi.');
    redirect(BASE_URL . '/pages/laporan/create.php');
}

$db = db();
$user = currentUser();
$user_id = (int)($user['id'] ?? 1);

try {
    $db->beginTransaction();

    $proyek_id         = (int)($_POST['proyek_id'] ?? 0);
    $jadwal_id         = !empty($_POST['jadwal_id']) ? (int)$_POST['jadwal_id'] : null;
    $judul_laporan     = trim($_POST['judul_laporan'] ?? '');
    $area_kerja        = trim($_POST['area_kerja'] ?? '');
    $tanggal_laporan   = $_POST['tanggal_laporan'] ?? date('Y-m-d');
    $cuaca             = trim($_POST['jam_cuaca'] ?? '08.00 - 17.00') . ' ' . trim($_POST['kondisi_cuaca'] ?? 'Cerah');
    $status_pengerjaan = $_POST['status_pengerjaan'] ?? 'dalam_proses';
    $progres_baru      = max(0, min(100, (int)($_POST['progres_proyek'] ?? 0)));
    $catatan_tambahan  = trim($_POST['deskripsi'] ?? '');

    if (!$proyek_id || $judul_laporan === '') {
        throw new Exception('Proyek dan judul pekerjaan wajib diisi.');
    }

    // 1. Susun Data Manpower ke JSON
    $mp = [
        'pm'         => max(0, (int)($_POST['mp_pm'] ?? 0)),
        'sm'         => max(0, (int)($_POST['mp_sm'] ?? 0)),
        'pengawas'   => max(0, (int)($_POST['mp_pengawas'] ?? 0)),
        'admin'      => max(0, (int)($_POST['mp_admin'] ?? 0)),
        'hse'        => max(0, (int)($_POST['mp_hse'] ?? 0)),
        'scaffolder' => max(0, (int)($_POST['mp_scaffolder'] ?? 0)),
        'teknisi'    => max(0, (int)($_POST['mp_teknisi'] ?? 0)),
        'helper'     => max(0, (int)($_POST['mp_helper'] ?? 0)),
    ];
    $mp['total'] = array_sum($mp);
    $json_manpower = json_encode($mp, JSON_UNESCAPED_UNICODE);

    // 2. Susun Data Activity Progress (Dinamis) ke JSON
    $activities = [];
    $ringkasan_teks = [];
    if (!empty($_POST['activity_item']) && is_array($_POST['activity_item'])) {
        foreach ($_POST['activity_item'] as $idx => $item_text) {
            $item_text = trim($item_text);
            if ($item_text !== '') {
                $is_done = isset($_POST['activity_done'][$idx]) && $_POST['activity_done'][$idx] == '1' ? 1 : 0;
                $activities[] = [
                    'item' => $item_text,
                    'done' => $is_done
                ];
                $ringkasan_teks[] = count($activities) . ". " . $item_text . ($is_done ? " ✓" : "");
            }
        }
    }
    $json_activity = json_encode($activities, JSON_UNESCAPED_UNICODE);
    $deskripsi_final = $catatan_tambahan !== '' ? $catatan_tambahan : implode("\n", $ringkasan_teks);

    // 3. Susun Data Next Plan ke JSON
    $next_plans = [];
    if (!empty($_POST['next_plan_item']) && is_array($_POST['next_plan_item'])) {
        foreach ($_POST['next_plan_item'] as $plan_text) {
            $plan_text = trim($plan_text);
            if ($plan_text !== '') {
                $next_plans[] = $plan_text;
            }
        }
    }
    $json_next_plan = json_encode($next_plans, JSON_UNESCAPED_UNICODE);

    // 4. Insert ke tabel laporan_lapangan
    $stmt = $db->prepare("
        INSERT INTO laporan_lapangan 
        (proyek_id, jadwal_id, user_id, judul_laporan, area_kerja, cuaca, manpower, deskripsi, activity_progress, next_plan, status_pengerjaan, kontribusi_progres, tanggal_laporan) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $proyek_id, $jadwal_id, $user_id, $judul_laporan, $area_kerja, 
        $cuaca, $json_manpower, $deskripsi_final, $json_activity, 
        $json_next_plan, $status_pengerjaan, $progres_baru, $tanggal_laporan
    ]);
    $laporan_id = (int)$db->lastInsertId();

    // 5. Update progres pada tabel proyek
    if ($progres_baru >= 100) {
        $db->prepare("UPDATE proyek SET progres = 100, status = 'selesai' WHERE id = ?")->execute([$proyek_id]);
    } else {
        $db->prepare("UPDATE proyek SET progres = ? WHERE id = ?")->execute([$progres_baru, $proyek_id]);
    }

    // 6. Update status jadwal jika dipilih
    if ($jadwal_id && !empty($_POST['status_jadwal'])) {
        $db->prepare("UPDATE jadwal SET status = ? WHERE id = ?")->execute([$_POST['status_jadwal'], $jadwal_id]);
    }

    // 7. Upload foto dokumentasi ke folder uploads/laporan/ (Sesuai konstanta UPLOAD_PATH & index.php)
    if (!empty($_FILES['foto_lapangan']['name'][0])) {
        $upload_dir = defined('UPLOAD_PATH') ? UPLOAD_PATH : (__DIR__ . '/../../uploads/laporan/');
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        foreach ($_FILES['foto_lapangan']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['foto_lapangan']['error'][$key] === UPLOAD_ERR_OK) {
                $orig_name = basename($_FILES['foto_lapangan']['name'][$key]);
                $ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) continue;

                $new_name = time() . '_' . $key . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $orig_name);
                // Disimpan sebagai 'laporan/nama_file.jpg' agar cocok dengan BASE_URL . '/uploads/' . $foto_pertama di index.php
                $rel_path = 'laporan/' . $new_name;

                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $stmtFoto = $db->prepare("INSERT INTO foto_lapangan (laporan_id, nama_file, path_file, keterangan) VALUES (?, ?, ?, ?)");
                    $stmtFoto->execute([$laporan_id, $orig_name, $rel_path, $area_kerja]);
                }
            }
        }
    }

    // 8. Catat ke activity_log menggunakan helper asli proyek Anda
    logActivity('upload_laporan', 'create', "Mengunggah laporan: $judul_laporan ($area_kerja)", 'laporan', [
        'laporan_id' => $laporan_id,
        'proyek_id'  => $proyek_id,
        'progres'    => $progres_baru
    ]);

    // 9. Kirim notifikasi ke Manajer Proyek & Admin menggunakan helper asli
    $pesan_notif = "Teknisi {$user['nama']} mengunggah laporan '$judul_laporan' ($area_kerja). Progres proyek kini: {$progres_baru}%.";
    kirimNotifikasiKeRole('manajer_proyek', 'Laporan Lapangan Baru', $pesan_notif);
    kirimNotifikasiKeRole('admin', 'Laporan Lapangan Baru', $pesan_notif);

    $db->commit();

    setFlash('success', 'Laporan lapangan berhasil disimpan dan progres proyek diperbarui!');
    redirect(BASE_URL . '/pages/laporan/show.php?id=' . $laporan_id);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('error', 'Gagal menyimpan laporan: ' . $e->getMessage());
    redirect(BASE_URL . '/pages/laporan/create.php');
}