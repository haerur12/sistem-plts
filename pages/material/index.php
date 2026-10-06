<?php
// pages/material/index.php
$pageTitle   = 'Penggunaan Material';
$currentPage = 'material';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','gudang']);

$db      = db();
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$total      = $db->query("SELECT COUNT(*) FROM penggunaan_material")->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $db->prepare("
    SELECT pm.*, k.nama_komponen, k.satuan, p.nama_proyek, u.nama as nama_user
    FROM penggunaan_material pm
    JOIN komponen k ON pm.komponen_id = k.id
    JOIN proyek p ON pm.proyek_id = p.id
    JOIN users u ON pm.user_id = u.id
    ORDER BY pm.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute();
$materials = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">Penggunaan Material</h2>
        <p class="text-sm text-slate-400 mt-0.5"><?= $total ?> total catatan</p>
    </div>
    <a href="<?= BASE_URL ?>/pages/material/create.php" class="btn-primary">
        <i class="bi bi-plus-lg"></i> Catat Penggunaan
    </a>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="table-header">No</th>
                    <th class="table-header">Komponen</th>
                    <th class="table-header">Proyek</th>
                    <th class="table-header">Jumlah</th>
                    <th class="table-header">Dicatat Oleh</th>
                    <th class="table-header">Tanggal</th>
                    <th class="table-header">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php if (empty($materials)): ?>
                <tr><td colspan="7" class="px-4 py-12 text-center">
                    <i class="bi bi-inbox text-4xl text-slate-200 block mb-2"></i>
                    <p class="text-sm text-slate-400">Belum ada catatan penggunaan material</p>
                </td></tr>
                <?php else: ?>
                <?php foreach ($materials as $i => $m): ?>
                <tr class="table-row">
                    <td class="table-cell text-slate-400"><?= $offset+$i+1 ?></td>
                    <td class="table-cell font-medium"><?= sanitize($m['nama_komponen']) ?></td>
                    <td class="table-cell text-slate-500 text-xs"><?= sanitize($m['nama_proyek']) ?></td>
                    <td class="table-cell">
                        <span class="text-red-600 font-semibold">-<?= $m['jumlah_digunakan'] ?></span>
                        <span class="text-xs text-slate-400"> <?= sanitize($m['satuan']) ?></span>
                    </td>
                    <td class="table-cell text-xs text-slate-500"><?= sanitize($m['nama_user']) ?></td>
                    <td class="table-cell text-xs"><?= formatTanggalShort($m['tanggal_penggunaan']) ?></td>
                    <td class="table-cell text-xs text-slate-400"><?= sanitize($m['keterangan'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between">
        <p class="text-xs text-slate-400">Menampilkan <?= $offset+1 ?>–<?= min($offset+$perPage,$total) ?> dari <?= $total ?></p>
        <div class="flex gap-1">
            <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
            <a href="?page=<?= $pg ?>"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-xs
                <?= $pg === $page ? 'bg-primary-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                <?= $pg ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>