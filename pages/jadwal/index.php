<?php
// pages/jadwal/index.php — KALENDAR INTERAKTIF PREMIUM
$pageTitle   = 'Kalender Jadwal';
$currentPage = 'jadwal';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);

$db = db();

// Ambil semua jadwal dengan data lengkap
$stmt = $db->query("
    SELECT j.*, p.nama_proyek, p.id as proyek_id, p.progres, p.status as status_proyek,
           pl.nama_pelanggan,
           DATEDIFF(j.tanggal_selesai, j.tanggal_mulai) as durasi_hari
    FROM jadwal j
    JOIN proyek p ON j.proyek_id = p.id
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    ORDER BY j.tanggal_mulai ASC
");
$jadwals = $stmt->fetchAll();

// Format untuk FullCalendar dengan custom rendering
$events = [];
$statusMeta = [
    'terjadwal' => ['color' => '#64748b', 'bg'    => 'bg-slate-100',    'text' => 'text-slate-700',    'label' => 'Terjadwal', 'icon' => 'bi-clock'],
    'berjalan'  => ['color' => '#3b82f6', 'bg'    => 'bg-blue-100',     'text' => 'text-blue-700',     'label' => 'Berjalan',  'icon' => 'bi-play-circle'],
    'selesai'   => ['color' => '#10b981', 'bg'    => 'bg-emerald-100',  'text' => 'text-emerald-700',  'label' => 'Selesai',   'icon' => 'bi-check-circle'],
    'ditunda'   => ['color' => '#f59e0b', 'bg'    => 'bg-amber-100',    'text' => 'text-amber-700',    'label' => 'Ditunda',   'icon' => 'bi-pause-circle'],
];

foreach ($jadwals as $j) {
    $meta = $statusMeta[$j['status']] ?? $statusMeta['terjadwal'];
    $events[] = [
        'id'            => $j['id'],
        'title'         => $j['nama_proyek'],
        'start'         => $j['tanggal_mulai'],
        'end'           => date('Y-m-d', strtotime($j['tanggal_selesai'] . ' +1 day')),
        'color'         => $meta['color'],
        'status'        => $j['status'],
        'statusLabel'   => $meta['label'],
        'statusIcon'    => $meta['icon'],
        'keterangan'    => $j['keterangan'] ?? '-',
        'pelanggan'     => $j['nama_pelanggan'],
        'proyek_id'     => $j['proyek_id'],
        'durasi_hari'   => $j['durasi_hari'],
        'progres'       => (int)$j['progres'],
        'extendedProps' => [
            'status'       => $j['status'],
            'statusLabel'  => $meta['label'],
            'statusIcon'   => $meta['icon'],
            'statusColor'  => $meta['color'],
            'keterangan'   => $j['keterangan'] ?? '-',
            'pelanggan'    => $j['nama_pelanggan'],
            'proyek_id'    => $j['proyek_id'],
            'durasi_hari'  => $j['durasi_hari'],
            'progres'      => (int)$j['progres'],
        ],
    ];
}

// Statistik
$totalJadwal = count($jadwals);
$berjalan    = count(array_filter($jadwals, fn($j) => $j['status'] === 'berjalan'));
$terjadwal   = count(array_filter($jadwals, fn($j) => $j['status'] === 'terjadwal'));
$selesai     = count(array_filter($jadwals, fn($j) => $j['status'] === 'selesai'));
$ditunda     = count(array_filter($jadwals, fn($j) => $j['status'] === 'ditunda'));

// Upcoming events (7 hari ke depan)
$today = date('Y-m-d');
$nextWeek = date('Y-m-d', strtotime('+7 days'));
$upcoming = array_filter($jadwals, function($j) use ($today, $nextWeek) {
    return $j['tanggal_mulai'] <= $nextWeek && $j['tanggal_selesai'] >= $today && $j['status'] !== 'selesai';
});
usort($upcoming, fn($a, $b) => strtotime($a['tanggal_mulai']) - strtotime($b['tanggal_mulai']));
$upcoming = array_slice($upcoming, 0, 5);

// Pelanggan untuk filter
$pelangganList = $db->query("SELECT id, nama_pelanggan FROM pelanggan ORDER BY nama_pelanggan")->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<!-- FullCalendar -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.9/index.global.min.js"></script>

<!-- Breadcrumb -->
<nav class="breadcrumb mb-3">
    <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
    <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
    <a href="<?= BASE_URL ?>/pages/proyek/index.php">Proyek</a>
    <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
    <span class="current">Kalender Jadwal</span>
</nav>

<!-- Header Premium -->
<div class="flex items-start justify-between mb-6 flex-wrap gap-4">
    <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
            <i class="bi bi-calendar3 text-2xl"></i>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Kalender Jadwal Instalasi</h2>
            <p class="text-sm text-slate-500 mt-0.5">Kelola timeline proyek dengan visual interaktif</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>/pages/proyek/gantt.php" class="btn-secondary">
            <i class="bi bi-bar-chart-steps"></i>
            <span class="hidden sm:inline">Gantt Chart</span>
        </a>
        <a href="<?= BASE_URL ?>/pages/jadwal/create.php" class="btn-primary">
            <i class="bi bi-calendar-plus"></i>
            <span class="hidden sm:inline">Tambah Jadwal</span>
        </a>
    </div>
</div>

<!-- Stats Cards Premium -->
<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
    <?php
    $statsCards = [
        ['val' => $totalJadwal, 'label' => 'Total Jadwal',  'icon' => 'bi-calendar3',        'gradient' => 'from-slate-500 to-slate-700',    'light' => 'bg-slate-50'],
        ['val' => $berjalan,    'label' => 'Sedang Berjalan','icon' => 'bi-play-circle-fill',  'gradient' => 'from-blue-500 to-blue-700',      'light' => 'bg-blue-50'],
        ['val' => $terjadwal,   'label' => 'Terjadwal',     'icon' => 'bi-clock-fill',        'gradient' => 'from-indigo-500 to-indigo-700',  'light' => 'bg-indigo-50'],
        ['val' => $selesai,     'label' => 'Selesai',       'icon' => 'bi-check-circle-fill', 'gradient' => 'from-emerald-500 to-emerald-700','light' => 'bg-emerald-50'],
        ['val' => $ditunda,     'label' => 'Ditunda',       'icon' => 'bi-pause-circle-fill', 'gradient' => 'from-amber-500 to-amber-700',    'light' => 'bg-amber-50'],
    ];
    foreach ($statsCards as $i => $s):
    ?>
    <div class="card p-4 relative overflow-hidden group hover:shadow-lg transition-all">
        <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-gradient-to-br <?= $s['gradient'] ?> opacity-10 group-hover:opacity-20 transition-opacity"></div>
        <div class="relative flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider"><?= $s['label'] ?></p>
                <p class="text-3xl font-extrabold text-slate-900 mt-1 tracking-tight"><?= $s['val'] ?></p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br <?= $s['gradient'] ?> flex items-center justify-center text-white shadow-md">
                <i class="bi <?= $s['icon'] ?>"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter Bar Modern -->
<div class="card p-4 mb-5">
    <div class="flex flex-wrap items-center gap-3">
        <!-- Search -->
        <div class="relative flex-1 min-w-[200px]">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" id="calendarSearch" placeholder="Cari proyek atau pelanggan..."
                class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
        </div>

        <!-- Filter Status -->
        <div class="flex gap-1 bg-slate-100 p-1 rounded-xl">
            <button type="button" data-filter-status="" class="status-filter px-3 py-1.5 text-xs font-semibold rounded-lg transition-all bg-white text-slate-900 shadow-sm">
                Semua
            </button>
            <?php foreach ($statusMeta as $key => $meta): ?>
            <button type="button" data-filter-status="<?= $key ?>"
                class="status-filter px-3 py-1.5 text-xs font-semibold rounded-lg transition-all text-slate-600 hover:text-slate-900">
                <i class="bi <?= $meta['icon'] ?> mr-1" style="color: <?= $meta['color'] ?>"></i>
                <?= $meta['label'] ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- View Switcher -->
        <div class="flex gap-1 bg-slate-100 p-1 rounded-xl">
            <button type="button" data-view="dayGridMonth" class="view-switch px-3 py-1.5 text-xs font-semibold rounded-lg transition-all bg-white text-slate-900 shadow-sm" title="Bulan (M)">
                <i class="bi bi-grid-3x3-gap mr-1"></i>Bulan
            </button>
            <button type="button" data-view="timeGridWeek" class="view-switch px-3 py-1.5 text-xs font-semibold rounded-lg transition-all text-slate-600 hover:text-slate-900" title="Minggu (W)">
                <i class="bi bi-layout-three-columns mr-1"></i>Minggu
            </button>
            <button type="button" data-view="listWeek" class="view-switch px-3 py-1.5 text-xs font-semibold rounded-lg transition-all text-slate-600 hover:text-slate-900" title="Agenda (L)">
                <i class="bi bi-list-ul mr-1"></i>Agenda
            </button>
        </div>
    </div>
</div>

<!-- Main Layout: Calendar + Side Panel -->
<div class="grid grid-cols-1 xl:grid-cols-4 gap-5">
    <!-- Calendar -->
    <div class="xl:col-span-3">
        <div class="card p-5">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Side Panel: Upcoming Events -->
    <div class="xl:col-span-1">
        <div class="card overflow-hidden sticky top-4">
            <div class="px-5 py-4 bg-gradient-to-r from-indigo-500 to-purple-600 text-white">
                <div class="flex items-center gap-2">
                    <i class="bi bi-rocket-takeoff text-lg"></i>
                    <h3 class="text-sm font-bold">Akan Datang</h3>
                </div>
                <p class="text-xs opacity-80 mt-1">7 hari ke depan</p>
            </div>

            <?php if (empty($upcoming)): ?>
            <div class="px-5 py-10 text-center">
                <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <i class="bi bi-calendar-check text-2xl text-slate-400"></i>
                </div>
                <p class="text-sm text-slate-500 font-medium">Tidak ada jadwal</p>
                <p class="text-xs text-slate-400 mt-1">dalam 7 hari ke depan</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-slate-100 max-h-[600px] overflow-y-auto">
                <?php foreach ($upcoming as $u):
                    $meta = $statusMeta[$u['status']] ?? $statusMeta['terjadwal'];
                    $start = new DateTime($u['tanggal_mulai']);
                    $isToday = $start->format('Y-m-d') === date('Y-m-d');
                    $isTomorrow = $start->format('Y-m-d') === date('Y-m-d', strtotime('+1 day'));
                ?>
                <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $u['proyek_id'] ?>"
                    class="block px-5 py-3.5 hover:bg-slate-50 transition-colors group">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 text-center">
                            <div class="text-xs font-bold text-slate-400 uppercase"><?= date('M', strtotime($u['tanggal_mulai'])) ?></div>
                            <div class="text-xl font-extrabold <?= $isToday ? 'text-indigo-600' : 'text-slate-800' ?> leading-none">
                                <?= date('d', strtotime($u['tanggal_mulai'])) ?>
                            </div>
                            <?php if ($isToday): ?>
                            <div class="text-[9px] font-bold text-indigo-600 mt-0.5">HARI INI</div>
                            <?php elseif ($isTomorrow): ?>
                            <div class="text-[9px] font-bold text-amber-600 mt-0.5">BESOK</div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate group-hover:text-indigo-600 transition-colors">
                                <?= sanitize($u['nama_proyek']) ?>
                            </p>
                            <p class="text-xs text-slate-400 truncate mt-0.5">
                                <i class="bi bi-person mr-1"></i><?= sanitize($u['nama_pelanggan']) ?>
                            </p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                    style="background: <?= $meta['color'] ?>20; color: <?= $meta['color'] ?>">
                                    <i class="bi <?= $meta['icon'] ?>"></i>
                                    <?= $meta['label'] ?>
                                </span>
                                <span class="text-[10px] text-slate-400">
                                    <?= $u['durasi_hari'] ?> hari
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Keyboard Shortcuts -->
            <div class="px-5 py-3 bg-slate-50 border-t border-slate-100">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Keyboard Shortcuts</p>
                <div class="grid grid-cols-2 gap-1.5 text-xs">
                    <div class="flex items-center gap-2">
                        <kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-[10px] font-mono font-bold text-slate-600">T</kbd>
                        <span class="text-slate-500">Hari ini</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-[10px] font-mono font-bold text-slate-600">M</kbd>
                        <span class="text-slate-500">Bulan</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-[10px] font-mono font-bold text-slate-600">W</kbd>
                        <span class="text-slate-500">Minggu</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-[10px] font-mono font-bold text-slate-600">N</kbd>
                        <span class="text-slate-500">Baru</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Event Premium -->
<div id="modalEvent" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden" onclick="closeEventModal()">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all" onclick="event.stopPropagation()" id="modalContent">
        <!-- Header dengan gradient dinamis -->
        <div id="eventHeader" class="px-6 py-5 text-white relative overflow-hidden" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 50%, white 1px, transparent 1px); background-size: 20px 20px;"></div>
            <div class="relative flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0 flex-1">
                    <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-calendar-event text-xl" id="eventIcon"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 bg-white/20 backdrop-blur rounded-full text-[10px] font-bold uppercase tracking-wider" id="eventStatus">Status</span>
                            <span class="text-xs opacity-80" id="eventDurasi">-</span>
                        </div>
                        <h3 class="text-lg font-bold truncate" id="eventTitle">Judul</h3>
                    </div>
                </div>
                <button onclick="closeEventModal()" class="p-2 rounded-xl bg-white/20 hover:bg-white/30 transition-colors flex-shrink-0">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Progress bar di header -->
            <div class="relative mt-4">
                <div class="flex justify-between text-xs mb-1.5">
                    <span class="opacity-80">Progress Proyek</span>
                    <span class="font-bold" id="eventProgresLabel">0%</span>
                </div>
                <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                    <div class="h-full bg-white rounded-full transition-all" id="eventProgresBar" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-4">
            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-3">
                <div class="p-3 bg-slate-50 rounded-xl">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="bi bi-person text-slate-400 text-sm"></i>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Pelanggan</span>
                    </div>
                    <p class="text-sm font-semibold text-slate-800 truncate" id="eventPelanggan">-</p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="bi bi-calendar-event text-slate-400 text-sm"></i>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Periode</span>
                    </div>
                    <p class="text-sm font-semibold text-slate-800" id="eventPeriode">-</p>
                </div>
            </div>

            <!-- Keterangan -->
            <div class="p-3 bg-slate-50 rounded-xl">
                <div class="flex items-center gap-2 mb-1.5">
                    <i class="bi bi-card-text text-slate-400 text-sm"></i>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Keterangan</span>
                </div>
                <p class="text-sm text-slate-700 leading-relaxed" id="eventKeterangan">-</p>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex gap-2">
            <a href="#" id="eventProyekLink" class="flex-1 btn-secondary justify-center text-xs">
                <i class="bi bi-eye"></i> Lihat Proyek
            </a>
            <a href="#" id="eventEditLink" class="flex-1 btn-primary justify-center text-xs">
                <i class="bi bi-pencil"></i> Edit Jadwal
            </a>
        </div>
    </div>
