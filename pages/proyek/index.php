<?php
// pages/pelanggan/index.php — DENGAN BULK ACTIONS
$pageTitle   = 'Data Pelanggan';
$currentPage = 'pelanggan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$db      = db();
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search  = $_GET['search'] ?? '';

$where = ['1=1'];
$params = [];
if ($search) { $where[] = '(nama_pelanggan LIKE ? OR no_telp LIKE ? OR email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereStr = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM pelanggan WHERE $whereStr");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("
    SELECT p.*, (SELECT COUNT(*) FROM proyek WHERE pelanggan_id = p.id) as total_proyek
    FROM pelanggan p
    WHERE $whereStr
    ORDER BY p.nama_pelanggan ASC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$pelanggans = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-bold text-slate-900 tracking-tight">Data Pelanggan</h2>
        <p class="text-[13px] text-slate-500 mt-1"><?= $totalRows ?> total pelanggan</p>
    </div>
    <a href="<?= BASE_URL ?>/pages/pelanggan/create.php" class="btn-primary">
        <i class="bi bi-plus-lg"></i> Tambah Pelanggan
    </a>
</div>

<div class="card p-4 mb-4">
    <form method="GET" class="flex gap-3">
        <div class="flex-1">
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Cari nama, telepon, atau email..."
                    class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-4 focus:ring-primary-500/15 focus:border-primary-500 transition-all">
            </div>
        </div>
        <button type="submit" class="btn-primary"><i class="bi bi-funnel"></i> Cari</button>
        <?php if ($search): ?>
        <a href="<?= BASE_URL ?>/pages/pelanggan/index.php" class="btn-secondary"><i class="bi bi-x-lg"></i> Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Bulk Actions Bar -->
<div id="bulkBar" class="hidden mb-4 card p-3 bg-primary-50 border-primary-200">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-primary-600 text-white flex items-center justify-center">
                <i class="bi bi-check2-all text-sm"></i>
            </div>
            <span class="text-sm font-semibold text-primary-900">
                <span id="selectedCount">0</span> pelanggan dipilih
            </span>
        </div>
        <div class="flex gap-2">
            <button onclick="bulkExport()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition-colors">
                <i class="bi bi-download"></i> Export CSV
            </button>
            <button onclick="bulkDelete()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-semibold transition-colors">
                <i class="bi bi-trash"></i> Hapus Terpilih
            </button>
            <button onclick="clearSelection()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition-colors">
                <i class="bi bi-x-lg"></i> Batal
            </button>
        </div>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="table-header w-10">
                        <input type="checkbox" id="checkAll" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer">
                    </th>
                    <th class="table-header">No</th>
                    <th class="table-header">Nama Pelanggan</th>
                    <th class="table-header">Alamat</th>
                    <th class="table-header">Kontak</th>
                    <th class="table-header">Proyek</th>
                    <th class="table-header">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php if (empty($pelanggans)): ?>
                <tr><td colspan="7" class="px-4 py-12 text-center">
                    <i class="bi bi-people text-4xl text-slate-200 block mb-2"></i>
                    <p class="text-sm text-slate-400">Belum ada data pelanggan</p>
                </td></tr>
            <?php else: ?>
                <?php foreach ($pelanggans as $i => $p): ?>
                <tr class="table-row">
                    <td class="table-cell">
                        <input type="checkbox" class="row-check rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer" value="<?= $p['id'] ?>">
                    </td>
                    <td class="table-cell text-slate-400"><?= $offset+$i+1 ?></td>
                    <td class="table-cell">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-50 to-indigo-100 text-primary-700 flex items-center justify-center flex-shrink-0 text-xs font-bold">
                                <?= strtoupper(substr($p['nama_pelanggan'], 0, 2)) ?>
                            </div>
                            <div>
                                <div class="font-medium text-sm"><?= sanitize($p['nama_pelanggan']) ?></div>
                                <?php if ($p['email']): ?>
                                <div class="text-xs text-slate-400"><?= sanitize($p['email']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="table-cell text-xs text-slate-500"><?= mb_strimwidth(sanitize($p['alamat']), 0, 50, '...') ?></td>
                    <td class="table-cell text-xs">
                        <span class="inline-flex items-center gap-1 text-slate-600"><i class="bi bi-telephone"></i><?= sanitize($p['no_telp']) ?></span>
                    </td>
                    <td class="table-cell">
                        <span class="badge-modern inline-flex items-center rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200">
                            <?= $p['total_proyek'] ?> proyek
                        </span>
                    </td>
                    <td class="table-cell">
                        <div class="flex gap-1">
                            <a href="<?= BASE_URL ?>/pages/pelanggan/edit.php?id=<?= $p['id'] ?>" class="p-1.5 rounded-lg hover:bg-amber-50 text-slate-400 hover:text-amber-600 transition-colors">
                                <i class="bi bi-pencil text-sm"></i>
                            </a>
                            <form method="POST" action="<?= BASE_URL ?>/api/pelanggan/delete.php" class="inline" onsubmit="return confirm('Hapus pelanggan ini?')">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button class="p-1.5 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-600 transition-colors">
                                    <i class="bi bi-trash text-sm"></i>
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

<!-- Modal Bulk Delete -->
<div id="modalBulkDelete" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-exclamation-triangle text-red-600 text-xl"></i>
        </div>
        <h3 class="text-center font-bold text-slate-900 mb-2">Hapus Massal?</h3>
        <p class="text-center text-sm text-slate-500 mb-6">
            <span id="deleteCount">0</span> pelanggan akan dihapus permanen.
        </p>
        <form method="POST" action="<?= BASE_URL ?>/api/pelanggan/bulk.php">
            <input type="hidden" name="action" value="delete">
            <div id="deleteIds"></div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('modalBulkDelete').classList.add('hidden')" class="flex-1 btn-secondary justify-center">Batal</button>
                <button type="submit" class="flex-1 btn-danger justify-center"><i class="bi bi-trash"></i> Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
const checkAll = document.getElementById('checkAll');
const rowChecks = document.querySelectorAll('.row-check');
const bulkBar = document.getElementById('bulkBar');
const selectedCount = document.getElementById('selectedCount');

function updateBulkBar() {
    const checked = document.querySelectorAll('.row-check:checked');
    selectedCount.textContent = checked.length;
    bulkBar.classList.toggle('hidden', checked.length === 0);
    checkAll.checked = checked.length === rowChecks.length && rowChecks.length > 0;
}

checkAll.addEventListener('change', () => {
    rowChecks.forEach(c => c.checked = checkAll.checked);
    updateBulkBar();
});
rowChecks.forEach(c => c.addEventListener('change', updateBulkBar));

function getSelectedIds() {
    return [...document.querySelectorAll('.row-check:checked')].map(c => c.value);
}

function clearSelection() {
    rowChecks.forEach(c => c.checked = false);
    checkAll.checked = false;
    updateBulkBar();
}

function bulkExport() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    window.location.href = '<?= BASE_URL ?>/api/pelanggan/export.php?ids=' + ids.join(',');
    clearSelection();
}

function bulkDelete() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    document.getElementById('deleteCount').textContent = ids.length;
    document.getElementById('deleteIds').innerHTML = ids.map(id => `<input type="hidden" name="ids[]" value="${id}">`).join('');
    document.getElementById('modalBulkDelete').classList.remove('hidden');
}
</script>

<?php if ($totalPages > 1): ?>
<div class="flex items-center justify-between mt-4">
    <p class="text-xs text-slate-400">Menampilkan <?= $offset+1 ?>–<?= min($offset+$perPage,$totalRows) ?> dari <?= $totalRows ?></p>
    <div class="flex gap-1">
        <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
        <a href="?page=<?= $pg ?>&search=<?= urlencode($search) ?>"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-xs transition-colors
            <?= $pg === $page ? 'bg-primary-600 text-white shadow-sm shadow-primary-600/30' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            <?= $pg ?>
        </a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>