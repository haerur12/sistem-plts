<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','gudang']);

$db         = db();
$komponen   = $db->query("SELECT * FROM komponen ORDER BY kategori, nama_komponen")->fetchAll();
$penggunaan = $db->query("
    SELECT pm.*, k.nama_komponen, k.satuan, p.nama_proyek, u.nama as nama_user
    FROM penggunaan_material pm
    JOIN komponen k ON pm.komponen_id = k.id
    JOIN proyek p ON pm.proyek_id = p.id
    JOIN users u ON pm.user_id = u.id
    ORDER BY pm.created_at DESC
")->fetchAll();

$totalKomponen = count($komponen);
$stokAman      = count(array_filter($komponen, fn($k) => stokStatus($k['stok_tersedia'],$k['stok_minimum'])[2] === 'aman'));
$stokKritis    = count(array_filter($komponen, fn($k) => in_array(stokStatus($k['stok_tersedia'],$k['stok_minimum'])[2], ['kritis','rendah'])));
$stokHabis     = count(array_filter($komponen, fn($k) => stokStatus($k['stok_tersedia'],$k['stok_minimum'])[2] === 'habis'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok Material — PT Energy Lima Pilar</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; font-size:11px; color:#1e293b; padding:20px; }
        .header { text-align:center; border-bottom:2px solid #1a237e; padding-bottom:14px; margin-bottom:20px; }
        .header .logo { font-size:20px; font-weight:700; color:#1a237e; }
        .header .subtitle { font-size:11px; color:#64748b; margin-top:2px; }
        .header .date { font-size:10px; color:#94a3b8; margin-top:4px; }
        .summary { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:16px; }
        .sum-box { border:1px solid #e2e8f0; border-radius:6px; padding:10px; text-align:center; }
        .sum-num { font-size:22px; font-weight:700; color:#1a237e; }
        .sum-label { font-size:9px; color:#64748b; margin-top:2px; }
        .section-title { background:#1a237e; color:#fff; padding:6px 12px; font-size:11px; font-weight:600; margin:14px 0 8px; border-radius:4px; }
        table { width:100%; border-collapse:collapse; font-size:10px; margin-bottom:12px; }
        th { background:#e8eaf6; color:#1a237e; padding:6px 8px; text-align:left; border:1px solid #c5cae9; }
        td { padding:5px 8px; border:1px solid #e8eaf6; }
        tr:nth-child(even) td { background:#f8fafc; }
        .status-aman   { color:#15803d; font-weight:600; }
        .status-rendah { color:#b45309; font-weight:600; }
        .status-kritis { color:#b91c1c; font-weight:600; }
        .status-habis  { color:#64748b; font-weight:600; }
        .footer { margin-top:24px; border-top:1px solid #e2e8f0; padding-top:10px; font-size:9px; color:#94a3b8; text-align:center; }
        @media print { body { padding:8px; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">PT ENERGY LIMA PILAR</div>
        <div class="subtitle">Laporan Stok dan Penggunaan Material PLTS</div>
        <div class="date">Dicetak pada: <?= date('d F Y H:i') ?> WIB</div>
    </div>

    <div class="summary">
        <div class="sum-box"><div class="sum-num"><?= $totalKomponen ?></div><div class="sum-label">Total Komponen</div></div>
        <div class="sum-box"><div class="sum-num" style="color:#15803d"><?= $stokAman ?></div><div class="sum-label">Stok Aman</div></div>
        <div class="sum-box"><div class="sum-num" style="color:#b91c1c"><?= $stokKritis ?></div><div class="sum-label">Perlu Perhatian</div></div>
        <div class="sum-box"><div class="sum-num" style="color:#64748b"><?= $stokHabis ?></div><div class="sum-label">Stok Habis</div></div>
    </div>

    <div class="section-title">DETAIL STOK KOMPONEN PLTS</div>
    <table>
        <thead>
            <tr><th>No</th><th>Nama Komponen</th><th>Kategori</th><th style="text-align:center">Tersedia</th><th style="text-align:center">Min</th><th>Satuan</th><th>Status</th></tr>
        </thead>
        <tbody>
            <?php foreach ($komponen as $i => $k):
                [,$label,$statusKey] = stokStatus($k['stok_tersedia'], $k['stok_minimum']);
            ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= sanitize($k['nama_komponen']) ?></td>
                <td><?= sanitize($k['kategori']) ?></td>
                <td style="text-align:center"><strong><?= $k['stok_tersedia'] ?></strong></td>
                <td style="text-align:center"><?= $k['stok_minimum'] ?></td>
                <td><?= sanitize($k['satuan']) ?></td>
                <td class="status-<?= $statusKey ?>"><?= $label ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="section-title">RIWAYAT PENGGUNAAN MATERIAL</div>
    <table>
        <thead>
            <tr><th>No</th><th>Komponen</th><th>Proyek</th><th style="text-align:center">Jumlah</th><th>Satuan</th><th>Dicatat Oleh</th><th>Tanggal</th></tr>
        </thead>
        <tbody>
            <?php if (empty($penggunaan)): ?>
            <tr><td colspan="7" style="text-align:center;color:#94a3b8">Belum ada riwayat penggunaan</td></tr>
            <?php else: ?>
            <?php foreach ($penggunaan as $i => $pm): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= sanitize($pm['nama_komponen']) ?></td>
                <td><?= sanitize($pm['nama_proyek']) ?></td>
                <td style="text-align:center;color:#b91c1c;font-weight:600">-<?= $pm['jumlah_digunakan'] ?></td>
                <td><?= sanitize($pm['satuan']) ?></td>
                <td><?= sanitize($pm['nama_user']) ?></td>
                <td><?= formatTanggalShort($pm['tanggal_penggunaan']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Laporan ini digenerate otomatis oleh Sistem Monitoring PLTS — PT Energy Lima Pilar
    </div>
</body>
</html>
<script>window.onload = () => window.print();</script>