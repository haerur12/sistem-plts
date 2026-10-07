<?php
// pages/proyek/index.php
$pageTitle   = 'Manajemen Proyek';
$currentPage = 'proyek';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);

$db = db();

// Filter
$filterStatus = $_GET['status'] ?? '';
$search       = $_GET['search'] ?? '';

$where  = ['1=1'];
$params = [];
if ($filterStatus) { $where[] = 'p.status = ?';          $params[] = $filterStatus; }
if ($search)       { $where[] = 'p.nama_proyek LIKE ?';  $params[] = "%$search%"; }

$whereStr = implode(' AND ', $where);

// Pagination
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

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-bold text-slate-900 tracking-tight">Daftar Proyek</h2>
        <p class="text-[13px] text-slate-500 mt-1"><?= $totalRows ?> total proyek</p>
    </div>
    <?php if (isAdmin()): ?>
    <a href="<?= BASE_URL ?>/pages/proyek/create.php" class="btn-primary">
        <i class="bi bi-plus-lg"></i> Tambah Proyek
    </a>
    <?php endif; ?>
</div>

<!-- Filter & Search -->
<div class="card p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3">
        <div class="flex-1 min-w-48">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="bi bi-search text-slate-400 text-sm"></i>
                </div>
                <input type="text" name="search" value="<?= sanitize($search) ?>"
                    placeholder="Cari nama proyek..."
                    class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-4 focus:ring-primary-500/15 focus:border-primary-500 transition-all">
            </div>
        </div>
        <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
            <option value="">Semua Status</option>
            <?php foreach (['survei','perencanaan','instalasi','selesai','ditunda'] as $st): ?>
            <option value="<?= $st ?>" <?= $filterStatus === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary">
            <i class="bi bi-funnel"></i> Filter
        </button>
        <?php if ($filterStatus || $search): ?>
        <a href="<?= BASE_URL ?>/pages/proyek/index.php" class="btn-secondary">
            <i class="bi bi-x-lg"></i> Reset
        </a>
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
                    <th class="table-header">Lokasi</th>
                    <th class="table-header">Tanggal</th>
                    <th class="table-header">Status</th>
                    <th class="table-header">Progres</th>
                    <th class="table-header">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($proyeks)): ?>
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center">
                        <span class="empty-icon mb-3"><i class="bi bi-diagram-3"></i></span>
                        <p class="text-sm text-slate-400">Belum ada data proyek</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($proyeks as $i => $p): ?>
                <tr class="table-row">
                    <td class="table-cell text-slate-400"><?= $offset + $i + 1 ?></td>
                    <td class="table-cell">
                        <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $p['id'] ?>"
                            class="font-medium text-slate-800 hover:text-primary-600">
                            <?= sanitize($p['nama_proyek']) ?>
                        </a>
                    </td>
                    <td class="table-cell text-slate-500"><?= sanitize($p['nama_pelanggan']) ?></td>
                    <td class="table-cell">
                        <span class="text-slate-500 text-xs"><?= mb_strimwidth(sanitize($p['lokasi']), 0, 35, '...') ?></span>
                    </td>
                    <td class="table-cell">
                        <div class="text-xs text-slate-500">
                            <?= formatTanggalShort($p['tanggal_mulai']) ?><br>
                            <span class="text-slate-400">s/d <?= formatTanggalShort($p['tanggal_selesai']) ?></span>
                        </div>
                    </td>
                    <td class="table-cell"><?= statusBadge($p['status']) ?></td>
                    <td class="table-cell">
                        <div class="flex items-center gap-2 min-w-20">
                            <div class="flex-1 h-1.5 progress-track rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-primary-500 to-primary-600 rounded-full progress-fill" style="width:<?= $p['progres'] ?>%"></div>
                            </div>
                            <span class="text-xs text-slate-500 w-8 text-right"><?= $p['progres'] ?>%</span>
                        </div>
                    </td>
                    <td class="table-cell">
                        <div class="flex items-center gap-1">
                            <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $p['id'] ?>"
                                class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-primary-600 transition-colors" title="Detail">
                                <i class="bi bi-eye text-sm"></i>
                            </a>
                            <?php if (isAdminOrManajer()): ?>
                            <a href="<?= BASE_URL ?>/pages/proyek/edit.php?id=<?= $p['id'] ?>"
                                class="p-1.5 rounded-lg hover:bg-amber-50 text-slate-500 hover:text-amber-600 transition-colors" title="Edit">
                                <i class="bi bi-pencil text-sm"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (isAdmin()): ?>
                            <button onclick="confirmDelete(<?= $p['id'] ?>, '<?= addslashes($p['nama_proyek']) ?>')"
                                class="p-1.5 rounded-lg hover:bg-red-50 text-slate-500 hover:text-red-600 transition-colors" title="Hapus">
                                <i class="bi bi-trash text-sm"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between">
        <p class="text-xs text-slate-400">
            Menampilkan <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalRows) ?> dari <?= $totalRows ?> data
        </p>
        <div class="flex gap-1">
            <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
            <a href="?page=<?= $pg ?>&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($search) ?>"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-xs transition-colors
                <?= $pg === $page ? 'bg-primary-600 text-white shadow-sm shadow-primary-600/30' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                <?= $pg ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="modalHapus" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden" onclick="closeModal()">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm mx-4" onclick="event.stopPropagation()">
        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-exclamation-triangle text-red-600 text-xl"></i>
        </div>
        <h3 class="text-center font-semibold text-slate-800 mb-2">Hapus Proyek?</h3>
        <p class="text-center text-sm text-slate-500 mb-6" id="modalDesc">Apakah Anda yakin ingin menghapus proyek ini?</p>
        <div class="flex gap-3">
            <button onclick="closeModal()" class="flex-1 btn-secondary justify-center">Batal</button>
            <form method="POST" action="<?= BASE_URL ?>/api/proyek/delete.php" class="flex-1">
                <input type="hidden" name="id" id="deleteId">
                <button type="submit" class="w-full btn-danger justify-center">
                    <i class="bi bi-trash"></i> Hapus
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, nama) {
    document.getElementById('deleteId').value  = id;
    document.getElementById('modalDesc').textContent = `Proyek "${nama}" akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`;
    document.getElementById('modalHapus').classList.remove('hidden');
}
function closeModal() {
    document.getElementById('modalHapus').classList.add('hidden');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>