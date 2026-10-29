<?php
// File: pages/laporan/show.php
$pageTitle   = 'Detail Laporan Lapangan';
$currentPage = 'laporan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek','teknisi_lapangan']);

$db     = db();
$dbConn = $db;
$laporan_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$user_id_login = $_SESSION['user']['id'] ?? 1;

// Proses tambah komentar jika ada submit form komentar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_komentar']) && $laporan_id > 0) {
    $isi_komentar = trim($_POST['komentar'] ?? '');
    if ($isi_komentar !== '' && $dbConn instanceof PDO) {
        $stmtKom = $dbConn->prepare("INSERT INTO komentar_laporan (laporan_id, user_id, komentar) VALUES (?, ?, ?)");
        $stmtKom->execute([$laporan_id, $user_id_login, $isi_komentar]);
        header("Location: index.php?page=laporan&action=show&id=" . $laporan_id);
        exit;
    }
}

// Ambil detail laporan_lapangan beserta proyek, pelanggan, dan teknisi pembuat
$stmt = $dbConn->prepare("
    SELECT l.*, 
           p.nama_proyek, p.lokasi AS lokasi_proyek, p.progres AS progres_proyek,
           pel.nama_pelanggan,
           u.nama AS nama_teknisi, u.role AS role_pembuat
    FROM laporan_lapangan l
    JOIN proyek p ON l.proyek_id = p.id
    JOIN pelanggan pel ON p.pelanggan_id = pel.id
    JOIN users u ON l.user_id = u.id
    WHERE l.id = ?
");
$stmt->execute([$laporan_id]);
$laporan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$laporan) {
    echo '<div class="alert alert-warning m-4">Data laporan lapangan tidak ditemukan. <a href="index.php?page=laporan">Kembali</a></div>';
    return;
}

// Ambil foto_lapangan
$stmtFoto = $dbConn->prepare("SELECT * FROM foto_lapangan WHERE laporan_id = ? ORDER BY id ASC");
$stmtFoto->execute([$laporan_id]);
$foto_list = $stmtFoto->fetchAll(PDO::FETCH_ASSOC);

