<?php
// pages/jadwal/index.php — KALENDER & MANAJEMEN JADWAL INSTALASI V2
$pageTitle   = 'Jadwal Instalasi';
$currentPage = 'jadwal';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'manajer_proyek']);

$db = db();

// Ambil semua jadwal dengan data lengkap proyek & pelanggan
$stmt = $db->query("
    SELECT j.*, p.nama_proyek, p.id as proyek_id, p.progres, p.status as status_proyek,
           pl.nama_pelanggan,
           DATEDIFF(j.tanggal_selesai, j.tanggal_mulai) + 1 as durasi_hari
    FROM jadwal j
    JOIN proyek p ON j.proyek_id = p.id
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    ORDER BY j.tanggal_mulai ASC
");
$jadwals = $stmt->fetchAll();

// Daftar proyek aktif untuk modal Quick-Create Jadwal
$proyeksAktif = $db->query("
    SELECT p.id, p.nama_proyek, pl.nama_pelanggan 
    FROM proyek p 
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    WHERE p.status IN ('survei','perencanaan','instalasi') 
    ORDER BY p.nama_proyek ASC
")->fetchAll();

$statusMeta = [
    'terjadwal' => [
        'color' => '#64748b',
        'bg'    => '#f1f5f9',
        'border'=> '#cbd5e1',
        'text'  => '#334155',
        'badge' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'label' => 'Terjadwal',
        'icon'  => 'bi-clock'
    ],
    'berjalan'  => [
        'color' => '#4f46e5',
        'bg'    => '#eef2ff',
        'border'=> '#a5b4fc',
        'text'  => '#3730a3',
        'badge' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'label' => 'Berjalan',
        'icon'  => 'bi-play-circle-fill'
    ],
    'selesai'   => [
        'color' => '#10b981',
        'bg'    => '#ecfdf5',
        'border'=> '#6ee7b7',
        'text'  => '#065f46',
        'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'label' => 'Selesai',
        'icon'  => 'bi-check-circle-fill'
    ],
    'ditunda'   => [
        'color' => '#f59e0b',
        'bg'    => '#fffbeb',
        'border'=> '#fcd34d',
        'text'  => '#92400e',
        'badge' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'label' => 'Ditunda',
        'icon'  => 'bi-pause-circle-fill'
    ],
];

$events = [];
foreach ($jadwals as $j) {
    $meta = $statusMeta[$j['status']] ?? $statusMeta['terjadwal'];
    $events[] = [
        'id'              => (int)$j['id'],
        'title'           => $j['nama_proyek'],
        'start'           => $j['tanggal_mulai'],
        'end'             => date('Y-m-d', strtotime($j['tanggal_selesai'] . ' +1 day')),
        'backgroundColor' => $meta['bg'],
        'borderColor'     => $meta['color'],
        'textColor'       => $meta['text'],
        'status'          => $j['status'],
        'extendedProps'   => [
            'status'          => $j['status'],
            'statusLabel'     => $meta['label'],
            'statusIcon'      => $meta['icon'],
            'statusColor'     => $meta['color'],
            'keterangan'      => $j['keterangan'] ?: 'Tidak ada keterangan tambahan',
            'keteranganRaw'   => $j['keterangan'] ?? '',
            'pelanggan'       => $j['nama_pelanggan'],
            'proyek_id'       => (int)$j['proyek_id'],
            'durasi_hari'     => max(1, (int)$j['durasi_hari']),
            'progres'         => (int)$j['progres'],
            'tanggal_mulai'   => $j['tanggal_mulai'],
            'tanggal_selesai' => $j['tanggal_selesai'],
        ],
    ];
}

// Statistik Ringkas
$totalJadwal = count($jadwals);
$berjalan    = count(array_filter($jadwals, fn($j) => $j['status'] === 'berjalan'));
$terjadwal   = count(array_filter($jadwals, fn($j) => $j['status'] === 'terjadwal'));
$selesai     = count(array_filter($jadwals, fn($j) => $j['status'] === 'selesai'));
$ditunda     = count(array_filter($jadwals, fn($j) => $j['status'] === 'ditunda'));

// Agenda Terdekat (14 hari ke depan atau yang sedang berjalan)
$today = date('Y-m-d');
$next2Weeks = date('Y-m-d', strtotime('+14 days'));
$upcoming = array_filter($jadwals, function($j) use ($today, $next2Weeks) {
    return $j['tanggal_mulai'] <= $next2Weeks && $j['tanggal_selesai'] >= $today && $j['status'] !== 'selesai';
});

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<!-- FullCalendar v6 -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.9/index.global.min.js"></script>

<div class="max-w-7xl mx-auto">
    <!-- Header Halaman -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <div class="flex items-center gap-3.5">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-600 to-indigo-700 text-white flex items-center justify-center shadow-lg shadow-primary-600/25">
                <i class="bi bi-calendar3 text-lg"></i>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Jadwal Instalasi & Timeline</h2>
                <p class="text-xs text-slate-500 mt-0.5">Klik tanggal di kalender untuk menambah jadwal baru, atau geser (*drag*) jadwal untuk *reschedule*</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Toggle Mode Kalender vs Tabel -->
            <div class="inline-flex bg-slate-200/70 p-1 rounded-xl">
                <button type="button" id="btnModeCalendar" onclick="switchMainMode('calendar')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white text-slate-900 shadow-xs flex items-center gap-1.5">
                    <i class="bi bi-calendar-week"></i> Kalender
                </button>
                <button type="button" id="btnModeTable" onclick="switchMainMode('table')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900 flex items-center gap-1.5">
                    <i class="bi bi-table"></i> Tabel Daftar (<?= $totalJadwal ?>)
                </button>
            </div>

            <a href="<?= BASE_URL ?>/pages/proyek/gantt.php" class="btn-secondary btn-sm">
                <i class="bi bi-bar-chart-steps"></i> <span class="hidden sm:inline">Gantt Chart</span>
            </a>
            <button type="button" onclick="openQuickCreateModal('<?= date('Y-m-d') ?>', '<?= date('Y-m-d', strtotime('+3 days')) ?>')" class="btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Tambah Jadwal
            </button>
        </div>
    </div>

    <!-- Stat Cards Ringkas (Bisa Diklik untuk Filter Status) -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
        <?php
        $statItems = [
            ['key' => '',          'val' => $totalJadwal, 'label' => 'Semua Jadwal',    'accent' => '#475569', 'icon' => 'bi-layers-fill',       'cls' => 'bg-slate-100 text-slate-600'],
            ['key' => 'berjalan',  'val' => $berjalan,    'label' => 'Sedang Berjalan', 'accent' => '#4f46e5', 'icon' => 'bi-play-circle-fill',  'cls' => 'bg-indigo-50 text-indigo-600'],
            ['key' => 'terjadwal', 'val' => $terjadwal,   'label' => 'Terjadwal',       'accent' => '#64748b', 'icon' => 'bi-clock-fill',        'cls' => 'bg-slate-100 text-slate-600'],
            ['key' => 'selesai',   'val' => $selesai,     'label' => 'Selesai',         'accent' => '#10b981', 'icon' => 'bi-check-circle-fill', 'cls' => 'bg-emerald-50 text-emerald-600'],
            ['key' => 'ditunda',   'val' => $ditunda,     'label' => 'Ditunda',         'accent' => '#f59e0b', 'icon' => 'bi-pause-circle-fill', 'cls' => 'bg-amber-50 text-amber-600'],
        ];
        foreach ($statItems as $st):
        ?>
        <button type="button" onclick="triggerStatusFilter('<?= $st['key'] ?>')"
            data-stat-card="<?= $st['key'] ?>"
            class="stat-filter-card card p-4 text-left stat-card hover:shadow-md transition-all <?= $st['key'] === '' ? 'ring-2 ring-primary-500' : '' ?>"
            style="--stat-accent: <?= $st['accent'] ?>">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider"><?= $st['label'] ?></p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1 leading-none"><?= $st['val'] ?></p>
                </div>
                <span class="w-9 h-9 rounded-xl <?= $st['cls'] ?> flex items-center justify-center">
                    <i class="bi <?= $st['icon'] ?> text-base"></i>
                </span>
            </div>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Filter & Toolbar -->
    <div class="card p-3.5 mb-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <!-- Pencarian Cepat -->
            <div class="relative flex-1 min-w-[220px]">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" id="calendarSearch" placeholder="Cari nama proyek, pelanggan, atau keterangan jadwal..."
                    class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:bg-white transition-all">
            </div>

            <!-- Navigasi Kalender Custom (Hanya muncul di Mode Kalender) -->
            <div id="calendarCustomNav" class="flex flex-wrap items-center gap-2">
                <div class="inline-flex items-center bg-slate-100 p-1 rounded-xl">
                    <button type="button" onclick="window.pltsCalendar?.prev()" class="p-1.5 px-2.5 rounded-lg hover:bg-white text-slate-600 transition-all" title="Sebelumnya">
                        <i class="bi bi-chevron-left text-xs"></i>
                    </button>
                    <button type="button" onclick="window.pltsCalendar?.today()" class="px-3 py-1 rounded-lg hover:bg-white text-xs font-bold text-slate-700 transition-all">
                        Hari Ini
                    </button>
                    <button type="button" onclick="window.pltsCalendar?.next()" class="p-1.5 px-2.5 rounded-lg hover:bg-white text-slate-600 transition-all" title="Berikutnya">
                        <i class="bi bi-chevron-right text-xs"></i>
                    </button>
                </div>

                <span id="calendarTitleLabel" class="text-sm font-extrabold text-slate-800 px-2 min-w-[140px] text-center">-</span>

                <div class="inline-flex bg-slate-100 p-1 rounded-xl">
                    <button type="button" data-view="dayGridMonth" class="view-switch px-3 py-1.5 text-xs font-bold rounded-lg transition-all bg-white text-slate-900 shadow-2xs">
                        Bulan
                    </button>
                    <button type="button" data-view="timeGridWeek" class="view-switch px-3 py-1.5 text-xs font-bold rounded-lg transition-all text-slate-600 hover:text-slate-900">
                        Minggu
                    </button>
                    <button type="button" data-view="listMonth" class="view-switch px-3 py-1.5 text-xs font-bold rounded-lg transition-all text-slate-600 hover:text-slate-900">
                        Agenda
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================================
         MODE 1: TAMPILAN KALENDER + SIDEBAR AGENDA
    =========================================================== -->
    <div id="sectionCalendarView" class="grid grid-cols-1 xl:grid-cols-12 gap-5">
        <!-- Kalender Utama -->
        <div class="xl:col-span-9">
            <div class="card p-4 sm:p-5">
                <div id="calendar"></div>
            </div>
        </div>

        <!-- Panel Kanan: Agenda Aktif & Terdekat -->
        <div class="xl:col-span-3">
            <div class="card overflow-hidden sticky top-6">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i class="bi bi-lightning-charge-fill text-amber-500"></i> Agenda Terdekat
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Jadwal aktif & 14 hari ke depan</p>
                    </div>
                    <span class="badge-modern bg-slate-100 text-slate-700"><?= count($upcoming) ?></span>
                </div>

                <?php if (empty($upcoming)): ?>
                <div class="p-8 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2.5">
                        <i class="bi bi-calendar2-check text-xl"></i>
                    </div>
                    <p class="text-xs font-semibold text-slate-600">Tidak ada jadwal mendesak</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Semua agenda 2 minggu ke depan kosong</p>
                </div>
                <?php else: ?>
                <div class="divide-y divide-slate-100 max-h-[580px] overflow-y-auto">
                    <?php foreach ($upcoming as $u):
                        $meta = $statusMeta[$u['status']] ?? $statusMeta['terjadwal'];
                        $isOngoing = ($u['tanggal_mulai'] <= $today && $u['tanggal_selesai'] >= $today);
                    ?>
                    <div class="p-4 hover:bg-slate-50/80 transition-colors">
                        <div class="flex items-start gap-3">
                            <div class="w-11 h-12 rounded-xl border border-slate-200 bg-white flex flex-col items-center justify-center flex-shrink-0 shadow-2xs">
                                <span class="text-[9px] font-extrabold uppercase text-slate-400 leading-none"><?= date('M', strtotime($u['tanggal_mulai'])) ?></span>
                                <span class="text-base font-extrabold text-slate-800 leading-tight mt-0.5"><?= date('d', strtotime($u['tanggal_mulai'])) ?></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="w-2 h-2 rounded-full" style="background: <?= $meta['color'] ?>"></span>
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color: <?= $meta['color'] ?>">
                                        <?= $isOngoing ? 'Sedang Berlangsung' : $meta['label'] ?>
                                    </span>
                                </div>
                                <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $u['proyek_id'] ?>"
                                   class="text-xs font-bold text-slate-800 hover:text-primary-600 truncate block">
                                    <?= sanitize($u['nama_proyek']) ?>
                                </a>
                                <p class="text-[11px] text-slate-500 truncate mt-0.5"><?= sanitize($u['keterangan'] ?: $u['nama_pelanggan']) ?></p>

                                <div class="flex items-center justify-between mt-2.5 pt-2 border-t border-slate-100 text-[11px]">
                                    <span class="text-slate-400"><i class="bi bi-clock"></i> <?= max(1, (int)$u['durasi_hari']) ?> hari</span>
                                    <a href="<?= BASE_URL ?>/pages/jadwal/edit.php?id=<?= $u['id'] ?>"
                                       class="font-semibold text-primary-600 hover:text-primary-800 inline-flex items-center gap-1">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ==========================================================
         MODE 2: TAMPILAN TABEL DAFTAR JADWAL (DENGAN AKSI EDIT & HAPUS)
    =========================================================== -->
    <div id="sectionTableView" class="card overflow-hidden hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="table-header">Proyek & Pelanggan</th>
                        <th class="table-header">Tahapan / Keterangan</th>
                        <th class="table-header">Periode Jadwal</th>
                        <th class="table-header">Durasi</th>
                        <th class="table-header">Status</th>
                        <th class="table-header text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="jadwalTableBody">
                    <?php if (empty($jadwals)): ?>
                    <tr>
                        <td colspan="6" class="table-cell text-center py-12 text-slate-400">
                            Belum ada data jadwal instalasi.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($jadwals as $j):
                        $meta = $statusMeta[$j['status']] ?? $statusMeta['terjadwal'];
                    ?>
                    <tr class="table-row jadwal-row-item"
                        data-status="<?= $j['status'] ?>"
                        data-search="<?= strtolower(sanitize($j['nama_proyek'] . ' ' . $j['nama_pelanggan'] . ' ' . ($j['keterangan'] ?? ''))) ?>">
                        <td class="table-cell">
                            <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $j['proyek_id'] ?>" class="font-bold text-slate-800 hover:text-primary-600 block">
                                <?= sanitize($j['nama_proyek']) ?>
                            </a>
                            <span class="text-xs text-slate-400"><?= sanitize($j['nama_pelanggan']) ?></span>
                        </td>
                        <td class="table-cell">
                            <span class="text-sm text-slate-700"><?= sanitize($j['keterangan'] ?: '-') ?></span>
                        </td>
                        <td class="table-cell whitespace-nowrap">
                            <div class="text-xs font-semibold text-slate-700">
                                <?= formatTanggal($j['tanggal_mulai']) ?> — <?= formatTanggal($j['tanggal_selesai']) ?>
                            </div>
                        </td>
                        <td class="table-cell whitespace-nowrap">
                            <span class="text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg">
                                <?= max(1, (int)$j['durasi_hari']) ?> hari
                            </span>
                        </td>
                        <td class="table-cell whitespace-nowrap">
                            <?= statusBadge($j['status']) ?>
                        </td>
                        <td class="table-cell text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="<?= BASE_URL ?>/pages/jadwal/edit.php?id=<?= $j['id'] ?>"
                                   class="btn-secondary btn-sm" title="Edit Jadwal">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form method="POST" action="<?= BASE_URL ?>/api/jadwal/delete.php" class="inline"
                                      onsubmit="event.preventDefault(); confirmDelete(this, 'Hapus jadwal untuk proyek <?= addslashes(sanitize($j['nama_proyek'])) ?>?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $j['id'] ?>">
                                    <button type="submit" class="btn-secondary btn-sm text-red-600 hover:bg-red-50" title="Hapus Jadwal">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==========================================================
     MODAL 1: DETAIL & QUICK-EDIT JADWAL
=========================================================== -->
<div id="modalEvent" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden items-center justify-center p-4" onclick="if(event.target===this) closeEventModal()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100" onclick="event.stopPropagation()">
        <div class="px-6 py-4 border-b border-slate-100 flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div id="eventBadgeIcon" class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-calendar-event text-lg" id="eventIcon"></i>
                </div>
                <div class="min-w-0">
                    <span id="eventStatus" class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 block">Status</span>
                    <h3 id="eventTitle" class="text-base font-bold text-slate-900 truncate">Nama Proyek</h3>
                </div>
            </div>
            <button type="button" onclick="closeEventModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pelanggan</span>
                    <p id="eventPelanggan" class="text-xs font-bold text-slate-800 mt-1 truncate">-</p>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Durasi & Periode</span>
                    <p id="eventPeriode" class="text-xs font-bold text-slate-800 mt-1">-</p>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Tahapan / Keterangan Pekerjaan</span>
                <p id="eventKeterangan" class="text-sm text-slate-700 leading-relaxed">-</p>
            </div>

            <div>
                <div class="flex justify-between text-xs mb-1.5">
                    <span class="font-semibold text-slate-500">Progres Proyek Saat Ini</span>
                    <span id="eventProgresLabel" class="font-bold text-primary-700">0%</span>
                </div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div id="eventProgresBar" class="h-full bg-primary-600 rounded-full transition-all" style="width: 0%"></div>
                </div>
            </div>

            <!-- Quick Update Status Langsung dari Modal -->
            <form method="POST" action="<?= BASE_URL ?>/api/jadwal/update.php" class="pt-3 border-t border-slate-100">
                <?= csrfField() ?>
                <input type="hidden" name="id" id="quickEditId">
                <input type="hidden" name="proyek_id" id="quickEditProyekId">
                <input type="hidden" name="tanggal_mulai" id="quickEditMulai">
                <input type="hidden" name="tanggal_selesai" id="quickEditSelesai">
                <input type="hidden" name="keterangan" id="quickEditKeterangan">

                <label class="form-label text-xs">Ubah Cepat Status Jadwal:</label>
                <div class="flex gap-2">
                    <select name="status" id="quickEditStatus" class="form-select py-2 text-xs flex-1">
                        <option value="terjadwal">Terjadwal</option>
                        <option value="berjalan">Sedang Berjalan</option>
                        <option value="selesai">Selesai</option>
                        <option value="ditunda">Ditunda</option>
                    </select>
                    <button type="submit" class="btn-primary btn-sm">
                        <i class="bi bi-check2"></i> Update Status
                    </button>
                </div>
            </form>
        </div>

        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
            <form method="POST" action="<?= BASE_URL ?>/api/jadwal/delete.php" id="modalDeleteForm"
                  onsubmit="event.preventDefault(); confirmDelete(this, 'Yakin ingin menghapus jadwal ini?')">
                <?= csrfField() ?>
                <input type="hidden" name="id" id="deleteJadwalId">
                <button type="submit" class="btn-secondary btn-sm text-red-600 hover:bg-red-50">
                    <i class="bi bi-trash"></i> Hapus
                </button>
            </form>

            <div class="flex items-center gap-2">
                <a href="#" id="eventProyekLink" class="btn-secondary btn-sm">
                    <i class="bi bi-diagram-3"></i> Detail Proyek
                </a>
                <a href="#" id="eventEditLink" class="btn-primary btn-sm">
                    <i class="bi bi-pencil-square"></i> Edit Lengkap
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================
     MODAL 2: QUICK-CREATE JADWAL (SAAT KLIK TANGGAL DI KALENDER)
=========================================================== -->
<div id="modalQuickCreate" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden items-center justify-center p-4" onclick="if(event.target===this) closeQuickCreateModal()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100" onclick="event.stopPropagation()">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Tambah Jadwal Instalasi</h3>
                <p class="text-xs text-slate-400">Buat jadwal baru langsung pada tanggal terpilih</p>
            </div>
            <button type="button" onclick="closeQuickCreateModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/api/jadwal/store.php" class="p-6 space-y-4">
            <?= csrfField() ?>
            <div>
                <label class="form-label">Pilih Proyek <span class="text-red-500">*</span></label>
                <select name="proyek_id" class="form-select" required>
                    <option value="">-- Pilih Proyek PLTS --</option>
                    <?php foreach ($proyeksAktif as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= sanitize($p['nama_proyek']) ?> — <?= sanitize($p['nama_pelanggan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Tanggal Mulai <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_mulai" id="qcTanggalMulai" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Tanggal Selesai <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_selesai" id="qcTanggalSelesai" class="form-input" required>
                </div>
            </div>

            <div>
                <label class="form-label">Tahapan / Keterangan Pekerjaan</label>
                <input type="text" name="keterangan" class="form-input" placeholder="Contoh: Pemasangan mounting & panel blok B">
            </div>

            <div class="flex gap-2.5 pt-2">
                <button type="button" onclick="closeQuickCreateModal()" class="btn-secondary flex-1 justify-center">Batal</button>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="bi bi-check-lg"></i> Simpan Jadwal
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Styling Kalender Bersih & Modern (Linear / Notion Calendar Style) */
.fc {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
}
.fc .fc-col-header-cell {
    background: #f8fafc;
    padding: 10px 0 !important;
    border-bottom: 1px solid #e2e8f0 !important;
}
.fc .fc-col-header-cell-cushion {
    font-size: 11px !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b !important;
    text-decoration: none !important;
}
.fc .fc-daygrid-day-frame {
    min-height: 105px;
    padding: 4px !important;
    transition: background 0.15s ease;
}
.fc .fc-daygrid-day:hover {
    background: #f8fafc;
}
.fc .fc-daygrid-day-number {
    font-size: 12px !important;
    font-weight: 600 !important;
    color: #475569 !important;
    padding: 4px 8px !important;
    text-decoration: none !important;
}
.fc .fc-day-today {
    background: rgba(99, 102, 241, 0.04) !important;
}
.fc .fc-day-today .fc-daygrid-day-number {
    background: #4f46e5;
    color: #fff !important;
    border-radius: 8px;
    padding: 2px 7px !important;
    margin: 2px;
}
.fc .fc-event {
    border-radius: 7px !important;
    border-width: 0 0 0 3px !important;
    padding: 3px 7px !important;
    margin: 2px 3px !important;
    font-size: 11.5px !important;
    font-weight: 600 !important;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.fc .fc-event:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.10);
}
.fc td, .fc th {
    border-color: #f1f5f9 !important;
}
.fc .fc-scrollgrid {
    border-color: #e2e8f0 !important;
    border-radius: 14px;
    overflow: hidden;
}
</style>

<script>
const allEvents = <?= json_encode($events) ?>;
let currentFilterStatus = '';

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const savedView  = localStorage.getItem('plts_cal_view') || 'dayGridMonth';

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: savedView,
        locale: 'id',
        firstDay: 1,
        height: 'auto',
        headerToolbar: false, // Menggunakan custom toolbar modern kita di atas
        events: allEvents,
        editable: true,
        selectable: true,
        dayMaxEvents: 3,
        moreLinkText: (n) => `+${n} jadwal lainnya`,

        // Klik atau blok tanggal kosong untuk tambah jadwal cepat
        select: function(info) {
            const startStr = info.startStr.split('T')[0];
            const endDt = new Date(info.end.getTime() - 86400000);
            const endStr = endDt.toISOString().split('T')[0];
            openQuickCreateModal(startStr, endStr);
            calendar.unselect();
        },

        // Tampilan pill event yang bersih dan rapi
        eventContent: function(arg) {
            const p = arg.event.extendedProps;
            return {
                html: `
                    <div class="flex items-center justify-between gap-1.5 overflow-hidden">
                        <span class="truncate font-bold">${arg.event.title}</span>
                        <span class="text-[10px] opacity-75 flex-shrink-0">${p.durasi_hari}h</span>
                    </div>
                `
            };
        },

        eventClick: function(info) {
            showEventModal(info.event);
        },

        // Drag & Drop Reschedule
        eventDrop: function(info) {
            handleReschedule(info);
        },
        eventResize: function(info) {
            handleReschedule(info);
        },

        datesSet: function(info) {
            document.getElementById('calendarTitleLabel').textContent = info.view.title;
            localStorage.setItem('plts_cal_view', info.view.type);
            updateViewSwitcherUI(info.view.type);
        }
    });

    calendar.render();
    window.pltsCalendar = calendar;

    // View Switcher (Bulan / Minggu / Agenda)
    document.querySelectorAll('.view-switch').forEach(btn => {
        btn.addEventListener('click', () => {
            calendar.changeView(btn.dataset.view);
        });
    });

    // Live Search
    document.getElementById('calendarSearch').addEventListener('input', applyAllFilters);
});

