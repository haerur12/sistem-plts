<?php
// pages/proyek/gantt.php — INTERACTIVE GANTT CHART & TIMELINE PROYEK V2
$pageTitle   = 'Gantt Chart Proyek';
$currentPage = 'gantt';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'manajer_proyek']);

$db           = db();
$filterStatus = trim($_GET['status'] ?? '');

$where  = ['1=1'];
$params = [];
if ($filterStatus !== '') {
    $where[]  = 'p.status = ?';
    $params[] = $filterStatus;
}
$whereStr = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT p.*, pl.nama_pelanggan
    FROM proyek p
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    WHERE $whereStr
    ORDER BY p.tanggal_mulai ASC, p.id ASC
");
$stmt->execute($params);
$proyeks = $stmt->fetchAll();

// Ambil daftar personel tim per proyek (dari tabel proyek_tim)
$timPerProyek = [];
try {
    $stmtTim = $db->query("SELECT proyek_id, nama_personel, peran_proyek FROM proyek_tim ORDER BY id ASC");
    foreach ($stmtTim->fetchAll() as $t) {
        $timPerProyek[(int)$t['proyek_id']][] = $t;
    }
} catch (Exception $e) {
    $timPerProyek = [];
}

// Ambil sub-jadwal instalasi per proyek
$jadwalPerProyek = [];
$stmtJadwal = $db->query("SELECT * FROM jadwal ORDER BY tanggal_mulai ASC");
foreach ($stmtJadwal->fetchAll() as $j) {
    $jadwalPerProyek[(int)$j['proyek_id']][] = $j;
}

// Hitung rentang tanggal global untuk sumbu X Gantt Chart
$todayTs = strtotime(date('Y-m-d'));
$minTs   = strtotime('-15 days', $todayTs);
$maxTs   = strtotime('+45 days', $todayTs);

foreach ($proyeks as $p) {
    $sTs = strtotime($p['tanggal_mulai']);
    $eTs = strtotime($p['tanggal_selesai']);
    if ($sTs && $sTs < $minTs) $minTs = $sTs;
    if ($eTs && $eTs > $maxTs) $maxTs = $eTs;
}

// Beri bantalan 5 hari di awal dan 7 hari di akhir
$minTs = strtotime('-5 days', $minTs);
$maxTs = strtotime('+7 days', $maxTs);

$totalDays = max(14, (int)round(($maxTs - $minTs) / 86400) + 1);
// Batasi maksimal 180 hari agar tetap ringan dirender
if ($totalDays > 180) {
    $minTs     = strtotime('-60 days', $todayTs);
    $maxTs     = strtotime('+90 days', $todayTs);
    $totalDays = 151;
}

$dayWidthPx  = 34; // Lebar per kolom hari (34px)
$chartWidth  = $totalDays * $dayWidthPx;
$todayOffset = (int)round(($todayTs - $minTs) / 86400);
$todayLeftPx = ($todayOffset >= 0 && $todayOffset <= $totalDays) ? ($todayOffset * $dayWidthPx) + ($dayWidthPx / 2) : -1;

// Kelompokkan header bulan untuk baris atas timeline
$monthsHeader = [];
$daysHeader   = [];
$namaBulanId  = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

for ($d = 0; $d < $totalDays; $d++) {
    $currTs = strtotime("+$d days", $minTs);
    $ym     = date('Y-m', $currTs);
    $mLabel = $namaBulanId[(int)date('n', $currTs)] . ' ' . date('Y', $currTs);

    if (!isset($monthsHeader[$ym])) {
        $monthsHeader[$ym] = ['label' => $mLabel, 'count' => 0];
    }
    $monthsHeader[$ym]['count']++;

    $daysHeader[] = [
        'ts'        => $currTs,
        'date'      => date('Y-m-d', $currTs),
        'day'       => date('d', $currTs),
        'isWeekend' => in_array((int)date('N', $currTs), [6, 7], true),
        'isToday'   => (date('Y-m-d', $currTs) === date('Y-m-d', $todayTs)),
    ];
}

// Hitung metrik ringkas
$jmlOverdue = 0;
$jmlAktif   = 0;
foreach ($proyeks as $p) {
    if (in_array($p['status'], ['survei', 'perencanaan', 'instalasi'], true)) {
        $jmlAktif++;
        if (strtotime($p['tanggal_selesai']) < $todayTs) {
            $jmlOverdue++;
        }
    }
}

