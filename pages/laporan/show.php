<?php
// File: pages/laporan/show.php
$pageTitle   = 'Detail Laporan Lapangan';
$currentPage = 'laporan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/activity.php';

requireRole(['admin', 'manajer_proyek', 'teknisi_lapangan']);

$db         = db();
$user       = currentUser();
$laporan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$laporan_id) {
    setFlash('error', 'ID laporan tidak valid.');
    redirect(BASE_URL . '/pages/laporan/index.php');
}

// Proses tambah komentar diskusi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_komentar'])) {
    if (!verifyCsrf()) {
        setFlash('error', 'Token keamanan tidak valid.');
        redirect(BASE_URL . '/pages/laporan/show.php?id=' . $laporan_id);
    }
    $isi_komentar = trim($_POST['komentar'] ?? '');
    if ($isi_komentar !== '') {
        $stmtKom = $db->prepare("INSERT INTO komentar_laporan (laporan_id, user_id, komentar) VALUES (?, ?, ?)");
        $stmtKom->execute([$laporan_id, $user['id'], $isi_komentar]);
        logActivity('komentar_laporan', 'create', "Menambahkan komentar pada laporan #$laporan_id", 'laporan');
        setFlash('success', 'Komentar berhasil ditambahkan.');
        redirect(BASE_URL . '/pages/laporan/show.php?id=' . $laporan_id);
    }
}