function handleReschedule(info) {
    const newStart = info.event.start.toISOString().split('T')[0];
    const newEnd = info.event.end
        ? new Date(info.event.end.getTime() - 86400000).toISOString().split('T')[0]
        : newStart;

    fetch('<?= BASE_URL ?>/api/jadwal/reschedule.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            id: info.event.id,
            tanggal_mulai: newStart,
            tanggal_selesai: newEnd
        })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            Toast?.show(data.message || 'Gagal mengubah jadwal', 'error');
            info.revert();
        } else {
            Toast?.show(`Jadwal "${info.event.title}" diperbarui ke ${newStart} s/d ${newEnd}`, 'success');
        }
    })
    .catch(() => {
        Toast?.show('Koneksi gagal saat mengubah jadwal', 'error');
        info.revert();
    });
}

function updateViewSwitcherUI(activeView) {
    document.querySelectorAll('.view-switch').forEach(b => {
        const isActive = b.dataset.view === activeView;
        b.className = isActive
            ? 'view-switch px-3 py-1.5 text-xs font-bold rounded-lg transition-all bg-white text-slate-900 shadow-2xs'
            : 'view-switch px-3 py-1.5 text-xs font-bold rounded-lg transition-all text-slate-600 hover:text-slate-900';
    });
}

function switchMainMode(mode) {
    const isCal = mode === 'calendar';
    document.getElementById('sectionCalendarView').classList.toggle('hidden', !isCal);
    document.getElementById('calendarCustomNav').classList.toggle('hidden', !isCal);
    document.getElementById('sectionTableView').classList.toggle('hidden', isCal);

    document.getElementById('btnModeCalendar').className = isCal
        ? 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white text-slate-900 shadow-xs flex items-center gap-1.5'
        : 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900 flex items-center gap-1.5';
    document.getElementById('btnModeTable').className = !isCal
        ? 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white text-slate-900 shadow-xs flex items-center gap-1.5'
        : 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900 flex items-center gap-1.5';

    if (isCal) window.pltsCalendar?.updateSize();
}

