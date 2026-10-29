<?php
// File: api/search/global.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user'])) {
    echo json_encode(['results' => []]);
    exit;
}

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$db      = db();
$user    = currentUser();
$keyword = '%' . $q . '%';
$results = [];

try {
    // 1. Cari di tabel Proyek (Admin, Manajer, Teknisi)
    if (!isGudang()) {
        $stmtProyek = $db->prepare("
            SELECT p.id, p.nama_proyek, p.lokasi, p.status, p.progres, pl.nama_pelanggan
            FROM proyek p
            JOIN pelanggan pl ON p.pelanggan_id = pl.id
            WHERE p.nama_proyek LIKE ? OR p.lokasi LIKE ? OR pl.nama_pelanggan LIKE ?
            ORDER BY p.created_at DESC
            LIMIT 4
        ");
        $stmtProyek->execute([$keyword, $keyword, $keyword]);
        foreach ($stmtProyek->fetchAll() as $p) {
            $results[] = [
                'title'    => sanitize($p['nama_proyek']),
                'type'     => 'Proyek',
                'subtitle' => sanitize($p['nama_pelanggan']) . ' · ' . $p['progres'] . '% (' . ucfirst($p['status']) . ')',
                'icon'     => 'bi-diagram-3',
                'url'      => isAdminOrManajer() 
                    ? BASE_URL . '/pages/proyek/show.php?id=' . $p['id']
                    : BASE_URL . '/pages/laporan/index.php?proyek_id=' . $p['id'],
            ];
        }
    }

    // 2. Cari di tabel Laporan Lapangan
    if (!isGudang()) {
        $sqlLaporan = "
            SELECT l.id, l.judul_laporan, l.tanggal_laporan, p.nama_proyek, u.nama AS teknisi
            FROM laporan_lapangan l
            JOIN proyek p ON l.proyek_id = p.id
            JOIN users u ON l.user_id = u.id
            WHERE (l.judul_laporan LIKE ? OR l.deskripsi LIKE ? OR p.nama_proyek LIKE ?)
        ";
        $paramsLap = [$keyword, $keyword, $keyword];
        if (isTeknisi()) {
            $sqlLaporan .= " AND l.user_id = ?";
            $paramsLap[] = $user['id'];
        }
        $sqlLaporan .= " ORDER BY l.created_at DESC LIMIT 4";

        $stmtLap = $db->prepare($sqlLaporan);
        $stmtLap->execute($paramsLap);
        foreach ($stmtLap->fetchAll() as $l) {
            $results[] = [
                'title'    => sanitize($l['judul_laporan']),
                'type'     => 'Laporan',
                'subtitle' => sanitize($l['nama_proyek']) . ' · ' . formatTanggal($l['tanggal_laporan']),
                'icon'     => 'bi-file-earmark-text',
                'url'      => BASE_URL . '/pages/laporan/show.php?id=' . $l['id'],
            ];
        }
    }

    // 3. Cari di tabel Pelanggan (Khusus Admin)
    if (isAdmin()) {
        $stmtPel = $db->prepare("
            SELECT id, nama_pelanggan, alamat, no_telp
            FROM pelanggan
            WHERE nama_pelanggan LIKE ? OR alamat LIKE ? OR email LIKE ?
            ORDER BY nama_pelanggan ASC
            LIMIT 3
        ");
        $stmtPel->execute([$keyword, $keyword, $keyword]);
        foreach ($stmtPel->fetchAll() as $pl) {
            $results[] = [
                'title'    => sanitize($pl['nama_pelanggan']),
                'type'     => 'Pelanggan',
                'subtitle' => sanitize($pl['no_telp']) . ' · ' . sanitize($pl['alamat']),
                'icon'     => 'bi-people',
                'url'      => BASE_URL . '/pages/pelanggan/edit.php?id=' . $pl['id'],
            ];
        }
    }

    // 4. Cari di tabel Komponen PLTS (Semua Role)
    $stmtKomp = $db->prepare("
        SELECT id, nama_komponen, kategori, stok_tersedia, satuan
        FROM komponen
        WHERE nama_komponen LIKE ? OR kategori LIKE ?
        ORDER BY nama_komponen ASC
        LIMIT 4
    ");
    $stmtKomp->execute([$keyword, $keyword]);
    foreach ($stmtKomp->fetchAll() as $k) {
        $results[] = [
            'title'    => sanitize($k['nama_komponen']),
            'type'     => 'Komponen',
            'subtitle' => sanitize($k['kategori']) . ' · Stok: ' . $k['stok_tersedia'] . ' ' . sanitize($k['satuan']),
            'icon'     => 'bi-box-seam',
            'url'      => isAdminOrGudang()
                ? BASE_URL . '/pages/komponen/index.php?search=' . urlencode($k['nama_komponen'])
                : BASE_URL . '/pages/komponen/status.php',
        ];
    }

    echo json_encode(['results' => array_slice($results, 0, 8)]);
} catch (Exception $e) {
    echo json_encode(['results' => []]);
}