</div>

<!-- FAB Quick Create -->
<a href="<?= BASE_URL ?>/pages/jadwal/create.php"
    class="fixed bottom-8 right-8 z-40 w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white rounded-2xl shadow-2xl shadow-indigo-500/40 flex items-center justify-center transition-all hover:scale-110 hover:rotate-90 group"
    title="Tambah Jadwal Baru (N)">
    <i class="bi bi-plus-lg text-2xl"></i>
    <span class="absolute right-full mr-3 px-3 py-1.5 bg-slate-900 text-white text-xs font-semibold rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
        Tambah Jadwal
    </span>
</a>

<style>
/* ====== CALENDAR PREMIUM STYLING ====== */
.fc {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
}

.fc .fc-toolbar {
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem !important;
}

.fc .fc-toolbar-title {
    font-size: 1.25rem !important;
    font-weight: 800 !important;
    color: #1e293b;
    letter-spacing: -0.02em;
}

html.dark .fc .fc-toolbar-title {
    color: #f1f5f9 !important;
}

.fc .fc-button {
    background: #fff !important;
    border: 1.5px solid #e2e8f0 !important;
    color: #475569 !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    padding: 0.5rem 1rem !important;
    text-transform: capitalize !important;
    box-shadow: none !important;
    border-radius: 10px !important;
    transition: all 0.2s !important;
}

