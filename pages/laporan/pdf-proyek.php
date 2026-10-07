<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);

$id = (int)($_GET['id'] ?? 0);
if (!$id) { die('ID proyek tidak valid.'); }

$db = db();

$stmt = $db->prepare("SELECT p.*, pl.nama_pelanggan, pl.no_telp, pl.email as email_pelanggan, pl.alamat FROM proyek p JOIN pelanggan pl ON p.pelanggan_id = pl.id WHERE p.id = ?");
$stmt->execute([$id]);
$proyek = $stmt->fetch();
if (!$proyek) die('Proyek tidak ditemukan.');

$jadwals   = $db->prepare("SELECT * FROM jadwal WHERE proyek_id = ? ORDER BY tanggal_mulai"); $jadwals->execute([$id]); $jadwals = $jadwals->fetchAll();
$laporans  = $db->prepare("SELECT l.*, u.nama as nama_teknisi FROM laporan_lapangan l JOIN users u ON l.user_id = u.id WHERE l.proyek_id = ? ORDER BY l.tanggal_laporan"); $laporans->execute([$id]); $laporans = $laporans->fetchAll();
$materials = $db->prepare("SELECT pm.*, k.nama_komponen, k.satuan FROM penggunaan_material pm JOIN komponen k ON pm.komponen_id = k.id WHERE pm.proyek_id = ? ORDER BY pm.tanggal_penggunaan"); $materials->execute([$id]); $materials = $materials->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Proyek — <?= sanitize($proyek['nama_proyek']) ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; font-size:11px; color:#1e293b; padding:20px; }
        .header { text-align:center; border-bottom:2px solid #1a237e; padding-bottom:14px; margin-bottom:20px; }
        .header .logo { font-size:20px; font-weight:700; color:#1a237e; letter-spacing:-0.5px; }
        .header .subtitle { font-size:11px; color:#64748b; margin-top:2px; }
        .header .print-date { font-size:10px; color:#94a3b8; margin-top:4px; }
        .section-title { background:#1a237e; color:#fff; padding:6px 12px; font-size:11px; font-weight:600; letter-spacing:.3px; margin:16px 0 8px; border-radius:4px; }
        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:0; border:1px solid #e2e8f0; border-radius:6px; overflow:hidden; margin-bottom:12px; }
        .info-row { display:flex; border-bottom:1px solid #f1f5f9; }
        .info-row:last-child { border-bottom:none; }
        .info-label { width:130px; padding:6px 10px; background:#f8fafc; font-weight:600; color:#475569; font-size:10px; flex-shrink:0; }
        .info-value { padding:6px 10px; color:#1e293b; }
        .progres-bar { height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden; margin-top:4px; }
        .progres-fill { height:8px; background:#1a237e; border-radius:4px; }
        table { width:100%; border-collapse:collapse; margin-bottom:12px; font-size:10px; }
        th { background:#e8eaf6; color:#1a237e; padding:6px 8px; text-align:left; font-size:10px; border:1px solid #c5cae9; }
        td { padding:5px 8px; border:1px solid #e8eaf6; color:#334155; }
        tr:nth-child(even) td { background:#f8fafc; }
        .badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:9px; font-weight:600; }
        .badge-instalasi { background:#dbeafe; color:#1d4ed8; }
        .badge-selesai { background:#dcfce7; color:#15803d; }
        .badge-survei { background:#f1f5f9; color:#475569; }
        .badge-ditunda { background:#fef3c7; color:#b45309; }
        .badge-perencanaan { background:#ede9fe; color:#7c3aed; }
        .badge-selesai2 { background:#dcfce7; color:#15803d; }
        .badge-terkendala { background:#fee2e2; color:#b91c1c; }
        .footer { margin-top:30px; border-top:1px solid #e2e8f0; padding-top:10px; display:flex; justify-content:space-between; align-items:flex-end; }
        .footer .left { font-size:9px; color:#94a3b8; }
        .signature { text-align:center; }
        .signature .line { border-top:1px solid #334155; width:160px; margin:40px auto 4px; }
        .signature .name { font-size:10px; color:#334155; font-weight:600; }
        .signature .title { font-size:9px; color:#64748b; }
        @media print { body { padding:10px; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">PT ENERGY LIMA PILAR</div>
        <div class="subtitle">Laporan Progres Proyek Instalasi PLTS</div>
        <div class="print-date">Dicetak pada: <?= date('d F Y H:i') ?> WIB</div>
    </div>

    <div class="section-title">INFORMASI PROYEK</div>
    <div class="info-grid">
        <div>
            <div class="info-row"><div class="info-label">Nama Proyek</div><div class="info-value"><strong><?= sanitize($proyek['nama_proyek']) ?></strong></div></div>
            <div class="info-row"><div class="info-label">Pelanggan</div><div class="info-value"><?= sanitize($proyek['nama_pelanggan']) ?></div></div>
            <div class="info-row"><div class="info-label">Lokasi</div><div class="info-value"><?= sanitize($proyek['lokasi']) ?></div></div>
            <div class="info-row"><div class="info-label">Deskripsi</div><div class="info-value"><?= sanitize($proyek['deskripsi'] ?? '-') ?></div></div>
        </div>
        <div>
            <div class="info-row"><div class="info-label">Tanggal Mulai</div><div class="info-value"><?= formatTanggal($proyek['tanggal_mulai']) ?></div></div>
            <div class="info-row"><div class="info-label">Target Selesai</div><div class="info-value"><?= formatTanggal($proyek['tanggal_selesai']) ?></div></div>
            <div class="info-row"><div class="info-label">Status</div><div class="info-value"><span class="badge badge-<?= $proyek['status'] ?>"><?= ucfirst($proyek['status']) ?></span></div></div>
            <div class="info-row">
                <div class="info-label">Progres</div>
                <div class="info-value">
                    <strong><?= $proyek['progres'] ?>%</strong>
                    <div class="progres-bar"><div class="progres-fill" style="width:<?= $proyek['progres'] ?>%"></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-title">JADWAL INSTALASI (<?= count($jadwals) ?> jadwal)</div>
    <table>
        <thead><tr><th>No</th><th>Tanggal Mulai</th><th>Tanggal Selesai</th><th>Keterangan</th><th>Status</th></tr></thead>
        <tbody>
            <?php if (empty($jadwals)): ?>
            <tr><td colspan="5" style="text-align:center;color:#94a3b8">Belum ada jadwal instalasi</td></tr>
            <?php else: ?>
            <?php foreach ($jadwals as $i => $j): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= formatTanggalShort($j['tanggal_mulai']) ?></td>
                <td><?= formatTanggalShort($j['tanggal_selesai']) ?></td>
                <td><?= sanitize($j['keterangan'] ?? '-') ?></td>
                <td><span class="badge badge-<?= $j['status'] ?>"><?= ucfirst($j['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="section-title">LAPORAN LAPANGAN (<?= count($laporans) ?> laporan)</div>
    <table>
        <thead><tr><th>No</th><th>Judul Laporan</th><th>Teknisi</th><th>Status</th><th>Tanggal</th></tr></thead>
        <tbody>
            <?php if (empty($laporans)): ?>
            <tr><td colspan="5" style="text-align:center;color:#94a3b8">Belum ada laporan lapangan</td></tr>
            <?php else: ?>
            <?php foreach ($laporans as $i => $l): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= sanitize($l['judul_laporan']) ?></td>
                <td><?= sanitize($l['nama_teknisi']) ?></td>
                <td><span class="badge badge-<?= $l['status_pengerjaan'] === 'terkendala' ? 'terkendala' : ($l['status_pengerjaan'] === 'selesai' ? 'selesai2' : 'instalasi') ?>"><?= str_replace('_',' ', ucfirst($l['status_pengerjaan'])) ?></span></td>
                <td><?= formatTanggalShort($l['tanggal_laporan']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="section-title">PENGGUNAAN MATERIAL (<?= count($materials) ?> catatan)</div>
    <table>
        <thead><tr><th>No</th><th>Komponen</th><th>Jumlah</th><th>Satuan</th><th>Tanggal</th><th>Keterangan</th></tr></thead>
        <tbody>
            <?php if (empty($materials)): ?>
            <tr><td colspan="6" style="text-align:center;color:#94a3b8">Belum ada penggunaan material</td></tr>
            <?php else: ?>
            <?php foreach ($materials as $i => $m): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= sanitize($m['nama_komponen']) ?></td>
                <td style="text-align:center"><?= $m['jumlah_digunakan'] ?></td>
                <td><?= sanitize($m['satuan']) ?></td>
                <td><?= formatTanggalShort($m['tanggal_penggunaan']) ?></td>
                <td><?= sanitize($m['keterangan'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <div class="left">
            <div>Sistem Monitoring PLTS — PT Energy Lima Pilar</div>
            <div>Dokumen ini digenerate otomatis oleh sistem</div>
        </div>
        <div class="signature">
            <div class="line"></div>
            <div class="name">Manajer Proyek</div>
            <div class="title">PT Energy Lima Pilar</div>
        </div>
    </div>
</body>
</html>
<script>window.onload = () => window.print();</script>