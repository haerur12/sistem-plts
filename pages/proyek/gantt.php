<?php
// pages/proyek/gantt.php
$pageTitle   = 'Gantt Chart Proyek';
$currentPage = 'gantt';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);

$db = db();

// Filter
$filterStatus = $_GET['status'] ?? '';
$filterTahun  = $_GET['tahun']  ?? date('Y');

$where  = ['1=1'];
$params = [];
if ($filterStatus) { $where[] = 'p.status = ?'; $params[] = $filterStatus; }
$where[] = 'YEAR(p.tanggal_mulai) = ? OR YEAR(p.tanggal_selesai) = ?';
$params[] = $filterTahun;
$params[] = $filterTahun;
$whereStr = implode(' AND ', $where);

// Ambil semua proyek
$stmt = $db->prepare("
    SELECT p.*, pl.nama_pelanggan,
    DATEDIFF(p.tanggal_selesai, p.tanggal_mulai) as durasi_hari
    FROM proyek p
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    WHERE $whereStr
    ORDER BY p.tanggal_mulai ASC
");
$stmt->execute($params);
$proyeks = $stmt->fetchAll();

// Statistik
$totalProyek    = count($proyeks);
$totalDurasi    = 0;
$onTimeCount    = 0;
$delayCount     = 0;
foreach ($proyeks as $p) {
    $totalDurasi += $p['durasi_hari'];
    if ($p['status'] === 'selesai' && strtotime($p['tanggal_selesai']) <= strtotime($p['tanggal_selesai'])) $onTimeCount++;
    if ($p['status'] === 'ditunda') $delayCount++;
}
$rataDurasi = $totalProyek > 0 ? round($totalDurasi / $totalProyek) : 0;

// Tentukan range bulan (12 bulan dari Januari - Desember tahun terpilih)
$bulanLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$startDate = new DateTime("$filterTahun-01-01");
$endDate   = new DateTime("$filterTahun-12-31");
$totalDays = $startDate->diff($endDate)->days + 1;

// Warna status
$statusColors = [
    'survei'      => ['bg' => 'bg-slate-400',    'hex' => '#94a3b8', 'label' => 'Survei'],
    'perencanaan' => ['bg' => 'bg-sky-400',      'hex' => '#38bdf8', 'label' => 'Perencanaan'],
    'instalasi'   => ['bg' => 'bg-indigo-500',   'hex' => '#6366f1', 'label' => 'Instalasi'],
    'selesai'     => ['bg' => 'bg-emerald-500',  'hex' => '#10b981', 'label' => 'Selesai'],
    'ditunda'     => ['bg' => 'bg-amber-500',    'hex' => '#f59e0b', 'label' => 'Ditunda'],
];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-start justify-between mb-5 flex-wrap gap-3">
    <div class="flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white shadow-lg shadow-sky-500/25">
            <i class="bi bi-calendar-range text-lg"></i>
        </span>
        <div>
            <h2 class="text-lg font-bold text-slate-900 tracking-tight">Gantt Chart Proyek</h2>
            <p class="text-[13px] text-slate-500 mt-0.5">Timeline visual semua proyek instalasi PLTS tahun <?= sanitize($filterTahun) ?></p>
        </div>
    </div>
    <a href="<?= BASE_URL ?>/pages/proyek/index.php" class="btn-secondary">
        <i class="bi bi-list-ul"></i> Tampilan Tabel
    </a>
</div>

<!-- Filter -->
<div class="card p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3 items-center">
        <div class="flex gap-1 bg-slate-100 p-1 rounded-xl">
            <?php
            $tahunSekarang = (int) date('Y');
            for ($t = $tahunSekarang - 2; $t <= $tahunSekarang + 1; $t++):
            ?>
            <button type="submit" name="tahun" value="<?= $t ?>"
                class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors
                <?= $filterTahun == $t ? 'bg-white text-primary-700 shadow-sm' : 'text-slate-600 hover:text-slate-800' ?>">
                <?= $t ?>
            </button>
            <?php endfor; ?>
        </div>
        <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
            <option value="">Semua Status</option>
            <?php foreach (['survei','perencanaan','instalasi','selesai','ditunda'] as $st): ?>
            <option value="<?= $st ?>" <?= $filterStatus === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary"><i class="bi bi-funnel"></i> Filter</button>
        <?php if ($filterStatus): ?>
        <a href="?tahun=<?= $filterTahun ?>" class="btn-secondary"><i class="bi bi-x-lg"></i> Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="bi bi-diagram-3 text-lg"></i>
        </div>
        <div>
            <p class="text-[11px] text-slate-500 font-medium">Total Proyek</p>
            <p class="text-xl font-bold text-slate-900"><?= $totalProyek ?></p>
        </div>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-sky-50 text-sky-600 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="bi bi-clock-history text-lg"></i>
        </div>
        <div>
            <p class="text-[11px] text-slate-500 font-medium">Rata-rata Durasi</p>
            <p class="text-xl font-bold text-slate-900"><?= $rataDurasi ?> <span class="text-xs font-medium text-slate-400">hari</span></p>
        </div>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="bi bi-check2-all text-lg"></i>
        </div>
        <div>
            <p class="text-[11px] text-slate-500 font-medium">Selesai</p>
            <p class="text-xl font-bold text-slate-900"><?= (int)($statusColors['selesai'] ?? 0) ?>
                <?php
                $selesaiCount = 0;
                foreach ($proyeks as $p) if ($p['status'] === 'selesai') $selesaiCount++;
                ?>
                <?= $selesaiCount ?>
            </p>
        </div>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="bi bi-hourglass-split text-lg"></i>
        </div>
        <div>
            <p class="text-[11px] text-slate-500 font-medium">Ditunda</p>
            <p class="text-xl font-bold text-slate-900"><?= $delayCount ?></p>
        </div>
    </div>
</div>

<!-- Gantt Chart -->
<div class="card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-3">
        <h3 class="text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2">
            <i class="bi bi-calendar3 text-primary-600"></i>
            Timeline <?= $filterTahun ?>
        </h3>
        <div class="flex items-center gap-4 text-xs">
            <?php foreach ($statusColors as $key => $c): ?>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded <?= $c['bg'] ?>"></span>
                <span class="text-slate-600"><?= $c['label'] ?></span>
            </span>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($proyeks)): ?>
    <div class="px-5 py-20 text-center">
        <span class="empty-icon mb-3 !w-16 !h-16"><i class="bi bi-calendar-x"></i></span>
        <p class="text-slate-400 text-sm">Tidak ada proyek pada tahun <?= sanitize($filterTahun) ?></p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <div class="min-w-[900px]">
            <!-- Header Timeline -->
            <div class="flex border-b border-slate-200 bg-slate-50/60 sticky top-0">
                <div class="w-72 flex-shrink-0 px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-r border-slate-200">
                    Proyek
                </div>
                <div class="flex-1 grid grid-cols-12">
                    <?php foreach ($bulanLabels as $i => $bl):
                        $isCurrentMonth = ((int)date('n') - 1) === $i && (int)date('Y') === (int)$filterTahun;
                    ?>
                    <div class="text-xs text-center py-3 border-r border-slate-100 last:border-r-0
                        <?= $isCurrentMonth ? 'bg-primary-50 text-primary-700 font-bold' : 'text-slate-500' ?>">
                        <?= $bl ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Rows -->
            <div class="divide-y divide-slate-100">
                <?php
                $today = new DateTime();
                $todayOffset = 0;
                if ($today >= $startDate && $today <= $endDate) {
                    $todayOffset = $startDate->diff($today)->days;
                }
                ?>
                <?php foreach ($proyeks as $p):
                    $pStart = new DateTime($p['tanggal_mulai']);
                    $pEnd   = new DateTime($p['tanggal_selesai']);

                    // Clamp ke range tahun
                    if ($pStart < $startDate) $pStart = clone $startDate;
                    if ($pEnd   > $endDate)   $pEnd   = clone $endDate;

                    $startOffset = $startDate->diff($pStart)->days;
                    $endOffset   = $startDate->diff($pEnd)->days;

                    $leftPct  = ($startOffset / $totalDays) * 100;
                    $widthPct = (($endOffset - $startOffset + 1) / $totalDays) * 100;

                    $color = $statusColors[$p['status']] ?? $statusColors['survei'];

                    $isOverdue = $p['status'] !== 'selesai' && new DateTime($p['tanggal_selesai']) < new DateTime();
                ?>
                <div class="flex items-center group hover:bg-primary-50/30 transition-colors">
                    <!-- Info Proyek -->
                    <div class="w-72 flex-shrink-0 px-5 py-3.5 border-r border-slate-100">
                        <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $p['id'] ?>"
                            class="text-sm font-semibold text-slate-800 hover:text-primary-600 transition-colors block truncate">
                            <?= sanitize($p['nama_proyek']) ?>
                        </a>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-slate-400 truncate"><?= sanitize($p['nama_pelanggan']) ?></span>
                            <span class="text-slate-300">·</span>
                            <span class="text-xs text-slate-500 font-medium"><?= $p['durasi_hari'] ?> hari</span>
                            <?php if ($isOverdue): ?>
                            <span class="inline-flex items-center gap-0.5 text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded">
                                <i class="bi bi-exclamation-triangle-fill"></i> OVERDUE
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Bar Container -->
                    <div class="flex-1 relative h-14 py-2">
                        <!-- Grid lines -->
                        <div class="absolute inset-0 grid grid-cols-12 pointer-events-none">
                            <?php for ($i = 0; $i < 12; $i++): ?>
                            <div class="border-r border-slate-100 last:border-r-0"></div>
                            <?php endfor; ?>
                        </div>

                        <!-- Today marker -->
                        <?php if ($today >= $startDate && $today <= $endDate): ?>
                        <div class="absolute top-0 bottom-0 w-px bg-red-400 z-10 pointer-events-none"
                            style="left: <?= ($todayOffset / $totalDays) * 100 ?>%">
                            <div class="absolute -top-0.5 left-1/2 -translate-x-1/2 w-2 h-2 rounded-full bg-red-500 ring-2 ring-white"></div>
                        </div>
                        <?php endif; ?>

                        <!-- Bar -->
                        <div class="absolute top-2 bottom-2 rounded-md <?= $color['bg'] ?> shadow-sm hover:shadow-md transition-all cursor-pointer group/bar overflow-hidden"
                            style="left: <?= $leftPct ?>%; width: <?= max($widthPct, 0.5) ?>%;"
                            onclick="window.location='<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $p['id'] ?>'">
                            <!-- Progress fill -->
                            <div class="absolute inset-y-0 left-0 bg-white/25 transition-all" style="width: <?= $p['progres'] ?>%"></div>
                            <!-- Label -->
                            <?php if ($widthPct > 6): ?>
                            <div class="absolute inset-0 flex items-center px-2 text-white text-[11px] font-semibold truncate">
                                <span class="truncate drop-shadow-sm"><?= $p['progres'] ?>%</span>
                            </div>
                            <?php endif; ?>
                            <!-- Tooltip -->
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/bar:block z-20 pointer-events-none">
                                <div class="bg-slate-900 text-white text-xs rounded-lg px-3 py-2 shadow-xl whitespace-nowrap">
                                    <p class="font-bold mb-1"><?= sanitize($p['nama_proyek']) ?></p>
                                    <p class="text-slate-300"><?= formatTanggal($p['tanggal_mulai']) ?> — <?= formatTanggal($p['tanggal_selesai']) ?></p>
                                    <p class="text-slate-300">Progres: <span class="text-white font-semibold"><?= $p['progres'] ?>%</span></p>
                                    <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-slate-900 rotate-45 -mt-1"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer info -->
    <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between text-xs text-slate-500">
        <div class="flex items-center gap-4">
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                Garis merah = Hari ini
            </span>
            <span class="flex items-center gap-1.5">
                <i class="bi bi-info-circle"></i>
                Klik bar untuk melihat detail proyek
            </span>
        </div>
        <span><?= count($proyeks) ?> proyek ditampilkan</span>
    </div>
</div>

<!-- Detail per Status -->
<?php if (!empty($proyeks)): ?>
<div class="grid grid-cols-1 md:grid-cols-5 gap-3 mt-4">
    <?php foreach ($statusColors as $key => $c):
        $count = 0;
        foreach ($proyeks as $p) if ($p['status'] === $key) $count++;
        $pct = $totalProyek > 0 ? round(($count / $totalProyek) * 100) : 0;
    ?>
    <div class="card p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                <span class="w-2.5 h-2.5 rounded-full <?= $c['bg'] ?>"></span>
                <?= $c['label'] ?>
            </span>
            <span class="text-[11px] text-slate-400"><?= $pct ?>%</span>
        </div>
        <p class="text-2xl font-bold text-slate-900"><?= $count ?></p>
         <div class="mt-2 h-1.5 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full <?= $c['bg'] ?> rounded-full transition-all" style="width: <?= $pct ?>%"></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>