$barColors = [
    'survei'      => ['track' => 'bg-slate-200',   'fill' => 'bg-slate-600',   'border' => 'border-slate-300'],
    'perencanaan' => ['track' => 'bg-sky-100',     'fill' => 'bg-sky-500',     'border' => 'border-sky-300'],
    'instalasi'   => ['track' => 'bg-indigo-100',  'fill' => 'bg-indigo-600',  'border' => 'border-indigo-300'],
    'selesai'     => ['track' => 'bg-emerald-100', 'fill' => 'bg-emerald-500', 'border' => 'border-emerald-300'],
    'ditunda'     => ['track' => 'bg-amber-100',   'fill' => 'bg-amber-500',   'border' => 'border-amber-300'],
];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <a href="<?= BASE_URL ?>/pages/proyek/index.php">Proyek</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Gantt Chart & Timeline</span>
    </nav>

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <div class="flex items-center gap-3.5">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-600 to-indigo-700 text-white flex items-center justify-center shadow-lg shadow-primary-600/25">
                <i class="bi bi-calendar-range-fill text-lg"></i>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Gantt Chart & Timeline Proyek PLTS</h2>
                <p class="text-xs text-slate-500 mt-0.5">Visualisasi rentang waktu kontrak, capaian progres, dan tahapan jadwal instalasi</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="scrollToToday()" class="btn-secondary btn-sm text-red-600 border-red-200 hover:bg-red-50">
                <i class="bi bi-geo-fill"></i> Fokus Hari Ini (<?= date('d M') ?>)
            </button>
            <a href="<?= BASE_URL ?>/pages/proyek/kanban.php" class="btn-secondary btn-sm">
                <i class="bi bi-kanban"></i> Kanban Board
            </a>
            <a href="<?= BASE_URL ?>/pages/proyek/index.php" class="btn-secondary btn-sm">
                <i class="bi bi-table"></i> Tabel Proyek
            </a>
        </div>
    </div>

    <!-- Filter & Legend Bar -->
    <div class="card p-4 mb-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2 flex-wrap">
            <?php
            $filterOpts = [
                ''            => 'Semua (' . count($proyeks) . ')',
                'survei'      => 'Survei',
                'perencanaan' => 'Perencanaan',
                'instalasi'   => 'Instalasi',
                'selesai'     => 'Selesai',
                'ditunda'     => 'Ditunda',
            ];
            foreach ($filterOpts as $fKey => $fLbl):
                $isAct = ($filterStatus === $fKey);
            ?>
            <a href="?status=<?= urlencode($fKey) ?>"
               class="px-3 py-1.5 rounded-xl text-xs font-bold border transition-all
               <?= $isAct ? 'bg-slate-900 text-white border-slate-900 shadow-2xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' ?>">
                <?= $fLbl ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="flex items-center gap-4 text-xs flex-wrap">
            <span class="inline-flex items-center gap-1.5 font-semibold text-slate-600">
                <span class="w-3 h-0.5 bg-red-500 inline-block"></span> Garis Hari Ini
            </span>
            <span class="inline-flex items-center gap-1.5 font-semibold text-slate-600">
                <span class="w-2.5 h-2.5 rounded-xs bg-indigo-600 inline-block"></span> Progres Selesai
            </span>
            <?php if ($jmlOverdue > 0): ?>
            <span class="badge-modern bg-red-50 text-red-700 ring-1 ring-red-200">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= $jmlOverdue ?> Proyek Lewat Estimasi
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- ==========================================================
         KONTAINER GANTT CHART INTERAKTIF
    =========================================================== -->
    <div class="card overflow-hidden">
        <?php if (empty($proyeks)): ?>
        <div class="p-14 text-center text-sm text-slate-400">Tidak ada proyek untuk ditampilkan pada Gantt Chart.</div>
        <?php else: ?>
        <div class="flex overflow-hidden">

            <!-- PANEL KIRI (DAFTAR PROYEK & TIM - STICKY) -->
            <div class="w-64 sm:w-80 flex-shrink-0 border-r border-slate-200 bg-white z-20">
                <!-- Header Kiri (Tinggi disamakan dengan 2 baris header tanggal kanan: 64px) -->
                <div class="h-16 px-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-slate-600">Proyek & Tim</span>
                    <span class="text-[11px] font-semibold text-slate-400">Progres</span>
                </div>

                <!-- Baris-baris Nama Proyek -->
                <div class="divide-y divide-slate-100">
                    <?php foreach ($proyeks as $p):
                        $pid     = (int)$p['id'];
                        $anggota = $timPerProyek[$pid] ?? [];
                        $subJadwal = $jadwalPerProyek[$pid] ?? [];
                        $isOverdue = ($p['status'] !== 'selesai' && strtotime($p['tanggal_selesai']) < $todayTs);
                    ?>
                    <div class="h-16 px-4 flex items-center justify-between gap-2 hover:bg-slate-50/80 transition-colors">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <?php if (!empty($subJadwal)): ?>
                                <button type="button" onclick="toggleSubJadwal(<?= $pid ?>)"
                                        class="w-5 h-5 rounded hover:bg-slate-200 text-slate-500 flex items-center justify-center flex-shrink-0"
                                        title="Tampilkan <?= count($subJadwal) ?> tahapan jadwal">
                                    <i class="bi bi-chevron-right text-[10px] transition-transform" id="iconExpand_<?= $pid ?>"></i>
                                </button>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $pid ?>"
                                   class="text-xs font-bold text-slate-900 hover:text-primary-600 truncate block"
                                   title="<?= sanitize($p['nama_proyek']) ?>">
                                    <?= sanitize($p['nama_proyek']) ?>
                                </a>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[11px] text-slate-400 truncate max-w-[130px]"><?= sanitize($p['nama_pelanggan']) ?></span>
                                <?php if ($isOverdue): ?>
                                <span class="text-[9px] font-extrabold px-1.5 py-0.2 rounded bg-red-50 text-red-600 border border-red-200">Terlambat</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Avatar Tim & Persentase -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <?php if (!empty($anggota)): ?>
                            <div class="hidden sm:flex items-center -space-x-1.5">
                                <?php foreach (array_slice($anggota, 0, 2) as $idx => $agt): ?>
                                <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-[9px] font-bold flex items-center justify-center ring-2 ring-white"
                                      title="<?= sanitize($agt['nama_personel'] . ' (' . $agt['peran_proyek'] . ')') ?>">
                                    <?= strtoupper(substr($agt['nama_personel'], 0, 1)) ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <span class="text-xs font-extrabold text-slate-700 w-9 text-right"><?= (int)$p['progres'] ?>%</span>
                        </div>
                    </div>

                    <!-- Baris Sub-Jadwal (Hidden by default) -->
                    <?php foreach ($subJadwal as $sj): ?>
                    <div class="subjadwal-row-<?= $pid ?> hidden h-10 pl-9 pr-4 bg-slate-50/70 flex items-center justify-between text-[11px] text-slate-600">
                        <span class="truncate"><i class="bi bi-arrow-return-right text-slate-400 mr-1"></i> <?= sanitize($sj['keterangan'] ?: 'Tahap Instalasi') ?></span>
                        <?= statusBadge($sj['status']) ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- PANEL KANAN (AREA TIMELINE SCROLLABLE) -->
            <div class="flex-1 overflow-x-auto relative" id="ganttScrollContainer">
                <div class="relative" style="width: <?= $chartWidth ?>px;">

                    <!-- GARIS VERTIKAL MERAH "HARI INI (TODAY)" -->
                    <?php if ($todayLeftPx > 0): ?>
                    <div id="todayMarkerLine"
                         class="absolute top-0 bottom-0 w-0.5 bg-red-500 z-10 pointer-events-none"
                         style="left: <?= $todayLeftPx ?>px;">
                        <div class="bg-red-500 text-white text-[9px] font-extrabold px-1.5 py-0.5 rounded-b-md shadow-xs whitespace-nowrap -translate-x-1/2">
                            HARI INI
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- HEADER BARIS 1: BULAN -->
                    <div class="flex h-8 border-b border-slate-200 bg-slate-100/80">
                        <?php foreach ($monthsHeader as $m): ?>
                        <div class="border-r border-slate-200 px-2 flex items-center text-[11px] font-extrabold text-slate-700 uppercase tracking-wider"
                             style="width: <?= $m['count'] * $dayWidthPx ?>px;">
                            <?= $m['label'] ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- HEADER BARIS 2: TANGGAL HARIAN -->
                    <div class="flex h-8 border-b border-slate-200 bg-slate-50">
                        <?php foreach ($daysHeader as $dh): ?>
                        <div class="border-r border-slate-100 flex items-center justify-center text-[10px] font-bold flex-shrink-0
                            <?= $dh['isToday'] ? 'bg-red-500 text-white' : ($dh['isWeekend'] ? 'bg-slate-100 text-slate-400' : 'text-slate-600') ?>"
                             style="width: <?= $dayWidthPx ?>px;"
                             title="<?= $dh['date'] ?>">
                            <?= $dh['day'] ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- BARIS-BARIS BAR PROYEK -->
                    <div class="divide-y divide-slate-100 relative">
                        <?php foreach ($proyeks as $p):
                            $pid       = (int)$p['id'];
                            $subJadwal = $jadwalPerProyek[$pid] ?? [];
                            $sTs       = max($minTs, strtotime($p['tanggal_mulai']));
                            $eTs       = min($maxTs, strtotime($p['tanggal_selesai']));

                            $startOffsetDays = max(0, (int)round(($sTs - $minTs) / 86400));
                            $durationDays    = max(1, (int)round(($eTs - $sTs) / 86400) + 1);

                            $barLeftPx  = $startOffsetDays * $dayWidthPx;
                            $barWidthPx = max($dayWidthPx, $durationDays * $dayWidthPx);
                            $clr        = $barColors[$p['status']] ?? $barColors['survei'];
                            $isOverdue  = ($p['status'] !== 'selesai' && strtotime($p['tanggal_selesai']) < $todayTs);
                        ?>
                        <div class="h-16 relative flex items-center hover:bg-slate-50/50"
                             style="background-image: linear-gradient(to right, #f1f5f9 1px, transparent 1px); background-size: <?= $dayWidthPx ?>px 100%;">
                            <!-- Bar Proyek -->
                            <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $pid ?>"
                               class="absolute h-8 rounded-xl border <?= $isOverdue ? 'bg-red-100 border-red-300' : $clr['track'] . ' ' . $clr['border'] ?> overflow-hidden shadow-2xs hover:shadow-md transition-all flex items-center group"
                               style="left: <?= $barLeftPx ?>px; width: <?= $barWidthPx ?>px;"
                               title="<?= sanitize($p['nama_proyek']) ?> (<?= formatTanggalShort($p['tanggal_mulai']) ?> - <?= formatTanggalShort($p['tanggal_selesai']) ?>) · Progres <?= (int)$p['progres'] ?>%">

                                <!-- Fill Progres di Dalam Bar -->
                                <div class="h-full <?= $isOverdue ? 'bg-red-500' : $clr['fill'] ?> transition-all"
                                     style="width: <?= (int)$p['progres'] ?>%;"></div>

                                <!-- Label di Atas Bar -->
                                <span class="absolute inset-0 px-2.5 flex items-center justify-between text-[11px] font-bold text-slate-900 mix-blend-multiply truncate">
                                    <span class="truncate"><?= sanitize($p['nama_proyek']) ?></span>
                                    <span class="ml-1"><?= (int)$p['progres'] ?>%</span>
                                </span>
                            </a>
                        </div>

                        <!-- Baris Bar Sub-Jadwal Instalasi -->
                        <?php foreach ($subJadwal as $sj):
                            $sjStart = max($minTs, strtotime($sj['tanggal_mulai']));
                            $sjEnd   = min($maxTs, strtotime($sj['tanggal_selesai']));
                            $sjOff   = max(0, (int)round(($sjStart - $minTs) / 86400));
                            $sjDur   = max(1, (int)round(($sjEnd - $sjStart) / 86400) + 1);
                            $sjLeft  = $sjOff * $dayWidthPx;
                            $sjWidth = max($dayWidthPx, $sjDur * $dayWidthPx);
                        ?>
                        <div class="subjadwal-row-<?= $pid ?> hidden h-10 relative flex items-center bg-slate-50/40"
                             style="background-image: linear-gradient(to right, #f1f5f9 1px, transparent 1px); background-size: <?= $dayWidthPx ?>px 100%;">
                            <div class="absolute h-5 rounded-md bg-emerald-500/80 text-white text-[10px] font-semibold px-2 flex items-center truncate shadow-2xs"
                                 style="left: <?= $sjLeft ?>px; width: <?= $sjWidth ?>px;"
                                 title="<?= sanitize($sj['keterangan']) ?> (<?= formatTanggalShort($sj['tanggal_mulai']) ?> - <?= formatTanggalShort($sj['tanggal_selesai']) ?>)">
                                <?= sanitize($sj['keterangan'] ?: 'Tahap Instalasi') ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>

        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function scrollToToday() {
    const container = document.getElementById('ganttScrollContainer');
    const marker    = document.getElementById('todayMarkerLine');
    if (container && marker) {
        const leftPos = parseInt(marker.style.left || '0', 10);
        container.scrollTo({
            left: Math.max(0, leftPos - (container.clientWidth / 2)),
            behavior: 'smooth'
        });
    }
}

function toggleSubJadwal(proyekId) {
    const rows = document.querySelectorAll('.subjadwal-row-' + proyekId);
    const icon = document.getElementById('iconExpand_' + proyekId);
    rows.forEach(r => r.classList.toggle('hidden'));
    if (icon) {
        icon.classList.toggle('rotate-90');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Otomatis fokus ke garis Hari Ini saat halaman dibuka
    setTimeout(scrollToToday, 150);
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>