<?php
// pages/proyek/index.php — DENGAN QUICK UPDATE MODAL
$pageTitle   = 'Manajemen Proyek';
$currentPage = 'proyek';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);

$db = db();
$filterStatus = $_GET['status'] ?? '';
$search       = $_GET['search'] ?? '';

$where  = ['1=1'];
$params = [];
if ($filterStatus) { $where[] = 'p.status = ?';          $params[] = $filterStatus; }
if ($search)       { $where[] = 'p.nama_proyek LIKE ?';  $params[] = "%$search%"; }
$whereStr = implode(' AND ', $where);

$perPage  = 10;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;

$total = $db->prepare("SELECT COUNT(*) FROM proyek p JOIN pelanggan pl ON p.pelanggan_id = pl.id WHERE $whereStr");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("
    SELECT p.*, pl.nama_pelanggan
    FROM proyek p
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    WHERE $whereStr
    ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$proyeks = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div>
        <h2 class="text-lg font-bold text-slate-900 tracking-tight">Daftar Proyek</h2>
        <p class="text-[13px] text-slate-500 mt-1"><?= $totalRows ?> total proyek</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>/pages/proyek/kanban.php" class="btn-secondary">
            <i class="bi bi-kanban"></i> Kanban
        </a>
        <?php if (isAdmin()): ?>
        <a href="<?= BASE_URL ?>/pages/proyek/create.php" class="btn-primary">
            <i class="bi bi-plus-lg"></i> Tambah Proyek
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter -->
<div class="card p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3">
        <div class="flex-1 min-w-48">
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Cari nama proyek..."
                    class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-4 focus:ring-primary-500/15 focus:border-primary-500 transition-all">
            </div>
        </div>
        <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
            <option value="">Semua Status</option>
            <?php foreach (['survei','perencanaan','instalasi','selesai','ditunda'] as $st): ?>
            <option value="<?= $st ?>" <?= $filterStatus === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary"><i class="bi bi-funnel"></i> Filter</button>
        <?php if ($filterStatus || $search): ?>
        <a href="<?= BASE_URL ?>/pages/proyek/index.php" class="btn-secondary"><i class="bi bi-x-lg"></i> Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabel -->
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="table-header">No</th>
                    <th class="table-header">Nama Proyek</th>
                    <th class="table-header">Pelanggan</th>
                    <th class="table-header">Tanggal</th>
                    <th class="table-header">Status</th>
                    <th class="table-header">Progres</th>
                    <th class="table-header">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php if (empty($proyeks)): ?>
                <tr><td colspan="7" class="px-4 py-12 text-center">
                    <span class="empty-icon mb-3"><i class="bi bi-diagram-3"></i></span>
                    <p class="text-sm text-slate-400">Belum ada data proyek</p>
                </td></tr>
            <?php else: ?>
                <?php foreach ($proyeks as $i => $p): ?>
                <tr class="table-row" id="row-<?= $p['id'] ?>">
                    <td class="table-cell text-slate-400"><?= $offset + $i + 1 ?></td>
                    <td class="table-cell">
                        <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $p['id'] ?>" class="font-medium text-slate-800 hover:text-primary-600">
                            <?= sanitize($p['nama_proyek']) ?>
                        </a>
                    </td>
                    <td class="table-cell text-slate-500 text-xs"><?= sanitize($p['nama_pelanggan']) ?></td>
                    <td class="table-cell">
                        <div class="text-xs text-slate-500">
                            <?= formatTanggalShort($p['tanggal_mulai']) ?><br>
                            <span class="text-slate-400">s/d <?= formatTanggalShort($p['tanggal_selesai']) ?></span>
                        </div>
                    </td>
                    <td class="table-cell" id="status-cell-<?= $p['id'] ?>">
                        <?= statusBadge($p['status']) ?>
                    </td>
                    <td class="table-cell" id="progres-cell-<?= $p['id'] ?>">
                        <div class="flex items-center gap-2 min-w-24">
                            <div class="flex-1 h-1.5 progress-track rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-primary-500 to-primary-600 rounded-full progress-fill" 
                                    style="width:<?= $p['progres'] ?>%"></div>
                            </div>
                            <span class="text-xs font-semibold text-slate-500 w-10 text-right"><?= $p['progres'] ?>%</span>
                        </div>
                    </td>
                    <td class="table-cell">
                        <div class="flex items-center gap-1">
                            <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $p['id'] ?>" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-primary-600 transition-colors" title="Detail">
                                <i class="bi bi-eye text-sm"></i>
                            </a>
                            <!-- QUICK UPDATE BUTTON -->
                            <button onclick="openQuickUpdate(<?= $p['id'] ?>, '<?= addslashes($p['nama_proyek']) ?>', '<?= $p['status'] ?>', <?= $p['progres'] ?>)" 
                                class="p-1.5 rounded-lg hover:bg-primary-50 text-slate-500 hover:text-primary-600 transition-colors" title="Quick Update">
                                <i class="bi bi-lightning-charge-fill text-sm"></i>
                            </button>
                            <?php if (isAdminOrManajer()): ?>
                            <a href="<?= BASE_URL ?>/pages/proyek/edit.php?id=<?= $p['id'] ?>" class="p-1.5 rounded-lg hover:bg-amber-50 text-slate-500 hover:text-amber-600 transition-colors" title="Edit">
                                <i class="bi bi-pencil text-sm"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between">
        <p class="text-xs text-slate-400">Menampilkan <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalRows) ?> dari <?= $totalRows ?></p>
        <div class="flex gap-1">
            <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
            <a href="?page=<?= $pg ?>&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($search) ?>"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-xs transition-colors
                <?= $pg === $page ? 'bg-primary-600 text-white shadow-sm shadow-primary-600/30' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
                <?= $pg ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- QUICK UPDATE MODAL -->
