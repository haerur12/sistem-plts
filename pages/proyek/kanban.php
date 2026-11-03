<?php
// pages/proyek/kanban.php — KANBAN BOARD INTERAKTIF + AVATAR TIM PROYEK
$pageTitle   = 'Kanban Board Proyek';
$currentPage = 'kanban';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'manajer_proyek']);

$db = db();
$proyeks = $db->query("
    SELECT p.*, pl.nama_pelanggan
    FROM proyek p
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    ORDER BY p.created_at DESC
")->fetchAll();

// Ambil daftar tim per proyek
$timPerProyek = [];
try {
    $stmtTim = $db->query("SELECT proyek_id, nama_personel, peran_proyek FROM proyek_tim ORDER BY id ASC");
    foreach ($stmtTim->fetchAll() as $t) {
        $timPerProyek[(int)$t['proyek_id']][] = $t;
    }
} catch (Exception $e) {
    $timPerProyek = [];
}

$columns = [
    'survei'      => ['label' => 'Survei Lokasi', 'color' => 'border-slate-300',   'badge' => 'bg-slate-100 text-slate-700',     'icon' => 'bi-compass'],
    'perencanaan' => ['label' => 'Perencanaan',   'color' => 'border-sky-400',     'badge' => 'bg-sky-50 text-sky-700',          'icon' => 'bi-vector-pen'],
    'instalasi'   => ['label' => 'Instalasi',     'color' => 'border-indigo-500',  'badge' => 'bg-indigo-50 text-indigo-700',    'icon' => 'bi-tools'],
    'selesai'     => ['label' => 'Selesai',       'color' => 'border-emerald-500', 'badge' => 'bg-emerald-50 text-emerald-700',  'icon' => 'bi-check2-circle'],
    'ditunda'     => ['label' => 'Ditunda',       'color' => 'border-amber-500',   'badge' => 'bg-amber-50 text-amber-700',      'icon' => 'bi-pause-circle'],
];

$grouped = array_fill_keys(array_keys($columns), []);
foreach ($proyeks as $p) {
    if (isset($grouped[$p['status']])) {
        $grouped[$p['status']][] = $p;
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <div class="flex items-center gap-3.5">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-600 to-indigo-700 text-white flex items-center justify-center shadow-lg shadow-primary-600/25">
                <i class="bi bi-kanban text-lg"></i>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Kanban Board Proyek PLTS</h2>
                <p class="text-xs text-slate-500 mt-0.5">Geser kartu proyek (*drag & drop*) antar kolom untuk mengubah tahapan status proyek secara langsung</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="<?= BASE_URL ?>/pages/proyek/index.php" class="btn-secondary btn-sm">
                <i class="bi bi-table"></i> Mode Tabel
            </a>
            <?php if (isAdmin()): ?>
            <a href="<?= BASE_URL ?>/pages/proyek/create.php" class="btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Tambah Proyek
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Papan Kanban 5 Kolom -->
    <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-4 items-start">
        <?php foreach ($columns as $statusKey => $col):
            $items = $grouped[$statusKey];
        ?>
        <div class="bg-slate-100/80 border border-slate-200/80 rounded-2xl p-3 flex flex-col max-h-[78vh]">
            <!-- Header Kolom -->
            <div class="flex items-center justify-between px-2 py-1.5 mb-2.5 border-t-4 <?= $col['color'] ?> rounded-t-sm">
                <div class="flex items-center gap-2">
                    <i class="bi <?= $col['icon'] ?> text-slate-600 text-sm"></i>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800"><?= $col['label'] ?></h3>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold <?= $col['badge'] ?>" id="count_<?= $statusKey ?>">
                    <?= count($items) ?>
                </span>
            </div>

            <!-- Area Dropzone Kartu -->
            <div class="kanban-dropzone space-y-3 overflow-y-auto flex-1 min-h-[220px] p-1"
                 data-status="<?= $statusKey ?>"
                 ondragover="event.preventDefault(); this.classList.add('bg-primary-50/50', 'rounded-xl');"
                 ondragleave="this.classList.remove('bg-primary-50/50', 'rounded-xl');"
                 ondrop="handleKanbanDrop(event, this)">

                <?php foreach ($items as $p):
                    $pid = (int)$p['id'];
                    $anggota = $timPerProyek[$pid] ?? [];
                ?>
                <div class="card p-4 cursor-grab active:cursor-grabbing hover:shadow-md transition-all kanban-card"
                     draggable="true"
                     data-id="<?= $pid ?>"
                     ondragstart="handleDragStart(event, <?= $pid ?>)">
                    <div class="flex items-start justify-between gap-2 mb-1.5">
                        <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $pid ?>" class="text-xs font-bold text-slate-900 hover:text-primary-600 leading-snug">
                            <?= sanitize($p['nama_proyek']) ?>
                        </a>
                    </div>
                    <p class="text-[11px] text-slate-400 truncate mb-3">
                        <i class="bi bi-building"></i> <?= sanitize($p['nama_pelanggan']) ?>
                    </p>

                    <!-- Progress Bar -->
                    <div class="mb-3">
                        <div class="flex justify-between text-[10px] font-semibold text-slate-500 mb-1">
                            <span>Progres</span>
                            <span><?= (int)$p['progres'] ?>%</span>
                        </div>
                        <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-primary-600 rounded-full" style="width: <?= (int)$p['progres'] ?>%"></div>
                        </div>
                    </div>

                    <!-- Footer Kartu: Avatar Tim & Tanggal -->
                    <div class="flex items-center justify-between pt-2.5 border-t border-slate-100">
                        <div class="flex items-center -space-x-1.5">
                            <?php if (empty($anggota)): ?>
                            <span class="text-[10px] text-slate-400">0 personel</span>
                            <?php else: ?>
                            <?php foreach (array_slice($anggota, 0, 3) as $idx => $agt):
                                $bgAv = ['bg-indigo-600', 'bg-emerald-600', 'bg-sky-600'][$idx % 3];
                            ?>
                            <span class="w-6 h-6 rounded-full <?= $bgAv ?> text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-white"
                                  title="<?= sanitize($agt['nama_personel'] . ' (' . $agt['peran_proyek'] . ')') ?>">
                                <?= strtoupper(substr($agt['nama_personel'], 0, 1)) ?>
                            </span>
                            <?php endforeach; ?>
                            <?php if (count($anggota) > 3): ?>
                            <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 text-[9px] font-bold flex items-center justify-center ring-2 ring-white">
                                +<?= count($anggota) - 3 ?>
                            </span>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <span class="text-[10px] text-slate-400">
                            <i class="bi bi-calendar2"></i> <?= formatTanggalShort($p['tanggal_selesai']) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
let draggedProjectId = null;

function handleDragStart(e, id) {
    draggedProjectId = id;
    e.dataTransfer.setData('text/plain', id);
}

function handleKanbanDrop(e, zoneEl) {
    e.preventDefault();
    zoneEl.classList.remove('bg-primary-50/50', 'rounded-xl');
    const newStatus = zoneEl.dataset.status;
    if (!draggedProjectId || !newStatus) return;

    const cardEl = document.querySelector(`.kanban-card[data-id="${draggedProjectId}"]`);
    if (!cardEl) return;

    zoneEl.prepend(cardEl);
    updateKanbanCounts();

    fetch('<?= BASE_URL ?>/api/proyek/quick-update.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            id: draggedProjectId,
            field: 'status',
            value: newStatus
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Toast?.show(`Status proyek diubah ke ${newStatus.toUpperCase()}`, 'success');
        } else {
            Toast?.show(data.message || 'Gagal memperbarui status', 'error');
            location.reload();
        }
    })
    .catch(() => Toast?.show('Koneksi gagal', 'error'));
}

function updateKanbanCounts() {
    document.querySelectorAll('.kanban-dropzone').forEach(z => {
        const st = z.dataset.status;
        const cnt = z.querySelectorAll('.kanban-card').length;
        const badge = document.getElementById('count_' + st);
        if (badge) badge.textContent = cnt;
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>