html.dark .fc .fc-button {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}

.fc .fc-button:hover {
    background: #f8fafc !important;
    border-color: #cbd5e1 !important;
    transform: translateY(-1px);
}

html.dark .fc .fc-button:hover {
    background: #334155 !important;
}

.fc .fc-button-primary:not(:disabled).fc-button-active {
    background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
    border-color: #6366f1 !important;
    color: #fff !important;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3) !important;
}

.fc .fc-button-primary:disabled {
    opacity: 0.5 !important;
}

.fc .fc-today-button {
    background: #f1f5f9 !important;
    border-color: #e2e8f0 !important;
    color: #475569 !important;
    font-weight: 700 !important;
}

.fc .fc-today-button:hover {
    background: #e2e8f0 !important;
}

.fc .fc-today-button:disabled {
    opacity: 0.4 !important;
}

.fc .fc-col-header-cell {
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 0.75rem 0;
    border-bottom: 2px solid #e2e8f0 !important;
}

html.dark .fc .fc-col-header-cell {
    background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
    border-color: #334155 !important;
}

.fc .fc-col-header-cell-cushion {
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    text-decoration: none;
}

.fc .fc-daygrid-day-number {
    color: #475569;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 0.5rem;
    text-decoration: none;
}

html.dark .fc .fc-daygrid-day-number {
    color: #cbd5e1;
}