// Ambil detail laporan_lapangan beserta proyek, pelanggan, jadwal, dan teknisi pembuat
$stmt = $db->prepare("
    SELECT l.*, 
           p.nama_proyek, p.lokasi AS lokasi_proyek, p.progres AS progres_proyek, p.status AS status_proyek,
           pel.nama_pelanggan,
           u.nama AS nama_teknisi, u.role AS role_pembuat,
           j.keterangan AS nama_jadwal, j.tanggal_mulai AS jadwal_mulai, j.tanggal_selesai AS jadwal_selesai
    FROM laporan_lapangan l
    JOIN proyek p ON l.proyek_id = p.id
    JOIN pelanggan pel ON p.pelanggan_id = pel.id
    JOIN users u ON l.user_id = u.id
    LEFT JOIN jadwal j ON l.jadwal_id = j.id
    WHERE l.id = ?
");
$stmt->execute([$laporan_id]);
$laporan = $stmt->fetch();

if (!$laporan) {
    setFlash('error', 'Data laporan lapangan tidak ditemukan.');
    redirect(BASE_URL . '/pages/laporan/index.php');
}

// Jika teknisi lapangan, pastikan hanya bisa melihat laporannya sendiri (atau proyeknya)
if (isTeknisi() && (int)$laporan['user_id'] !== (int)$user['id']) {
    setFlash('error', 'Anda tidak memiliki akses ke laporan ini.');
    redirect(BASE_URL . '/pages/laporan/index.php');
}

// Ambil daftar foto_lapangan
$stmtFoto = $db->prepare("SELECT * FROM foto_lapangan WHERE laporan_id = ? ORDER BY id ASC");
$stmtFoto->execute([$laporan_id]);
$foto_list = $stmtFoto->fetchAll();

// Helper URL foto agar mendukung path lama ('assets/uploads/...') maupun standar ('laporan/...')
$resolveFotoUrl = function (string $path): string {
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    if (str_starts_with($path, 'assets/') || str_starts_with($path, 'uploads/')) {
        return BASE_URL . '/' . ltrim($path, '/');
    }
    return BASE_URL . '/uploads/' . ltrim($path, '/');
};

// Ambil daftar komentar_laporan
$stmtKomen = $db->prepare("
    SELECT k.*, u.nama, u.role 
    FROM komentar_laporan k 
    JOIN users u ON k.user_id = u.id 
    WHERE k.laporan_id = ? 
    ORDER BY k.created_at ASC
");
$stmtKomen->execute([$laporan_id]);
$komentar_list = $stmtKomen->fetchAll();

// Decode JSON Manpower, Activity Progress, dan Next Plan
$mp         = !empty($laporan['manpower']) ? json_decode($laporan['manpower'], true) : [];
$activities = !empty($laporan['activity_progress']) ? json_decode($laporan['activity_progress'], true) : [];
$next_plans = !empty($laporan['next_plan']) ? json_decode($laporan['next_plan'], true) : [];

// Format Tanggal Bahasa Indonesia untuk Teks WhatsApp
$hari_indo  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'];
$bulan_indo = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$ts         = strtotime($laporan['tanggal_laporan']);
$tgl_wa     = $hari_indo[(int)date('w', $ts)] . ', ' . date('j', $ts) . ' ' . $bulan_indo[(int)date('n', $ts)] . ' ' . date('Y', $ts);

// Susun Format Teks WhatsApp PT Energy Lima Pilar
$fmtMp = function ($val): string {
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
} elseif (!empty($laporan['deskripsi'])) {
    $wa_activity_lines[] = $laporan['deskripsi'];
}

$total_act  = count($activities);
$pct_harian = $total_act > 0 ? round(($done_count / $total_act) * 100) : 100;

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
    . "*" . $laporan['nama_pelanggan'] . "*\n\n\n"
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

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <a href="<?= BASE_URL ?>/pages/laporan/index.php">Laporan Lapangan</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Detail Laporan #<?= $laporan['id'] ?></span>
    </nav>

    <!-- Header Halaman -->
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div class="flex items-center gap-3.5">
            <a href="<?= BASE_URL ?>/pages/laporan/index.php"
                class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors shadow-xs">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight"><?= sanitize($laporan['judul_laporan']) ?></h2>
                    <?= statusBadge($laporan['status_pengerjaan']) ?>
                </div>
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1 font-medium text-primary-700">
                        <i class="bi bi-diagram-3"></i>
                        <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $laporan['proyek_id'] ?>" class="hover:underline">
                            <?= sanitize($laporan['nama_proyek']) ?>
                        </a>
                    </span>
                    <span class="text-slate-300">·</span>
                    <span class="inline-flex items-center gap-1"><i class="bi bi-building"></i> <?= sanitize($laporan['nama_pelanggan']) ?></span>
                    <span class="text-slate-300">·</span>
                    <span class="inline-flex items-center gap-1"><i class="bi bi-clock"></i> Diunggah <?= timeAgo($laporan['created_at']) ?></span>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="btn-secondary btn-sm">
                <i class="bi bi-printer"></i> Cetak
            </button>
            <a href="<?= BASE_URL ?>/pages/laporan/create.php" class="btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Buat Laporan Baru
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <!-- KOLOM KIRI: RINCIAN LAPORAN -->
        <div class="xl:col-span-7 space-y-5">

            <!-- 1. Ringkasan Informasi Lapangan -->
            <div class="card p-5">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Tanggal</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block"><?= formatTanggal($laporan['tanggal_laporan']) ?></span>
                    </div>
                    <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-100">
                        <span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider block">Area / Gedung</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block truncate">
                            <i class="bi bi-geo-alt-fill text-amber-500 mr-1"></i><?= sanitize($laporan['area_kerja'] ?: '-') ?>
                        </span>
                    </div>
                    <div class="p-3 rounded-xl bg-sky-50/60 border border-sky-100">
                        <span class="text-[11px] font-bold text-sky-600 uppercase tracking-wider block">Cuaca & Jam</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block truncate">
                            <i class="bi bi-cloud-sun-fill text-sky-500 mr-1"></i><?= sanitize($laporan['cuaca'] ?: 'Cerah') ?>
                        </span>
                    </div>
                    <div class="p-3 rounded-xl bg-emerald-50/60 border border-emerald-100">
                        <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider block">Teknisi Pelapor</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block truncate">
                            <i class="bi bi-person-check-fill text-emerald-500 mr-1"></i><?= sanitize($laporan['nama_teknisi']) ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($laporan['kontribusi_progres']) || isset($laporan['progres_proyek'])): ?>
                <?php $progTampil = (int)($laporan['kontribusi_progres'] ?? $laporan['progres_proyek']); ?>
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        <span class="font-semibold text-slate-600">Capaian Progres Proyek Saat Laporan Dibuat</span>
                        <span class="font-extrabold text-primary-700"><?= $progTampil ?>%</span>
                    </div>
                    <div class="h-2 progress-track rounded-full overflow-hidden">
                        <div class="h-full progress-fill rounded-full" style="width: <?= $progTampil ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- 2. Rincian Manpower (Personel) -->
            <div class="card p-5">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i class="bi bi-people-fill"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Manpower (Personel Lapangan)</h3>
                            <p class="text-xs text-slate-400">Distribusi tim yang bertugas di lokasi proyek</p>
                        </div>
                    </div>
                    <span class="badge-modern bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200">
                        Total: <?= (int)($mp['total'] ?? 0) ?> Orang
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <?php
                    $daftar_posisi = [
                        'pm'         => ['1. Project Manager', 'bi-person-workspace'],
                        'sm'         => ['2. Site Manager',    'bi-person-badge'],
                        'pengawas'   => ['3. Pengawas Teknik', 'bi-person-lines-fill'],
                        'admin'      => ['4. Admin',           'bi-laptop'],
                        'hse'        => ['5. HSE / Safetyman', 'bi-shield-check'],
                        'scaffolder' => ['6. Scaffolder',      'bi-ladder'],
                        'teknisi'    => ['7. Teknisi',         'bi-tools'],
                        'helper'     => ['8. Helper',          'bi-person-raised-hand'],
                    ];
                    foreach ($daftar_posisi as $k => [$label, $icon]):
                        $jml = (int)($mp[$k] ?? 0);
                        $aktifCls = $jml > 0
                            ? 'bg-white border-slate-200 shadow-2xs'
                            : 'bg-slate-50/60 border-slate-100 opacity-60';
                    ?>
                    <div class="p-3 rounded-xl border <?= $aktifCls ?> flex items-center justify-between">
                        <div class="min-w-0 pr-2">
                            <p class="text-[11px] font-semibold text-slate-500 truncate"><?= $label ?></p>
                            <p class="text-base font-extrabold <?= $jml > 0 ? 'text-slate-900' : 'text-slate-400' ?> mt-0.5">
                                <?= $jml > 0 ? $jml . ' <span class="text-xs font-medium text-slate-400">Org</span>' : '-' ?>
                            </p>
                        </div>
                        <i class="bi <?= $icon ?> text-lg <?= $jml > 0 ? 'text-indigo-500' : 'text-slate-300' ?>"></i>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. Activity Progress & Next Plan -->
            <div class="card p-5">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i class="bi bi-list-check"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">
                                Activity Progress <?= !empty($laporan['area_kerja']) ? '— ' . sanitize($laporan['area_kerja']) : '' ?>
                            </h3>
                            <p class="text-xs text-slate-400">Daftar item pekerjaan yang dieksekusi hari ini</p>
                        </div>
                    </div>
                    <?php if (!empty($activities)): ?>
                    <span class="badge-modern bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                        <i class="bi bi-check2-circle"></i> <?= $done_count ?>/<?= $total_act ?> Selesai (<?= $pct_harian ?>%)
                    </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($activities)): ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($activities as $idx => $act):
                        $is_done = !empty($act['done']);
                    ?>
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-6 h-6 rounded-full text-xs font-bold flex items-center justify-center flex-shrink-0 <?= $is_done ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                                <?= $idx + 1 ?>
                            </span>
                            <span class="text-sm font-medium text-slate-800"><?= sanitize($act['item']) ?></span>
                        </div>
                        <?php if ($is_done): ?>
                        <span class="badge-modern bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 flex-shrink-0">
                            ✓ Selesai
                        </span>
                        <?php else: ?>
                        <span class="badge-modern bg-amber-50 text-amber-700 ring-1 ring-amber-200 flex-shrink-0">
                            <i class="bi bi-hourglass-split"></i> Lanjut Besok
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="p-4 rounded-xl bg-slate-50 text-sm text-slate-700 whitespace-pre-line">
                    <?= sanitize($laporan['deskripsi']) ?>
                </div>
                <?php endif; ?>

                <!-- Next Plan -->
                <div class="mt-5 pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-1.5">
                        <i class="bi bi-calendar2-plus text-primary-600"></i> Next Plan (Rencana Selanjutnya)
                    </h4>
                    <?php if (!empty($next_plans)): ?>
                    <div class="space-y-2">
                        <?php foreach ($next_plans as $idx => $np): ?>
                        <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-sm text-slate-700">
                            <span class="w-5 h-5 rounded-md bg-primary-100 text-primary-700 text-xs font-bold flex items-center justify-center flex-shrink-0">
                                <?= $idx + 1 ?>
                            </span>
                            <span><?= sanitize($np) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-xs text-slate-400 italic">Tidak ada catatan rencana selanjutnya.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 4. Galeri Foto Dokumentasi Lapangan (Dengan Interactive Lightbox) -->
            <div class="card p-5">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                            <i class="bi bi-images"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Foto Dokumentasi Lapangan</h3>
                            <p class="text-xs text-slate-400">Klik foto untuk memperbesar (Lightbox Preview)</p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold text-slate-400"><?= count($foto_list) ?> Foto</span>
                </div>

                <?php if (!empty($foto_list)): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <?php foreach ($foto_list as $idx => $foto):
                        $imgUrl = $resolveFotoUrl($foto['path_file']);
                    ?>
                    <div class="group relative aspect-video rounded-xl overflow-hidden bg-slate-100 border border-slate-200 cursor-pointer"
                         onclick="openLightbox(<?= $idx ?>)">
                        <img src="<?= sanitize($imgUrl) ?>"
                             alt="<?= sanitize($foto['nama_file']) ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                             loading="lazy">
                        <div class="absolute inset-0 bg-slate-900/0 group-hover:bg-slate-900/40 transition-colors flex items-center justify-center">
                            <span class="w-9 h-9 rounded-full bg-white/90 text-slate-800 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-md">
                                <i class="bi bi-zoom-in"></i>
                            </span>
                        </div>
                        <?php if (!empty($foto['keterangan'])): ?>
                        <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/70 to-transparent p-2">
                            <p class="text-[11px] text-white truncate"><?= sanitize($foto['keterangan']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="py-8 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2">
                        <i class="bi bi-camera text-xl"></i>
                    </div>
                    <p class="text-sm text-slate-400">Belum ada foto dokumentasi yang dilampirkan.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- 5. Diskusi & Evaluasi Laporan (komentar_laporan) -->
            <div class="card p-5">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center">
                            <i class="bi bi-chat-dots-fill"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Diskusi & Catatan Evaluasi</h3>
                            <p class="text-xs text-slate-400"><?= count($komentar_list) ?> tanggapan pada laporan ini</p>
                        </div>
                    </div>
                </div>

                <?php if (!empty($komentar_list)): ?>
                <div class="space-y-3 mb-4 max-h-80 overflow-y-auto pr-1">
                    <?php foreach ($komentar_list as $kom):
                        $isMe = (int)$kom['user_id'] === (int)$user['id'];
                    ?>
                    <div class="p-3.5 rounded-xl border <?= $isMe ? 'bg-indigo-50/40 border-indigo-100' : 'bg-slate-50 border-slate-100' ?>">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-md bg-primary-600 text-white text-[11px] font-bold flex items-center justify-center">
                                    <?= strtoupper(substr($kom['nama'], 0, 1)) ?>
                                </span>
                                <span class="text-xs font-bold text-slate-800"><?= sanitize($kom['nama']) ?></span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-white border border-slate-200 text-slate-500 font-semibold">
                                    <?= ucfirst(str_replace('_', ' ', $kom['role'])) ?>
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-400"><?= timeAgo($kom['created_at']) ?></span>
                        </div>
                        <p class="text-sm text-slate-700 pl-8 leading-relaxed"><?= nl2br(sanitize($kom['komentar'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="flex gap-2">
                    <?= csrfField() ?>
                    <input type="text" name="komentar" class="form-input flex-1"
                           placeholder="Tulis catatan evaluasi atau tanggapan..." required>
                    <button type="submit" name="kirim_komentar" class="btn-primary">
                        <i class="bi bi-send"></i> Kirim
                    </button>
                </form>
            </div>

        </div>

        <!-- KOLOM KANAN: PREVIEW & SHARE WHATSAPP -->
        <div class="xl:col-span-5">
            <div class="card overflow-hidden sticky top-6 border-0 shadow-lg">
                <div class="bg-[#075e54] px-5 py-3.5 flex items-center justify-between text-white">
                    <div class="flex items-center gap-2.5">
                        <i class="bi bi-whatsapp text-xl"></i>
                        <div>
                            <h3 class="text-sm font-bold leading-tight">Format Daily Report WhatsApp</h3>
                            <p class="text-[11px] text-emerald-100">Siap salin atau kirim langsung ke grup</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="salinTeksDetailWA()"
                                class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 text-white text-xs font-bold transition-colors flex items-center gap-1">
                            <i class="bi bi-clipboard-check"></i> Copy
                        </button>
                        <a href="https://api.whatsapp.com/send?text=<?= rawurlencode($teks_wa_lengkap) ?>"
                           target="_blank"
                           class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-white text-xs font-bold transition-colors flex items-center gap-1 shadow-sm">
                            <i class="bi bi-send-fill"></i> Share WA
                        </a>
                    </div>
                </div>

                <div class="p-4" style="background-color: #efeae2; background-image: radial-gradient(#d1cbbf 1px, transparent 1px); background-size: 18px 18px;">
                    <div class="bg-[#d9fdd3] text-[#111b21] p-3.5 rounded-xl rounded-tr-none shadow-sm text-[13px] leading-relaxed">
                        <pre id="waDetailText" class="whitespace-pre-wrap font-sans m-0"><?= sanitize($teks_wa_lengkap) ?></pre>
                        <div class="text-right mt-1.5 text-[10px] text-slate-500">
                            <?= date('H:i', strtotime($laporan['created_at'])) ?> ✓✓
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL LIGHTBOX FOTO DOKUMENTASI -->
<?php if (!empty($foto_list)): ?>
<div id="lightboxModal" class="fixed inset-0 bg-slate-950/90 backdrop-blur-xs z-[999] hidden items-center justify-center p-4"
     onclick="if(event.target===this) closeLightbox()">
    <button type="button" onclick="closeLightbox()"
            class="absolute top-5 right-5 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-lg transition-colors">
        <i class="bi bi-x-lg"></i>
    </button>

    <?php if (count($foto_list) > 1): ?>
    <button type="button" onclick="navLightbox(-1)"
            class="absolute left-4 sm:left-6 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-xl transition-colors">
        <i class="bi bi-chevron-left"></i>
    </button>
    <button type="button" onclick="navLightbox(1)"
            class="absolute right-4 sm:right-6 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-xl transition-colors">
        <i class="bi bi-chevron-right"></i>
    </button>
    <?php endif; ?>

    <div class="max-w-4xl max-h-[85vh] flex flex-col items-center">
        <img id="lightboxImg" src="" alt="Preview Foto" class="max-h-[78vh] max-w-full object-contain rounded-xl shadow-2xl">
        <p id="lightboxCaption" class="text-white/90 text-sm font-medium mt-3 text-center"></p>
    </div>
</div>
<?php endif; ?>

<script>
function salinTeksDetailWA() {
    const teks = document.getElementById('waDetailText').innerText;
    navigator.clipboard.writeText(teks).then(() => {
        if (typeof Toast !== 'undefined') {
            Toast.show('Format laporan WhatsApp berhasil disalin!', 'success');
        }
    });
}

// Lightbox Gallery Logic
const galleryPhotos = <?= json_encode(array_map(function ($f) use ($resolveFotoUrl) {
    return [
        'url'     => $resolveFotoUrl($f['path_file']),
        'caption' => ($f['keterangan'] ? $f['keterangan'] . ' — ' : '') . $f['nama_file']
    ];
}, $foto_list)) ?>;

let currentPhotoIdx = 0;

function openLightbox(idx) {
    if (!galleryPhotos.length) return;
    currentPhotoIdx = idx;
    updateLightboxContent();
    const modal = document.getElementById('lightboxModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeLightbox() {
    const modal = document.getElementById('lightboxModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function navLightbox(dir) {
    currentPhotoIdx = (currentPhotoIdx + dir + galleryPhotos.length) % galleryPhotos.length;
    updateLightboxContent();
}

function updateLightboxContent() {
    const item = galleryPhotos[currentPhotoIdx];
    document.getElementById('lightboxImg').src = item.url;
    document.getElementById('lightboxCaption').textContent = `${currentPhotoIdx + 1} / ${galleryPhotos.length} — ${item.caption}`;
}

document.addEventListener('keydown', e => {
    const modal = document.getElementById('lightboxModal');
    if (!modal || modal.classList.contains('hidden')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') navLightbox(-1);
    if (e.key === 'ArrowRight') navLightbox(1);
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>