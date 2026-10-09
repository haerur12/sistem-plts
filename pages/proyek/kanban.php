<?php
// pages/proyek/kanban.php — KANBAN BOARD
$pageTitle   = 'Kanban Board';
$currentPage = 'kanban';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);

$db = db();

// Ambil semua proyek
$proyeks = $db->query("
    SELECT p.*, pl.nama_pelanggan,
    (SELECT COUNT(*) FROM laporan_lapangan WHERE proyek_id = p.id) as total_laporan
    FROM proyek p
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    ORDER BY p.created_at DESC
")->fetchAll();

// Kelompokkan per status
$statuses = [
    'survei'      => ['label' => 'Survei',       'color' => '#94a3b8', 'bg' => 'bg-slate-50',    'icon' => 'bi-search'],
    'perencanaan' => ['label' => 'Perencanaan',  'color' => '#38bdf8', 'bg' => 'bg-sky-50',      'icon' => 'bi-clipboard-data'],
    'instalasi'   => ['label' => 'Instalasi',    'color' => '#6366f1', 'bg' => 'bg-indigo-50',   'icon' => 'bi-tools'],
    'selesai'     => ['label' => 'Selesai',      'color' => '#10b981', 'bg' => 'bg-emerald-50',  'icon' => 'bi-check-circle'],
    'ditunda'     => ['label' => 'Ditunda',      'color' => '#f59e0b', 'bg' => 'bg-amber-50',    'icon' => 'bi-pause-circle'],
];

$grouped = [];
foreach ($statuses as $key => $s) $grouped[$key] = [];
foreach ($proyeks as $p) $grouped[$p['status']][] = $p;

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div>
        <h2 class="text-lg font-bold text-slate-900 tracking-tight">Kanban Board Proyek</h2>
        <p class="text-[13px] text-slate-500 mt-1">Drag & drop card untuk update status</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>/pages/proyek/index.php" class="btn-secondary">
            <i class="bi bi-list-ul"></i> Tampilan Tabel
        </a>
        <?php if (isAdmin()): ?>
        <a href="<?= BASE_URL ?>/pages/proyek/create.php" class="btn-primary">
            <i class="bi bi-plus-lg"></i> Tambah Proyek
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="flex gap-4 overflow-x-auto pb-4" id="kanbanBoard">
    <?php foreach ($statuses as $statusKey => $statusMeta):
        $items = $grouped[$statusKey];
    ?>
    <div class="flex-shrink-0 w-80">
        <!-- Column Header -->
        <div class="card mb-3 overflow-hidden">
            <div class="px-4 py-3 flex items-center justify-between" style="background: <?= $statusMeta['color'] ?>15; border-left: 4px solid <?= $statusMeta['color'] ?>;">
                <div class="flex items-center gap-2">
                    <i class="bi <?= $statusMeta['icon'] ?>" style="color: <?= $statusMeta['color'] ?>"></i>
                    <h3 class="text-sm font-bold text-slate-800"><?= $statusMeta['label'] ?></h3>
                    <span class="px-2 py-0.5 bg-white rounded-full text-xs font-bold text-slate-600 shadow-sm">
                        <?= count($items) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Drop Zone -->
        <div class="kanban-column space-y-3 min-h-[200px] p-2 rounded-xl transition-colors"
            data-status="<?= $statusKey ?>"
            ondragover="handleDragOver(event)"
            ondragleave="handleDragLeave(event)"
            ondrop="handleDrop(event, '<?= $statusKey ?>')">
            
            <?php if (empty($items)): ?>
            <div class="text-center py-8 text-slate-400 text-xs border-2 border-dashed border-slate-200 rounded-xl">
                <i class="bi bi-inbox text-2xl block mb-2"></i>
                Tidak ada proyek
            </div>
            <?php else: ?>
                <?php foreach ($items as $p): ?>
                <div class="kanban-card card card-hover p-4 cursor-grab active:cursor-grabbing"
                    draggable="true"
                    data-id="<?= $p['id'] ?>"
                    ondragstart="handleDragStart(event, <?= $p['id'] ?>)">
                    
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $p['id'] ?>" 
                            class="text-sm font-semibold text-slate-800 hover:text-primary-600 transition-colors line-clamp-2 flex-1">
                            <?= sanitize($p['nama_proyek']) ?>
                        </a>
                    </div>
                    
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 mb-3">
                        <i class="bi bi-person"></i>
                        <span class="truncate"><?= sanitize($p['nama_pelanggan']) ?></span>
                    </div>

                    <!-- Progress -->
                    <div class="mb-3">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Progres</span>
                            <span class="text-xs font-bold" style="color: <?= $statusMeta['color'] ?>"><?= $p['progres'] ?>%</span>
                        </div>
                        <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all" 
                                style="width: <?= $p['progres'] ?>%; background: <?= $statusMeta['color'] ?>"></div>
                        </div>
                    </div>

                    <!-- Meta -->
                    <div class="flex items-center justify-between text-[11px] text-slate-400 pt-2 border-t border-slate-100">
                        <span class="inline-flex items-center gap-1">
                            <i class="bi bi-calendar3"></i>
                            <?= formatTanggalShort($p['tanggal_mulai']) ?>
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <i class="bi bi-file-earmark-text"></i>
                            <?= $p['total_laporan'] ?> laporan
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<style>
.kanban-column.drag-over {
    background: #eef2ff;
    border: 2px dashed #6366f1;
}
.kanban-card.dragging {
    opacity: 0.4;
    transform: rotate(2deg);
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
let draggedCardId = null;

function handleDragStart(e, id) {
    draggedCardId = id;
    e.target.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', id);
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    e.currentTarget.classList.add('drag-over');
}

function handleDragLeave(e) {
    e.currentTarget.classList.remove('drag-over');
}

function handleDrop(e, newStatus) {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over');
    
    const id = draggedCardId;
    if (!id) return;
    
    // Optimistic update UI
    const card = document.querySelector(`.kanban-card[data-id="${id}"]`);
    if (card) card.classList.remove('dragging');
    
    // Update via AJAX
    fetch('<?= BASE_URL ?>/api/proyek/quick-update.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            id: id,
            field: 'status',
            value: newStatus
        })
    }).then(r => r.json()).then(data => {
        if (data.success) {
            setTimeout(() => location.reload(), 400);
        } else {
            alert('Gagal update: ' + (data.message || 'Unknown error'));
            location.reload();
        }
    }).catch(() => {
        alert('Terjadi kesalahan');
        location.reload();
    });
}

document.addEventListener('dragend', e => {
    document.querySelectorAll('.kanban-card').forEach(c => c.classList.remove('dragging'));
    document.querySelectorAll('.kanban-column').forEach(c => c.classList.remove('drag-over'));
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>