.fc .fc-day-today {
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%) !important;
}

html.dark .fc .fc-day-today {
    background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(99,102,241,0.05)) !important;
}

.fc .fc-day-today .fc-daygrid-day-number {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white !important;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    margin: 4px;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
}

.fc .fc-event {
    border: none !important;
    border-radius: 8px !important;
    padding: 3px 8px !important;
    font-size: 0.72rem !important;
    font-weight: 600 !important;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.22, 1, 0.36, 1);
    margin: 1px 0 !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.fc .fc-event:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
    z-index: 10;
}

.fc .fc-event-title {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.fc .fc-daygrid-event-dot {
    display: none;
}

.fc td, .fc th {
    border-color: #f1f5f9 !important;
}

html.dark .fc td, html.dark .fc th {
    border-color: #334155 !important;
}

.fc .fc-scrollgrid {
    border-color: #e2e8f0 !important;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

html.dark .fc .fc-scrollgrid {
    border-color: #334155 !important;
}

.fc .fc-daygrid-day-frame {
    padding: 2px;
    min-height: 90px;
}

.fc .fc-day-other .fc-daygrid-day-number {
    opacity: 0.3;
}

.fc .fc-day-other {
    background: #fafafa !important;
}

html.dark .fc .fc-day-other {
    background: #0f172a !important;
}

/* Event custom content */
.event-custom {
    display: flex;
    flex-direction: column;
    gap: 2px;
    width: 100%;
}

.event-custom-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 4px;
}

.event-custom-title {
    font-weight: 700;
    font-size: 0.72rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}

.event-custom-pelanggan {
    font-size: 0.65rem;
    opacity: 0.85;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.event-custom-progress {
    height: 3px;
    background: rgba(255,255,255,0.3);
    border-radius: 9999px;
    overflow: hidden;
    margin-top: 2px;
}

.event-custom-progress-bar {
    height: 100%;
    background: rgba(255,255,255,0.9);
    border-radius: 9999px;
    transition: width 0.6s ease;
}

/* Modal animation */
#modalEvent.hidden #modalContent {
    transform: scale(0.95);
    opacity: 0;
}

#modalContent {
    animation: modalIn 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

@keyframes modalIn {
    from { transform: scale(0.95) translateY(10px); opacity: 0; }
    to { transform: scale(1) translateY(0); opacity: 1; }
}

/* Filter status button */
.status-filter.active {
    background: white !important;
    color: #0f172a !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

html.dark .status-filter.active {
    background: #334155 !important;
    color: #f1f5f9 !important;
}

/* View switch button */
.view-switch.active {
    background: white !important;
    color: #0f172a !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

html.dark .view-switch.active {
    background: #334155 !important;
    color: #f1f5f9 !important;
}

/* Responsive */
@media (max-width: 768px) {
    .fc .fc-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    .fc .fc-toolbar-title {
        text-align: center;
    }
    .fc .fc-header-toolbar > .fc-toolbar-chunk {
        display: flex;
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const allEvents = <?= json_encode($events) ?>;

    // Restore saved view
    const savedView = localStorage.getItem('calendar_view') || 'dayGridMonth';

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: savedView,
        locale: 'id',
        firstDay: 1,
        height: 750,
        fixedWeekCount: false,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        buttonText: {
            today: 'Hari Ini',
            month: 'Bulan',
            week: 'Minggu',
            list: 'Agenda'
        },
        events: allEvents,
        editable: true,
        eventStartEditable: true,
        eventDurationEditable: true,
        dayMaxEvents: 3,
        moreLinkText: (n) => `+${n} lagi`,
        noEventsText: 'Tidak ada jadwal untuk ditampilkan',

        // Custom event rendering
        eventContent: function(arg) {
            const props = arg.event.extendedProps;
            const progres = props.progres || 0;
            const pelanggan = props.pelanggan || '';

            const html = `
                <div class="event-custom">
                    <div class="event-custom-top">
                        <i class="bi ${props.statusIcon || 'bi-calendar'}" style="font-size: 0.7rem;"></i>
                        <span class="event-custom-title">${arg.event.title}</span>
                        <span style="font-size: 0.65rem; opacity: 0.8;">${progres}%</span>
                    </div>
                    ${pelanggan ? `<div class="event-custom-pelanggan"><i class="bi bi-person-fill" style="font-size: 0.6rem;"></i> ${pelanggan}</div>` : ''}
                    <div class="event-custom-progress">
                        <div class="event-custom-progress-bar" style="width: ${progres}%"></div>
                    </div>
                </div>
            `;
            return { html: html };
        },

        eventClick: function(info) {
            showEventModal(info.event);
        },

        eventDrop: function(info) {
            const newStart = info.event.start.toISOString().split('T')[0];
            const newEnd = info.event.end
                ? new Date(info.event.end.getTime() - 86400000).toISOString().split('T')[0]
                : newStart;

            if (!confirm(`Reschedule "${info.event.title}"?\nMulai: ${newStart}\nSelesai: ${newEnd}`)) {
                info.revert();
                return;
            }

            fetch('<?= BASE_URL ?>/api/jadwal/reschedule.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    id: info.event.id,
                    tanggal_mulai: newStart,
                    tanggal_selesai: newEnd
                })
            }).then(r => r.json()).then(data => {
                if (!data.success) {
                    Toast?.show('Gagal reschedule: ' + (data.message || 'Unknown error'), 'error');
                    info.revert();
                } else {
                    Toast?.show('Jadwal berhasil direschedule', 'success');
                }
            }).catch(() => {
                Toast?.show('Terjadi kesalahan', 'error');
                info.revert();
            });
        },

        datesSet: function() {
            // Save current view
            localStorage.setItem('calendar_view', calendar.view.type);
            updateViewButtons(calendar.view.type);
        }
    });

    calendar.render();

    // ====== VIEW SWITCHER ======
    document.querySelectorAll('.view-switch').forEach(btn => {
        btn.addEventListener('click', () => {
            const view = btn.dataset.view;
            calendar.changeView(view);
            localStorage.setItem('calendar_view', view);
            updateViewButtons(view);
        });
    });

    function updateViewButtons(currentView) {
        document.querySelectorAll('.view-switch').forEach(b => {
            b.classList.toggle('active', b.dataset.view === currentView);
        });
    }
    updateViewButtons(savedView);

    // ====== STATUS FILTER ======
    let currentFilter = '';
    document.querySelectorAll('.status-filter').forEach(btn => {
        btn.addEventListener('click', () => {
            currentFilter = btn.dataset.filterStatus;
            document.querySelectorAll('.status-filter').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyFilters();
        });
    });
    document.querySelector('.status-filter[data-filter-status=""]').classList.add('active');

    // ====== SEARCH ======
    const searchInput = document.getElementById('calendarSearch');
    let searchTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applyFilters, 300);
    });

    function applyFilters() {
        const search = searchInput.value.toLowerCase().trim();
        const filtered = allEvents.filter(ev => {
            const matchStatus = !currentFilter || ev.status === currentFilter;
            const matchSearch = !search ||
                ev.title.toLowerCase().includes(search) ||
                (ev.extendedProps.pelanggan || '').toLowerCase().includes(search);
            return matchStatus && matchSearch;
        });
        calendar.removeAllEvents();
        calendar.addEventSource(filtered);
    }

    // ====== KEYBOARD SHORTCUTS ======
    document.addEventListener('keydown', e => {
        // Skip if typing in input
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;
        // Skip if modal open
        if (!document.getElementById('modalEvent').classList.contains('hidden')) {
            if (e.key === 'Escape') closeEventModal();
            return;
        }

        switch(e.key.toLowerCase()) {
            case 't':
                calendar.today();
                Toast?.show('Kembali ke hari ini', 'info');
                break;
            case 'm':
                calendar.changeView('dayGridMonth');
                break;
            case 'w':
                calendar.changeView('timeGridWeek');
                break;
            case 'l':
                calendar.changeView('listWeek');
                break;
            case 'n':
                window.location = '<?= BASE_URL ?>/pages/jadwal/create.php';
                break;
            case 'arrowleft':
                calendar.prev();
                break;
            case 'arrowright':
                calendar.next();
                break;
        }
    });

    // Store calendar reference globally for shortcuts
    window.pltsCalendar = calendar;
});

