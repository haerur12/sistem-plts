<?php
// pages/jadwal/index.php — KALENDAR INTERAKTIF
$pageTitle   = 'Kalender Jadwal';
$currentPage = 'jadwal';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);

$db = db();

// Ambil semua jadwal
$stmt = $db->query("
    SELECT j.*, p.nama_proyek, p.id as proyek_id, pl.nama_pelanggan
    FROM jadwal j
    JOIN proyek p ON j.proyek_id = p.id
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    ORDER BY j.tanggal_mulai ASC
");
$jadwals = $stmt->fetchAll();

// Format untuk FullCalendar
$events = [];
$statusColors = [
    'terjadwal' => '#94a3b8',
    'berjalan'  => '#3b82f6',
    'selesai'   => '#10b981',
    'ditunda'   => '#f59e0b',
];
$statusLabels = [
    'terjadwal' => 'Terjadwal',
    'berjalan'  => 'Berjalan',
    'selesai'   => 'Selesai',
    'ditunda'   => 'Ditunda',
];

foreach ($jadwals as $j) {
    $events[] = [
        'id'          => $j['id'],
        'title'       => $j['nama_proyek'],
        'start'       => $j['tanggal_mulai'],
        'end'         => date('Y-m-d', strtotime($j['tanggal_selesai'] . ' +1 day')),
        'color'       => $statusColors[$j['status']] ?? '#94a3b8',
        'status'      => $j['status'],
        'statusLabel' => $statusLabels[$j['status']] ?? $j['status'],
        'keterangan'  => $j['keterangan'] ?? '-',
        'pelanggan'   => $j['nama_pelanggan'],
        'proyek_id'   => $j['proyek_id'],
        'extendedProps' => [
            'status'     => $j['status'],
            'keterangan' => $j['keterangan'] ?? '-',
            'pelanggan'  => $j['nama_pelanggan'],
            'proyek_id'  => $j['proyek_id'],
        ],
    ];
}

// Statistik
$totalJadwal = count($jadwals);
$berjalan    = count(array_filter($jadwals, fn($j) => $j['status'] === 'berjalan'));
$terjadwal   = count(array_filter($jadwals, fn($j) => $j['status'] === 'terjadwal'));
$selesai     = count(array_filter($jadwals, fn($j) => $j['status'] === 'selesai'));

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<!-- FullCalendar CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.9/index.global.min.js"></script>

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div>
        <h2 class="text-lg font-bold text-slate-900 tracking-tight">Kalender Jadwal Instalasi</h2>
        <p class="text-[13px] text-slate-500 mt-1">Drag event untuk reschedule · Klik event untuk detail</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>/pages/jadwal/index.php?view=table" class="btn-secondary">
            <i class="bi bi-list-ul"></i> Tampilan Tabel
        </a>
        <a href="<?= BASE_URL ?>/pages/jadwal/create.php" class="btn-primary">
            <i class="bi bi-calendar-plus"></i> Tambah Jadwal
        </a>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
            <i class="bi bi-calendar3 text-lg"></i>
        </div>
        <div>
            <p class="text-[11px] text-slate-500 font-medium">Total Jadwal</p>
            <p class="text-xl font-bold text-slate-900"><?= $totalJadwal ?></p>
        </div>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-sky-50 text-sky-600 rounded-xl flex items-center justify-center">
            <i class="bi bi-play-circle text-lg"></i>
        </div>
        <div>
            <p class="text-[11px] text-slate-500 font-medium">Berjalan</p>
            <p class="text-xl font-bold text-slate-900"><?= $berjalan ?></p>
        </div>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-slate-50 text-slate-600 rounded-xl flex items-center justify-center">
            <i class="bi bi-clock-history text-lg"></i>
        </div>
        <div>
            <p class="text-[11px] text-slate-500 font-medium">Terjadwal</p>
            <p class="text-xl font-bold text-slate-900"><?= $terjadwal ?></p>
        </div>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
            <i class="bi bi-check-circle text-lg"></i>
        </div>
        <div>
            <p class="text-[11px] text-slate-500 font-medium">Selesai</p>
            <p class="text-xl font-bold text-slate-900"><?= $selesai ?></p>
        </div>
    </div>
</div>

<!-- Legend -->
<div class="card p-3 mb-4 flex flex-wrap items-center gap-4">
    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Status:</span>
    <?php foreach ($statusColors as $key => $color): ?>
    <span class="flex items-center gap-1.5 text-xs text-slate-700">
        <span class="w-3 h-3 rounded" style="background: <?= $color ?>"></span>
        <?= $statusLabels[$key] ?>
    </span>
    <?php endforeach; ?>
</div>

<!-- Calendar -->
<div class="card p-5">
    <div id="calendar"></div>
</div>

<!-- Modal Detail Event -->
<div id="modalEvent" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden" onclick="closeEventModal()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden" onclick="event.stopPropagation()">
        <div id="eventHeader" class="px-6 py-4 text-white" style="background: #6366f1;">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold opacity-90 uppercase tracking-wider" id="eventStatus">Status</p>
                    <h3 class="text-lg font-bold mt-1 truncate" id="eventTitle">Judul</h3>
                </div>
                <button onclick="closeEventModal()" class="p-1.5 rounded-lg bg-white/20 hover:bg-white/30 transition-colors">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </div>
        <div class="p-6 space-y-4">
            <div class="flex items-start gap-3">
                <i class="bi bi-person text-slate-400 mt-0.5"></i>
                <div>
                    <p class="text-xs text-slate-400">Pelanggan</p>
                    <p class="text-sm font-medium text-slate-800" id="eventPelanggan">-</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <i class="bi bi-calendar-event text-slate-400 mt-0.5"></i>
                <div>
                    <p class="text-xs text-slate-400">Periode</p>
                    <p class="text-sm font-medium text-slate-800" id="eventPeriode">-</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <i class="bi bi-card-text text-slate-400 mt-0.5"></i>
                <div>
                    <p class="text-xs text-slate-400">Keterangan</p>
                    <p class="text-sm text-slate-700" id="eventKeterangan">-</p>
                </div>
            </div>
        </div>
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

<style>
.fc { font-family: 'Plus Jakarta Sans', sans-serif !important; }
.fc .fc-toolbar-title { font-size: 1.1rem !important; font-weight: 700 !important; color: #1e293b; }
.fc .fc-button {
    background: #fff !important;
    border: 1px solid #e2e8f0 !important;
    color: #475569 !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    padding: 0.4rem 0.8rem !important;
    text-transform: capitalize !important;
    box-shadow: none !important;
}
.fc .fc-button:hover { background: #f1f5f9 !important; }
.fc .fc-button-primary:not(:disabled).fc-button-active {
    background: #4f46e5 !important;
    border-color: #4f46e5 !important;
    color: #fff !important;
}
.fc .fc-col-header-cell { background: #f8fafc; padding: 0.5rem 0; }
.fc .fc-col-header-cell-cushion { color: #64748b; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.fc .fc-daygrid-day-number { color: #475569; font-size: 0.8rem; font-weight: 500; padding: 0.4rem 0.5rem; }
.fc .fc-day-today { background: #eef2ff !important; }
.fc .fc-event {
    border: none !important;
    border-radius: 6px !important;
    padding: 2px 6px !important;
    font-size: 0.72rem !important;
    font-weight: 600 !important;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.fc .fc-event:hover { transform: translateY(-1px); box-shadow: 0 4px 12px -4px rgba(0,0,0,0.2); }
.fc .fc-event-title { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fc td, .fc th { border-color: #f1f5f9 !important; }
.fc .fc-scrollgrid { border-color: #e2e8f0 !important; border-radius: 12px; overflow: hidden; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'id',
        firstDay: 1,
        height: 700,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        buttonText: {
            today: 'Hari Ini',
            month: 'Bulan',
            week: 'Minggu',
            list: 'Agenda'
        },
        events: <?= json_encode($events) ?>,
        editable: true,
        eventStartEditable: true,
        eventDurationEditable: true,
        eventClick: function(info) {
            showEventModal(info.event);
        },
        eventDrop: function(info) {
            if (!confirm(`Reschedule "${info.event.title}" ke ${info.event.start.toISOString().split('T')[0]}?`)) {
                info.revert();
                return;
            }
            const newEnd = info.event.end ? new Date(info.event.end.getTime() - 86400000) : info.event.start;
            fetch('<?= BASE_URL ?>/api/jadwal/reschedule.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    id: info.event.id,
                    tanggal_mulai: info.event.start.toISOString().split('T')[0],
                    tanggal_selesai: newEnd.toISOString().split('T')[0]
                })
            }).then(r => r.json()).then(data => {
                if (!data.success) {
                    alert('Gagal reschedule: ' + (data.message || 'Unknown error'));
                    info.revert();
                }
            }).catch(() => { info.revert(); alert('Terjadi kesalahan'); });
        }
    });
    calendar.render();
});

function showEventModal(event) {
    const props = event.extendedProps;
    document.getElementById('eventTitle').textContent = event.title;
    document.getElementById('eventStatus').textContent = props.statusLabel || props.status;
    document.getElementById('eventPelanggan').textContent = props.pelanggan || '-';
    document.getElementById('eventKeterangan').textContent = props.keterangan || '-';
    
    const start = event.start.toISOString().split('T')[0];
    const end = event.end ? new Date(event.end.getTime() - 86400000).toISOString().split('T')[0] : start;
    document.getElementById('eventPeriode').textContent = `${formatTgl(start)} — ${formatTgl(end)}`;
    
    document.getElementById('eventProyekLink').href = '<?= BASE_URL ?>/pages/proyek/show.php?id=' + props.proyek_id;
    document.getElementById('eventEditLink').href = '<?= BASE_URL ?>/pages/jadwal/edit.php?id=' + event.id;
    document.getElementById('eventHeader').style.background = event.backgroundColor || '#6366f1';
    document.getElementById('modalEvent').classList.remove('hidden');
}

function closeEventModal() {
    document.getElementById('modalEvent').classList.add('hidden');
}

function formatTgl(tgl) {
    const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const d = new Date(tgl);
    return `${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEventModal(); });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>