<div id="quickUpdateModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden" onclick="closeQuickUpdate()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden" onclick="event.stopPropagation()">
        <div class="px-5 py-4 bg-gradient-to-br from-primary-600 to-primary-700 text-white">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="bi bi-lightning-charge-fill text-amber-300"></i>
                        <span class="text-xs font-bold uppercase tracking-wider opacity-90">Quick Update</span>
                    </div>
                    <h3 class="text-sm font-bold truncate" id="quTitle">Proyek</h3>
                </div>
                <button onclick="closeQuickUpdate()" class="p-1.5 rounded-lg bg-white/20 hover:bg-white/30 transition-colors">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </div>
        
        <form id="quickUpdateForm" class="p-5 space-y-4">
            <input type="hidden" name="id" id="quId">
            
            <div>
                <label class="form-label text-xs">Status</label>
                <select name="status" id="quStatus" class="form-select text-sm">
                    <?php foreach (['survei','perencanaan','instalasi','selesai','ditunda'] as $st): ?>
                    <option value="<?= $st ?>"><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="form-label text-xs flex justify-between">
                    <span>Progres</span>
                    <span class="font-bold text-primary-600" id="quProgresLabel">0%</span>
                </label>
                <input type="range" name="progres" id="quProgres" min="0" max="100" value="0" 
                    class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-primary-600"
                    oninput="document.getElementById('quProgresLabel').textContent = this.value + '%'">
                <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                    <span>0%</span><span>50%</span><span>100%</span>
                </div>
            </div>
            
            <div id="quFeedback" class="hidden"></div>
            
            <div class="flex gap-2 pt-2">
                <button type="button" onclick="closeQuickUpdate()" class="flex-1 btn-secondary justify-center text-xs">Batal</button>
                <button type="submit" class="flex-1 btn-primary justify-center text-xs" id="quSubmitBtn">
                    <i class="bi bi-check-lg"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openQuickUpdate(id, nama, status, progres) {
    document.getElementById('quId').value = id;
    document.getElementById('quTitle').textContent = nama;
    document.getElementById('quStatus').value = status;
    document.getElementById('quProgres').value = progres;
    document.getElementById('quProgresLabel').textContent = progres + '%';
    document.getElementById('quFeedback').classList.add('hidden');
    document.getElementById('quSubmitBtn').disabled = false;
    document.getElementById('quSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Simpan';
    document.getElementById('quickUpdateModal').classList.remove('hidden');
}

function closeQuickUpdate() {
    document.getElementById('quickUpdateModal').classList.add('hidden');
}

document.getElementById('quickUpdateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('quId').value;
    const status = document.getElementById('quStatus').value;
    const progres = document.getElementById('quProgres').value;
    const btn = document.getElementById('quSubmitBtn');
    const feedback = document.getElementById('quFeedback');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i> Menyimpan...';
    
    // Update status
    fetch('<?= BASE_URL ?>/api/proyek/quick-update.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({id: id, field: 'status', value: status})
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error(data.message);
        // Update progres
        return fetch('<?= BASE_URL ?>/api/proyek/quick-update.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({id: id, field: 'progres', value: progres})
        });
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error(data.message);
        
        feedback.className = 'p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-xs flex items-center gap-2';
        feedback.innerHTML = '<i class="bi bi-check-circle-fill"></i> Berhasil diperbarui!';
        feedback.classList.remove('hidden');
        
        // Update UI baris tabel
        location.reload();
    })
    .catch(err => {
        feedback.className = 'p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-xs flex items-center gap-2';
        feedback.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> ' + err.message;
        feedback.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Simpan';
    });
});

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeQuickUpdate(); });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>