// ====== MODAL FUNCTIONS ======
function showEventModal(event) {
    const props = event.extendedProps;
    const color = props.statusColor || '#6366f1';

    document.getElementById('eventTitle').textContent = event.title;
    document.getElementById('eventStatus').textContent = props.statusLabel || props.status;
    document.getElementById('eventIcon').className = 'bi ' + (props.statusIcon || 'bi-calendar-event') + ' text-xl';
    document.getElementById('eventPelanggan').textContent = props.pelanggan || '-';
    document.getElementById('eventKeterangan').textContent = props.keterangan || '-';
    document.getElementById('eventDurasi').textContent = (props.durasi_hari || 0) + ' hari';

    const start = event.start.toISOString().split('T')[0];
    const end = event.end ? new Date(event.end.getTime() - 86400000).toISOString().split('T')[0] : start;
    document.getElementById('eventPeriode').textContent = `${formatTgl(start)} — ${formatTgl(end)}`;

    // Progress
    const progres = props.progres || 0;
    document.getElementById('eventProgresLabel').textContent = progres + '%';
    document.getElementById('eventProgresBar').style.width = progres + '%';

    // Links
    document.getElementById('eventProyekLink').href = '<?= BASE_URL ?>/pages/proyek/show.php?id=' + props.proyek_id;
    document.getElementById('eventEditLink').href = '<?= BASE_URL ?>/pages/jadwal/edit.php?id=' + event.id;

    // Dynamic gradient
    document.getElementById('eventHeader').style.background = `linear-gradient(135deg, ${color}, ${adjustColor(color, -30)})`;

    // Show modal
    document.getElementById('modalEvent').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeEventModal() {
    document.getElementById('modalEvent').classList.add('hidden');
    document.body.style.overflow = '';
}

function formatTgl(tgl) {
    const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const d = new Date(tgl);
    return `${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
}

// Adjust color brightness
function adjustColor(hex, percent) {
    const num = parseInt(hex.replace('#', ''), 16);
    const amt = Math.round(2.55 * percent);
    const R = Math.max(0, Math.min(255, (num >> 16) + amt));
    const G = Math.max(0, Math.min(255, (num >> 8 & 0x00FF) + amt));
    const B = Math.max(0, Math.min(255, (num & 0x0000FF) + amt));
    return '#' + (0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1);
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>