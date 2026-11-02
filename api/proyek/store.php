<?php
// api/proyek/store.php — SIMPAN PROYEK + TIM PERSONEL
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/proyek/index.php');
}

foreach (['pelanggan_id', 'nama_proyek', 'lokasi', 'tanggal_mulai', 'tanggal_selesai'] as $f) {
    if (empty($_POST[$f])) {
        setFlash('error', 'Semua field utama wajib diisi.');
        redirect(BASE_URL . '/pages/proyek/create.php');
    }
}

if ($_POST['tanggal_selesai'] <= $_POST['tanggal_mulai']) {
    setFlash('error', 'Tanggal selesai harus setelah tanggal mulai.');
    redirect(BASE_URL . '/pages/proyek/create.php');
}

$latitude  = trim($_POST['latitude'] ?? '');
$longitude = trim($_POST['longitude'] ?? '');
if (($latitude === '') !== ($longitude === '')) {
    setFlash('error', 'Latitude dan longitude harus diisi bersama.');
    redirect(BASE_URL . '/pages/proyek/create.php');
}
if ($latitude !== '' && (
    !is_numeric($latitude) || !is_numeric($longitude)
    || (float)$latitude < -90 || (float)$latitude > 90
    || (float)$longitude < -180 || (float)$longitude > 180
)) {
    setFlash('error', 'Koordinat tidak valid.');
    redirect(BASE_URL . '/pages/proyek/create.php');
}

$db = db();

try {
    $db->beginTransaction();

    $namaProyek = trim($_POST['nama_proyek']);

    $stmt = $db->prepare("
        INSERT INTO proyek (pelanggan_id, nama_proyek, lokasi, latitude, longitude, tanggal_mulai, tanggal_selesai, deskripsi)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        (int)$_POST['pelanggan_id'],
        $namaProyek,
        trim($_POST['lokasi']),
        $latitude !== '' ? $latitude : null,
        $longitude !== '' ? $longitude : null,
        $_POST['tanggal_mulai'],
        $_POST['tanggal_selesai'],
        trim($_POST['deskripsi'] ?? ''),
    ]);

    $proyekId = (int)$db->lastInsertId();

    // Simpan Penempatan Tim dari Akun Users Terdaftar
    $stmtTim  = $db->prepare("INSERT INTO proyek_tim (proyek_id, user_id, nama_personel, peran_proyek, no_hp) VALUES (?, ?, ?, ?, ?)");
    $stmtUser = $db->prepare("SELECT id, nama FROM users WHERE id = ?");

    if (!empty($_POST['tim_user_ids']) && is_array($_POST['tim_user_ids'])) {
        foreach ($_POST['tim_user_ids'] as $uid) {
            $uid = (int)$uid;
            $stmtUser->execute([$uid]);
            $uData = $stmtUser->fetch();
            if ($uData) {
                $peran = trim($_POST['tim_user_peran'][$uid] ?? 'Teknisi Lapangan');
                $stmtTim->execute([$proyekId, $uid, $uData['nama'], $peran, null]);

                // Kirim notifikasi personal ke user yang ditempatkan
                kirimNotifikasi(
                    $uid,
                    'Penempatan Proyek Baru',
                    "Anda telah ditempatkan sebagai {$peran} pada proyek \"{$namaProyek}\"."
                );
            }
        }
    }

    // Simpan Personel Lapangan Tambahan (Non-Akun / Manual)
    if (!empty($_POST['manual_nama']) && is_array($_POST['manual_nama'])) {
        foreach ($_POST['manual_nama'] as $idx => $mNama) {
            $mNama = trim($mNama);
            if ($mNama !== '') {
                $mPeran = trim($_POST['manual_peran'][$idx] ?? 'Helper');
                $mHp    = trim($_POST['manual_hp'][$idx] ?? '');
                $stmtTim->execute([$proyekId, null, $mNama, $mPeran, $mHp !== '' ? $mHp : null]);
            }
        }
    }

    kirimNotifikasiKeRole('manajer_proyek', 'Proyek Baru Ditambahkan', "Proyek \"$namaProyek\" telah ditambahkan ke sistem.");
    logActivity('create_proyek', 'create', "Membuat proyek baru: $namaProyek", 'proyek', ['proyek_id' => $proyekId]);

    $db->commit();

    setFlash('success', 'Proyek beserta tim personel berhasil ditambahkan.');
    redirect(BASE_URL . '/pages/proyek/show.php?id=' . $proyekId);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    setFlash('error', 'Gagal menyimpan proyek: ' . $e->getMessage());
    redirect(BASE_URL . '/pages/proyek/create.php');
}