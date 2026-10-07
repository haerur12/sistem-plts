<?php
// pages/jadwal/index.php
$pageTitle   = 'Jadwal Instalasi';
$currentPage = 'jadwal';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);

$db      = db();
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$total      = $db->query("SELECT COUNT(*) FROM jadwal")->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $db->prepare("
    SELECT j.*, p.nama_proyek, pl.nama_pelanggan
    FROM jadwal j
    JOIN proyek p ON j.proyek_id = p.id
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    ORDER BY j.tanggal_mulai DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute();
$jadwals = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-bold text-slate-900 tracking-tight">Jadwal Instalasi</h2>
        <p class="text-[13px] text-slate-500 mt-1"><?= $total ?> total jadwal</p>
    </div>
    <a href="<?= BASE_URL ?>/pages/jadwal/create.php" class="btn-primary">
        <i class="bi bi-calendar-plus"></i> Tambah Jadwal
    </a>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="table-header">No</th>
                    <th class="table-header">Proyek</th>
                    <th class="table-header">Mulai</th>
                    <th class="table-header">Selesai</th>
                    <th class="table-header">Keterangan</th>
                    <th class="table-header">Status</th>
                    <th class="table-header">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($jadwals)): ?>
                <tr><td colspan="7" class="px-4 py-12 text-center">
                    <i class="bi bi-calendar-x text-4xl text-slate-200 block mb-2"></i>
                    <p class="text-sm text-slate-400">Belum ada jadwal instalasi</p>
                </td></tr>
                <?php else: ?>
                <?php foreach ($jadwals as $i => $j): ?>
                <tr class="table-row">
                    <td class="table-cell text-slate-400"><?= $offset+$i+1 ?></td>
                    <td class="table-cell">
                        <div class="font-medium text-sm"><?= sanitize($j['nama_proyek']) ?></div>
                        <div class="text-xs text-slate-400"><?= sanitize($j['nama_pelanggan']) ?></div>
                    </td>
                    <td class="table-cell text-xs"><?= formatTanggalShort($j['tanggal_mulai']) ?></td>
                    <td class="table-cell text-xs"><?= formatTanggalShort($j['tanggal_selesai']) ?></td>
                    <td class="table-cell text-xs text-slate-500"><?= sanitize($j['keterangan'] ?? '-') ?></td>
                    <td class="table-cell"><?= statusBadge($j['status']) ?></td>
                    <td class="table-cell">
                        <div class="flex gap-1">
                            <a href="<?= BASE_URL ?>/pages/jadwal/edit.php?id=<?= $j['id'] ?>"
                                class="p-1.5 rounded-lg hover:bg-amber-50 text-slate-400 hover:text-amber-600 transition-colors">
                                <i class="bi bi-pencil text-sm"></i>
                            </a>
                            <form method="POST" action="<?= BASE_URL ?>/api/jadwal/delete.php" class="inline"
                                onsubmit="return confirm('Hapus jadwal ini?')">
                                <input type="hidden" name="id" value="<?= $j['id'] ?>">
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>