function triggerStatusFilter(statusKey) {
    currentFilterStatus = statusKey;
    document.querySelectorAll('.stat-filter-card').forEach(card => {
        card.classList.toggle('ring-2', card.dataset.statCard === statusKey);
        card.classList.toggle('ring-primary-500', card.dataset.statCard === statusKey);
    });
    applyAllFilters();
}

function applyAllFilters() {
    const q = document.getElementById('calendarSearch').value.toLowerCase().trim();

    // Filter Kalender
    const filteredEvents = allEvents.filter(ev => {
        const matchStatus = !currentFilterStatus || ev.status === currentFilterStatus;
        const matchSearch = !q ||
            ev.title.toLowerCase().includes(q) ||
            (ev.extendedProps.pelanggan || '').toLowerCase().includes(q) ||
            (ev.extendedProps.keterangan || '').toLowerCase().includes(q);
        return matchStatus && matchSearch;
    });
    window.pltsCalendar.removeAllEvents();
    window.pltsCalendar.addEventSource(filteredEvents);

    // Filter Tabel Daftar Jadwal
    document.querySelectorAll('.jadwal-row-item').forEach(row => {
        const matchStatus = !currentFilterStatus || row.dataset.status === currentFilterStatus;
        const matchSearch = !q || row.dataset.search.includes(q);
        row.classList.toggle('hidden', !(matchStatus && matchSearch));
    });
}

