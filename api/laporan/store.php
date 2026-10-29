<?php
// File: api/laporan/store.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
if (file_exists(__DIR__ . '/../../includes/functions.php')) {
    require_once __DIR__ . '/../../includes/functions.php';
}
if (file_exists(__DIR__ . '/../../includes/auth.php')) {
    require_once __DIR__ . '/../../includes/auth.php';
}
if (file_exists(__DIR__ . '/../../includes/csrf.php')) {
    require_once __DIR__ . '/../../includes/csrf.php';
}
if (file_exists(__DIR__ . '/../../includes/activity.php')) {
    require_once __DIR__ . '/../../includes/activity.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php?page=laporan');
    exit;
}

// Ambil koneksi PDO dari config/database.php
$dbConn = $pdo ?? $db ?? $conn ?? null;
$user_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 1;

try {
    if ($dbConn instanceof PDO) {
        $dbConn->beginTransaction();
    }

    $proyek_id         = (int) ($_POST['proyek_id'] ?? 0);
    $jadwal_id         = !empty($_POST['jadwal_id']) ? (int) $_POST['jadwal_id'] : null;
    $judul_laporan     = trim($_POST['judul_laporan'] ?? '');
    $area_kerja        = trim($_POST['area_kerja'] ?? '');
    $tanggal_laporan   = $_POST['tanggal_laporan'] ?? date('Y-m-d');
    $cuaca             = trim($_POST['jam_cuaca'] ?? '08.00 - 17.00') . ' ' . trim($_POST['kondisi_cuaca'] ?? 'Cerah');
    $status_pengerjaan = $_POST['status_pengerjaan'] ?? 'dalam_proses';
    $progres_baru      = (int) ($_POST['progres_proyek'] ?? 0);
    $catatan_tambahan  = trim($_POST['deskripsi'] ?? '');

    // 1. Susun Data Manpower ke JSON
    $mp = [
        'pm'         => (int) ($_POST['mp_pm'] ?? 0),
        'sm'         => (int) ($_POST['mp_sm'] ?? 0),
        'pengawas'   => (int) ($_POST['mp_pengawas'] ?? 0),
        'admin'      => (int) ($_POST['mp_admin'] ?? 0),
        'hse'        => (int) ($_POST['mp_hse'] ?? 0),
        'scaffolder' => (int) ($_POST['mp_scaffolder'] ?? 0),
        'teknisi'    => (int) ($_POST['mp_teknisi'] ?? 0),
        'helper'     => (int) ($_POST['mp_helper'] ?? 0),
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
                $ringkasan_teks[] = (count($activities)) . ". " . $item_text . ($is_done ? " ✓" : "");
            }
        }
    }
    $json_activity = json_encode($activities, JSON_UNESCAPED_UNICODE);

    // Isi kolom deskripsi otomatis jika catatan tambahan dikosongkan
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
    $stmt = $dbConn->prepare("
        INSERT INTO laporan_lapangan 
        (proyek_id, jadwal_id, user_id, judul_laporan, area_kerja, cuaca, manpower, deskripsi, activity_progress, next_plan, status_pengerjaan, kontribusi_progres, tanggal_laporan) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $proyek_id, $jadwal_id, $user_id, $judul_laporan, $area_kerja, 
        $cuaca, $json_manpower, $deskripsi_final, $json_activity, 
        $json_next_plan, $status_pengerjaan, $progres_baru, $tanggal_laporan
    ]);
    $laporan_id = $dbConn->lastInsertId();

    // 5. Update progres pada tabel proyek
    if ($progres_baru >= 100) {
        $stmtProyek = $dbConn->prepare("UPDATE proyek SET progres = 100, status = 'selesai' WHERE id = ?");
        $stmtProyek->execute([$proyek_id]);
    } else {
        $stmtProyek = $dbConn->prepare("UPDATE proyek SET progres = ? WHERE id = ?");
        $stmtProyek->execute([$progres_baru, $proyek_id]);
    }

    // 6. Update status jadwal jika dipilih
    if ($jadwal_id && !empty($_POST['status_jadwal'])) {
        $stmtJadwal = $dbConn->prepare("UPDATE jadwal SET status = ? WHERE id = ?");
        $stmtJadwal->execute([$_POST['status_jadwal'], $jadwal_id]);
    }

    // 7. Upload foto dokumentasi ke tabel foto_lapangan
    if (!empty($_FILES['foto_lapangan']['name'][0])) {
        $upload_dir = __DIR__ . '/../../assets/uploads/laporan/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        foreach ($_FILES['foto_lapangan']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['foto_lapangan']['error'][$key] === UPLOAD_ERR_OK) {
                $orig_name = basename($_FILES['foto_lapangan']['name'][$key]);
                $new_name  = time() . '_' . $key . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $orig_name);
                $rel_path  = 'assets/uploads/laporan/' . $new_name;

                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $stmtFoto = $dbConn->prepare("INSERT INTO foto_lapangan (laporan_id, nama_file, path_file, keterangan) VALUES (?, ?, ?, ?)");
                    $stmtFoto->execute([$laporan_id, $orig_name, $rel_path, $area_kerja]);
                }
            }
        }
    }

    // 8. Simpan ke tabel activity_log
    $stmtLog = $dbConn->prepare("
        INSERT INTO activity_log (user_id, aksi, tipe, deskripsi, modul, ip_address, user_agent) 
        VALUES (?, 'upload_laporan', 'create', ?, 'laporan', ?, ?)
    ");
    $stmtLog->execute([
        $user_id,
        "Mengunggah laporan: $judul_laporan ($area_kerja)",
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    // 9. Kirim notifikasi ke Manajer Proyek & Admin
    $stmtManagers = $dbConn->query("SELECT id FROM users WHERE role IN ('admin', 'manajer_proyek') AND status = 'aktif'");
    $stmtNotif = $dbConn->prepare("INSERT INTO notifikasi (user_id, judul, pesan) VALUES (?, 'Laporan Lapangan Baru', ?)");
    foreach ($stmtManagers->fetchAll(PDO::FETCH_ASSOC) as $mgr) {
        $stmtNotif->execute([
            $mgr['id'], 
            "Laporan baru '$judul_laporan' ($area_kerja) telah diunggah. Progres proyek kini: {$progres_baru}%."
        ]);
    }

    if ($dbConn instanceof PDO) {
        $dbConn->commit();
    }

    $_SESSION['success'] = "Laporan lapangan berhasil disimpan dan progres proyek diperbarui!";
} catch (Exception $e) {
    if ($dbConn instanceof PDO && $dbConn->inTransaction()) {
        $dbConn->rollBack();
    }
    $_SESSION['error'] = "Gagal menyimpan laporan: " . $e->getMessage();
}

header('Location: ../../index.php?page=laporan');
exit;