// Ambil komentar_laporan
$stmtKomen = $dbConn->prepare("
    SELECT k.*, u.nama, u.role 
    FROM komentar_laporan k 
    JOIN users u ON k.user_id = u.id 
    WHERE k.laporan_id = ? 
    ORDER BY k.created_at ASC
");
$stmtKomen->execute([$laporan_id]);
$komentar_list = $stmtKomen->fetchAll(PDO::FETCH_ASSOC);

// Decode JSON Manpower, Activity Progress, dan Next Plan
$mp = !empty($laporan['manpower']) ? json_decode($laporan['manpower'], true) : [];
$activities = !empty($laporan['activity_progress']) ? json_decode($laporan['activity_progress'], true) : [];
$next_plans = !empty($laporan['next_plan']) ? json_decode($laporan['next_plan'], true) : [];

// Helper Tanggal Indonesia untuk Teks WhatsApp
$hari_indo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'];
$bulan_indo = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$ts = strtotime($laporan['tanggal_laporan']);
$tgl_wa = $hari_indo[date('w', $ts)] . ', ' . date('j', $ts) . ' ' . $bulan_indo[(int)date('n', $ts)] . ' ' . date('Y', $ts);

// Susun Format Teks WhatsApp
$fmtMp = function($val) {
    $n = (int)($val ?? 0);
    return $n === 0 ? '-' : $n . ' orang';
};

$wa_activity_lines = [];
$done_count = 0;
if (!empty($activities)) {
    foreach ($activities as $idx => $act) {
        $is_done = !empty($act['done']);
        if ($is_done) $done_count++;
        $wa_activity_lines[] = ($idx + 1) . '. ' . $act['item'] . ($is_done ? ' ✓' : '');
    }
} else {
    $wa_activity_lines[] = $laporan['deskripsi'];
}

$wa_next_lines = [];
if (!empty($next_plans)) {
    foreach ($next_plans as $idx => $np) {
        $wa_next_lines[] = ($idx + 1) . '. ' . $np;
    }
} else {
    $wa_next_lines[] = '-';
}

$teks_wa_lengkap = "_Selamat malam_\n\n"
    . "*Daily Report Project*\n"
    . "_" . ($laporan['judul_laporan'] ?: 'Maintenance Solar PV Rooftop') . "_\n"
    . "*" . $laporan['nama_pelanggan'] . " (" . $laporan['nama_proyek'] . ")*\n\n\n"
    . "Date :\n" . $tgl_wa . "\n\n\n"
    . "1. Project Manager : " . $fmtMp($mp['pm'] ?? 0) . "\n"
    . "2. Site Manager : " . $fmtMp($mp['sm'] ?? 0) . "\n"
    . "3. Pengawas Teknik : " . $fmtMp($mp['pengawas'] ?? 0) . "\n"
    . "4. Admin : " . $fmtMp($mp['admin'] ?? 0) . "\n"
    . "5. Hse / Safetyman : " . $fmtMp($mp['hse'] ?? 0) . "\n"
    . "6. Scaffolder : " . $fmtMp($mp['scaffolder'] ?? 0) . "\n"
    . "7. Teknisi : " . $fmtMp($mp['teknisi'] ?? 0) . "\n"
    . "8. Helper : " . $fmtMp($mp['helper'] ?? 0) . "\n"
    . "   Total =  " . (int)($mp['total'] ?? 0) . " orang\n\n"
    . "*Weather Report*\n"
    . ($laporan['cuaca'] ?? '08.00 - 17.00 Cerah') . "\n\n"
    . "*Activity Progress :*\n"
    . " " . ($laporan['area_kerja'] ?? '-') . "\n\n\n"
    . implode("\n", $wa_activity_lines) . "\n\n"
    . "*Next Plan* :\n\n\n"
    . implode("\n", $wa_next_lines) . "\n\n"
    . "_terima kasih_";
?>

<?php
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<main class="flex-1 overflow-y-auto p-6">

<div class="container-fluid py-3">
    <!-- Header Navigasi -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-file-earmark-text text-primary me-2"></i><?= htmlspecialchars($laporan['judul_laporan']) ?>
            </h4>
            <div class="text-muted small">
                Proyek: <strong><?= htmlspecialchars($laporan['nama_proyek']) ?></strong> — Klien: <strong><?= htmlspecialchars($laporan['nama_pelanggan']) ?></strong>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?page=laporan" class="btn btn-outline-secondary btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- KOLOM KIRI: RINCIAN LAPORAN LAPANGAN -->
        <div class="col-lg-7">
            
            <!-- Ringkasan Info Laporan -->
            <div class="report-card">
                <div class="report-card-header">
                    <span><i class="bi bi-info-circle text-primary me-2"></i>Informasi Umum Laporan</span>
                    <?php
                    $badge_status = [
                        'belum_mulai'  => 'bg-secondary',
                        'dalam_proses' => 'bg-primary',
                        'selesai'      => 'bg-success',
                        'terkendala'   => 'bg-danger'
                    ][$laporan['status_pengerjaan']] ?? 'bg-secondary';
                    ?>
                    <span class="badge <?= $badge_status ?> text-uppercase"><?= str_replace('_', ' ', $laporan['status_pengerjaan']) ?></span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 small">
                        <div class="col-sm-6">
                            <div class="text-muted">Tanggal Laporan</div>
                            <div class="fw-bold fs-6"><?= htmlspecialchars($tgl_wa) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Area / Gedung Spesifik</div>
                            <div class="fw-bold fs-6"><i class="bi bi-building me-1 text-warning"></i><?= htmlspecialchars($laporan['area_kerja'] ?? '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Weather Report (Cuaca)</div>
                            <div class="fw-semibold"><i class="bi bi-cloud-sun me-1 text-info"></i><?= htmlspecialchars($laporan['cuaca'] ?? '08.00 - 17.00 Cerah') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Dilaporkan Oleh</div>
                            <div class="fw-semibold"><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($laporan['nama_teknisi']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rincian Manpower -->
            <div class="report-card">
                <div class="report-card-header">
                    <span><i class="bi bi-people-fill text-warning me-2"></i>Manpower (Personel di Lapangan)</span>
                    <span class="badge bg-primary">Total: <?= (int)($mp['total'] ?? 0) ?> Orang</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-2 small">
                        <?php
                        $daftar_posisi = [
                            'pm'         => '1. Project Manager',
                            'sm'         => '2. Site Manager',
                            'pengawas'   => '3. Pengawas Teknik',
                            'admin'      => '4. Admin',
                            'hse'        => '5. HSE / Safetyman',
                            'scaffolder' => '6. Scaffolder',
                            'teknisi'    => '7. Teknisi',
                            'helper'     => '8. Helper',
                        ];
                        foreach ($daftar_posisi as $k => $label):
                            $jml = (int)($mp[$k] ?? 0);
                        ?>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-light d-flex justify-content-between align-items-center">
                                <span class="text-muted"><?= $label ?></span>
                                <span class="fw-bold <?= $jml > 0 ? 'text-dark' : 'text-secondary' ?>"><?= $jml > 0 ? $jml . ' Org' : '-' ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Rincian Activity Progress & Next Plan -->
            <div class="report-card">
                <div class="report-card-header">
                    <span><i class="bi bi-list-check text-success me-2"></i>Activity Progress — <?= htmlspecialchars($laporan['area_kerja'] ?? '') ?></span>
                    <?php if (!empty($activities)): ?>
                        <span class="badge bg-success"><?= $done_count ?>/<?= count($activities) ?> Selesai</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($activities)): ?>
                        <ul class="list-group list-group-flush mb-3">
                            <?php foreach ($activities as $idx => $act): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span><strong><?= $idx + 1 ?>.</strong> <?= htmlspecialchars($act['item']) ?></span>
                                    <?php if (!empty($act['done'])): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">✓ Selesai</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">Belum Selesai</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="mb-3" style="white-space: pre-line;"><?= htmlspecialchars($laporan['deskripsi']) ?></p>
                    <?php endif; ?>

                    <h6 class="fw-bold border-top pt-3 mt-3"><i class="bi bi-calendar2-plus text-primary me-2"></i>Next Plan (Rencana Selanjutnya)</h6>
                    <?php if (!empty($next_plans)): ?>
                        <ol class="mb-0 ps-3">
                            <?php foreach ($next_plans as $np): ?>
                                <li class="mb-1"><?= htmlspecialchars($np) ?></li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <p class="text-muted small mb-0">Tidak ada catatan next plan.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Galeri Foto Dokumentasi Lapangan -->
            <div class="report-card">
                <div class="report-card-header">
                    <span><i class="bi bi-images text-info me-2"></i>Foto Dokumentasi Lapangan (<?= count($foto_list) ?>)</span>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($foto_list)): ?>
                        <div class="row g-3">
                            <?php foreach ($foto_list as $foto): ?>
                                <div class="col-6 col-md-4">
                                    <a href="<?= htmlspecialchars($foto['path_file']) ?>" target="_blank">
                                        <img src="<?= htmlspecialchars($foto['path_file']) ?>" alt="<?= htmlspecialchars($foto['nama_file']) ?>" class="foto-lapangan-thumb">
                                    </a>
                                    <?php if (!empty($foto['keterangan'])): ?>
                                        <div class="small text-muted mt-1 text-truncate"><?= htmlspecialchars($foto['keterangan']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-muted small">Belum ada foto dokumentasi yang diunggah pada laporan ini.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Diskusi / Komentar Laporan (komentar_laporan) -->
            <div class="report-card">
                <div class="report-card-header">
                    <span><i class="bi bi-chat-dots text-secondary me-2"></i>Komentar & Evaluasi Manajer Proyek (<?= count($komentar_list) ?>)</span>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($komentar_list)): ?>
                        <div class="mb-3">
                            <?php foreach ($komentar_list as $kom): ?>
                                <div class="p-3 rounded bg-light border mb-2">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <strong><?= htmlspecialchars($kom['nama']) ?> <span class="badge bg-secondary ms-1"><?= htmlspecialchars($kom['role']) ?></span></strong>
                                        <span class="text-muted"><?= htmlspecialchars($kom['created_at']) ?></span>
                                    </div>
                                    <div class="small"><?= nl2br(htmlspecialchars($kom['komentar'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="input-group">
                            <input type="text" name="komentar" class="form-control" placeholder="Tulis catatan atau tanggapan untuk laporan ini..." required>
                            <button type="submit" name="kirim_komentar" class="btn btn-primary">
                                <i class="bi bi-send me-1"></i>Kirim
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        <!-- KOLOM KANAN: FORMAT WHATSAPP SIAP SALIN -->
        <div class="col-lg-5">
            <div class="card wa-preview-card sticky-top" style="top: 20px;">
                <div class="wa-preview-header">
                    <span class="fw-bold"><i class="bi bi-whatsapp me-2"></i>Format Teks WhatsApp</span>
                    <button type="button" class="btn btn-sm btn-light fw-semibold" onclick="salinTeksDetailWA()">
                        <i class="bi bi-clipboard-check me-1"></i>Copy Teks WA
                    </button>
                </div>
                <div class="card-body p-3 bg-white">
                    <div class="wa-chat-wallpaper">
                        <div class="wa-chat-bubble">
                            <div id="waDetailText"><?= htmlspecialchars($teks_wa_lengkap) ?></div>
                            <span class="wa-bubble-time"><?= date('H:i', strtotime($laporan['created_at'])) ?> ✓✓</span>
                        </div>
                    </div>
                    <div id="alertCopyShow" class="alert alert-success py-2 px-3 mt-2 mb-0 small d-none">
                        <i class="bi bi-check2-all me-1"></i>Teks laporan berhasil disalin ke clipboard!
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function salinTeksDetailWA() {
    const teks = document.getElementById('waDetailText').innerText;
    navigator.clipboard.writeText(teks).then(() => {
        const el = document.getElementById('alertCopyShow');
        el.classList.remove('d-none');
        setTimeout(() => el.classList.add('d-none'), 3000);
    });
}
</script>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>