// Modal Detail & Quick-Edit
function showEventModal(event) {
    const p = event.extendedProps;
    document.getElementById('eventTitle').textContent = event.title;
    document.getElementById('eventStatus').textContent = p.statusLabel;
    document.getElementById('eventStatus').style.color = p.statusColor;
    document.getElementById('eventPelanggan').textContent = p.pelanggan;
    document.getElementById('eventKeterangan').textContent = p.keterangan;
    document.getElementById('eventPeriode').textContent = `${p.tanggal_mulai} s/d ${p.tanggal_selesai} (${p.durasi_hari} hari)`;

    document.getElementById('eventProgresLabel').textContent = p.progres + '%';
    document.getElementById('eventProgresBar').style.width = p.progres + '%';

    // Isi form Quick Update Status
    document.getElementById('quickEditId').value = event.id;
    document.getElementById('quickEditProyekId').value = p.proyek_id;
    document.getElementById('quickEditMulai').value = p.tanggal_mulai;
    document.getElementById('quickEditSelesai').value = p.tanggal_selesai;
    document.getElementById('quickEditKeterangan').value = p.keteranganRaw;
    document.getElementById('quickEditStatus').value = p.status;

    document.getElementById('deleteJadwalId').value = event.id;
    document.getElementById('eventProyekLink').href = '<?= BASE_URL ?>/pages/proyek/show.php?id=' + p.proyek_id;
    document.getElementById('eventEditLink').href = '<?= BASE_URL ?>/pages/jadwal/edit.php?id=' + event.id;

    const modal = document.getElementById('modalEvent');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEventModal() {
    const modal = document.getElementById('modalEvent');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Modal Quick-Create
function openQuickCreateModal(startDate, endDate) {
    document.getElementById('qcTanggalMulai').value = startDate;
    document.getElementById('qcTanggalSelesai').value = endDate || startDate;
    const modal = document.getElementById('modalQuickCreate');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeQuickCreateModal() {
    const modal = document.getElementById('modalQuickCreate');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>