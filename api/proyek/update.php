<?php
// api/proyek/update.php — UPDATE PROYEK & SINKRONISASI TIM
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin', 'manajer_proyek']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/proyek/index.php');
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    setFlash('error', 'ID tidak valid.');
    redirect(BASE_URL . '/pages/proyek/index.php');
}

$db  = db();
$cek = $db->prepare("SELECT * FROM proyek WHERE id = ?");
$cek->execute([$id]);
$proyek = $cek->fetch();

if (!$proyek) {
    setFlash('error', 'Proyek tidak ditemukan.');
    redirect(BASE_URL . '/pages/proyek/index.php');
}

// Deteksi apakah ini Quick Update (hanya status & progres dari show.php) atau Full Update (dari edit.php)
$isQuickUpdate = !isset($_POST['pelanggan_id']) && !isset($_POST['nama_proyek']);

try {
    $db->beginTransaction();

    if ($isQuickUpdate) {
        $progresBaru = max(0, min(100, (int)($_POST['progres'] ?? $proyek['progres'])));
        $statusBaru  = $progresBaru >= 100 ? 'selesai' : ($_POST['status'] ?? $proyek['status']);

        $stmt = $db->prepare("UPDATE proyek SET status = ?, progres = ? WHERE id = ?");
        $stmt->execute([$statusBaru, $progresBaru, $id]);
        $namaProyek = $proyek['nama_proyek'];
    } else {
        if (!isAdmin()) {
            throw new Exception('Anda tidak memiliki izin untuk mengedit seluruh informasi proyek.');
        }

        foreach (['pelanggan_id', 'nama_proyek', 'lokasi', 'tanggal_mulai', 'tanggal_selesai'] as $f) {
            if (empty($_POST[$f])) {
                setFlash('error', 'Field ' . str_replace('_', ' ', $f) . ' wajib diisi.');
                redirect(BASE_URL . '/pages/proyek/edit.php?id=' . $id);
            }
        }

        if ($_POST['tanggal_selesai'] <= $_POST['tanggal_mulai']) {
            setFlash('error', 'Tanggal selesai harus setelah tanggal mulai.');
            redirect(BASE_URL . '/pages/proyek/edit.php?id=' . $id);
        }

        $latitude    = trim($_POST['latitude'] ?? '');
        $longitude   = trim($_POST['longitude'] ?? '');
        $progresBaru = max(0, min(100, (int)($_POST['progres'] ?? 0)));
        $statusBaru  = $progresBaru >= 100 ? 'selesai' : ($_POST['status'] ?? 'survei');
        $namaProyek  = trim($_POST['nama_proyek']);

        $stmt = $db->prepare("
            UPDATE proyek 
            SET pelanggan_id = ?, nama_proyek = ?, lokasi = ?, latitude = ?, longitude = ?,
                tanggal_mulai = ?, tanggal_selesai = ?, status = ?, progres = ?, deskripsi = ?
            WHERE id = ?
        ");
        $stmt->execute([
            (int)$_POST['pelanggan_id'],
            $namaProyek,
            trim($_POST['lokasi']),
            $latitude !== '' ? $latitude : null,
            $longitude !== '' ? $longitude : null,
            $_POST['tanggal_mulai'],
            $_POST['tanggal_selesai'],
            $statusBaru,
            $progresBaru,
            trim($_POST['deskripsi'] ?? ''),
            $id,
        ]);

        // Sinkronisasi Tabel proyek_tim
        // Ambil user_id lama agar kita hanya mengirim notifikasi ke anggota yang baru ditambahkan
        $oldUsersStmt = $db->prepare("SELECT user_id FROM proyek_tim WHERE proyek_id = ? AND user_id IS NOT NULL");
        $oldUsersStmt->execute([$id]);
        $oldUserIds = array_map('intval', $oldUsersStmt->fetchAll(PDO::FETCH_COLUMN));

        // Reset dan masukkan susunan tim terbaru
        $db->prepare("DELETE FROM proyek_tim WHERE proyek_id = ?")->execute([$id]);

        $stmtInsertTim = $db->prepare("INSERT INTO proyek_tim (proyek_id, user_id, nama_personel, peran_proyek, no_hp) VALUES (?, ?, ?, ?, ?)");
        $stmtUser      = $db->prepare("SELECT id, nama FROM users WHERE id = ?");

        if (!empty($_POST['tim_user_ids']) && is_array($_POST['tim_user_ids'])) {
            foreach ($_POST['tim_user_ids'] as $uid) {
                $uid = (int)$uid;
                $stmtUser->execute([$uid]);
                $uData = $stmtUser->fetch();
                if ($uData) {
                    $peran = trim($_POST['tim_user_peran'][$uid] ?? 'Teknisi Lapangan');
                    $stmtInsertTim->execute([$id, $uid, $uData['nama'], $peran, null]);

                    if (!in_array($uid, $oldUserIds, true)) {
                        kirimNotifikasi($uid, 'Penempatan Proyek', "Anda telah ditempatkan sebagai {$peran} pada proyek \"{$namaProyek}\".");
                    }
                }
            }
        }

        if (!empty($_POST['manual_nama']) && is_array($_POST['manual_nama'])) {
            foreach ($_POST['manual_nama'] as $idx => $mNama) {
                $mNama = trim($mNama);
                if ($mNama !== '') {
                    $mPeran = trim($_POST['manual_peran'][$idx] ?? 'Helper');
                    $mHp    = trim($_POST['manual_hp'][$idx] ?? '');
                    $stmtInsertTim->execute([$id, null, $mNama, $mPeran, $mHp !== '' ? $mHp : null]);
                }
            }
        }
    }

    logActivity('update_proyek', 'update', "Memperbarui proyek \"$namaProyek\"", 'proyek', ['proyek_id' => $id]);
    kirimNotifikasiKeRole('admin', 'Proyek Diperbarui', "Proyek \"$namaProyek\" telah diperbarui.");

    $db->commit();
    setFlash('success', 'Data proyek dan tim berhasil diperbarui.');
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    setFlash('error', 'Gagal memperbarui proyek: ' . $e->getMessage());
}

redirect(BASE_URL . '/pages/proyek/show.php?id=' . $id);