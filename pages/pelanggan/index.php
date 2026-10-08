<?php
$pageTitle   = 'Pelanggan';
$currentPage = 'pelanggan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$search = trim($_GET['search'] ?? '');
$sql = 'SELECT * FROM pelanggan';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE nama_pelanggan LIKE ? OR email LIKE ? OR no_telp LIKE ?';
    $term = '%' . $search . '%';
    $params = [$term, $term, $term];
}
$sql .= ' ORDER BY nama_pelanggan ASC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$pelanggan = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <h2 class="text-lg font-bold text-slate-900 tracking-tight">Data Pelanggan</h2>
        <p class="text-[13px] text-slate-500 mt-1"><?= count($pelanggan) ?> pelanggan</p>
    </div>
    <a href="<?= BASE_URL ?>/pages/pelanggan/create.php" class="btn-primary">
        <i class="bi bi-plus-lg"></i> Tambah Pelanggan
    </a>
</div>

<div class="card p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="search" name="search" value="<?= sanitize($search) ?>"
            placeholder="Cari nama, email, atau nomor telepon..."
            class="form-input flex-1 min-w-48">
        <button type="submit" class="btn-primary"><i class="bi bi-search"></i> Cari</button>
        <?php if ($search !== ''): ?>
        <a href="<?= BASE_URL ?>/pages/pelanggan/index.php" class="btn-secondary">Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="table-header">Nama Pelanggan</th>
                    <th class="table-header">Telepon</th>
                    <th class="table-header">Email</th>
                    <th class="table-header">Alamat</th>
                    <th class="table-header text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$pelanggan): ?>
                <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">
                    <?= $search !== '' ? 'Pelanggan tidak ditemukan.' : 'Belum ada data pelanggan.' ?>
                </td></tr>
                <?php else: ?>
                <?php foreach ($pelanggan as $pl): ?>
                <tr class="table-row">
                    <td class="table-cell font-semibold"><?= sanitize($pl['nama_pelanggan']) ?></td>
                    <td class="table-cell"><?= sanitize($pl['no_telp'] ?? '') ?></td>
                    <td class="table-cell"><?= sanitize($pl['email'] ?? '') ?></td>
                    <td class="table-cell max-w-sm truncate"><?= sanitize($pl['alamat'] ?? '') ?></td>
                    <td class="table-cell text-right whitespace-nowrap">
                        <a href="<?= BASE_URL ?>/pages/pelanggan/edit.php?id=<?= (int)$pl['id'] ?>"
                            class="inline-flex p-2 rounded-lg hover:bg-amber-50 text-slate-400 hover:text-amber-600"
                            aria-label="Edit pelanggan">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="<?= BASE_URL ?>/api/pelanggan/delete.php" class="inline"
                            onsubmit="return confirm('Hapus pelanggan ini?')">
                            <input type="hidden" name="id" value="<?= (int)$pl['id'] ?>">
                            <button type="submit" class="inline-flex p-2 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-600"
                                aria-label="Hapus pelanggan">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>