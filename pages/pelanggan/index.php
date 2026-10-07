<?php
$pageTitle   = 'Data Pelanggan';
$currentPage = 'pelanggan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$db     = db();
$search = $_GET['search'] ?? '';
$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = 'nama_pelanggan LIKE ?'; $params[] = "%$search%"; }
$whereStr = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM pelanggan WHERE $whereStr");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("
    SELECT p.*, COUNT(pr.id) as total_proyek
    FROM pelanggan p
    LEFT JOIN proyek pr ON p.id = pr.pelanggan_id
    WHERE $whereStr
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$pelanggans = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">Data Pelanggan</h2>
        <p class="text-sm text-slate-400 mt-0.5"><?= $totalRows ?> total pelanggan</p>
    </div>
    <a href="<?= BASE_URL ?>/pages/pelanggan/create.php" class="btn-primary">
        <i class="bi bi-person-plus"></i> Tambah Pelanggan
    </a>
</div>

<!-- Search -->
<div class="card p-4 mb-4">
    <form method="GET" class="flex gap-3">
        <div class="flex-1 relative">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" name="search" value="<?= sanitize($search) ?>"
                placeholder="Cari nama pelanggan..."
                class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <button type="submit" class="btn-primary"><i class="bi bi-funnel"></i> Cari</button>
        <?php if ($search): ?>
        <a href="<?= BASE_URL ?>/pages/pelanggan/index.php" class="btn-secondary"><i class="bi bi-x-lg"></i> Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="table-header">No</th>
                    <th class="table-header">Nama Pelanggan</th>
                    <th class="table-header">No. Telepon</th>
                    <th class="table-header">Email</th>
                    <th class="table-header">Alamat</th>
                    <th class="table-header">Total Proyek</th>
                    <th class="table-header">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php if (empty($pelanggans)): ?>
                <tr><td colspan="7" class="px-4 py-12 text-center">
                    <i class="bi bi-people text-4xl text-slate-200 block mb-2"></i>
                    <p class="text-sm text-slate-400">Belum ada data pelanggan</p>
                </td></tr>
                <?php else: ?>
                <?php foreach ($pelanggans as $i => $p): ?>
                <tr class="table-row">
                    <td class="table-cell text-slate-400"><?= $offset + $i + 1 ?></td>
                    <td class="table-cell">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-semibold text-sm flex-shrink-0">
                                <?= strtoupper(substr($p['nama_pelanggan'], 0, 1)) ?>
                            </div>
                            <span class="font-medium text-slate-800"><?= sanitize($p['nama_pelanggan']) ?></span>
                        </div>
                    </td>
                    <td class="table-cell text-sm"><?= sanitize($p['no_telp']) ?></td>
                    <td class="table-cell">
                        <?php if ($p['email']): ?>
                        <a href="mailto:<?= sanitize($p['email']) ?>" class="text-primary-700 hover:underline text-sm">
                            <?= sanitize($p['email']) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-slate-300 text-sm">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="table-cell">
                        <span class="text-xs text-slate-500"><?= mb_strimwidth(sanitize($p['alamat']), 0, 40, '...') ?></span>
                    </td>
                    <td class="table-cell">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                            <?= $p['total_proyek'] ?> proyek
                        </span>
                    </td>
                    <td class="table-cell">
                        <div class="flex items-center gap-1">
                            <a href="<?= BASE_URL ?>/pages/pelanggan/edit.php?id=<?= $p['id'] ?>"
                                class="p-1.5 rounded-lg hover:bg-amber-50 text-slate-400 hover:text-amber-600 transition-colors" title="Edit">
                                <i class="bi bi-pencil text-sm"></i>
                            </a>
                            <form method="POST" action="<?= BASE_URL ?>/api/pelanggan/delete.php" class="inline"
                                onsubmit="return confirm('Hapus pelanggan <?= addslashes($p['nama_pelanggan']) ?>?')">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button class="p-1.5 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-600 transition-colors" title="Hapus">
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

    <?php if ($totalPages > 1): ?>
    <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between">
        <p class="text-xs text-slate-400">Menampilkan <?= $offset+1 ?>–<?= min($offset+$perPage,$totalRows) ?> dari <?= $totalRows ?></p>
        <div class="flex gap-1">
            <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
            <a href="?page=<?= $pg ?>&search=<?= urlencode($search) ?>"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-xs transition-colors
                <?= $pg === $page ? 'bg-primary-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                <?= $pg ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>