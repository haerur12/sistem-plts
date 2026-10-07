<?php
// pages/proyek/show.php
$pageTitle   = 'Detail Proyek';
$currentPage = 'proyek';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','ID tidak valid.'); redirect(BASE_URL.'/pages/proyek/index.php'); }

$db = db();

$stmt = $db->prepare("
    SELECT p.*, pl.nama_pelanggan, pl.no_telp, pl.email as email_pelanggan
    FROM proyek p
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$proyek = $stmt->fetch();
if (!$proyek) { setFlash('error','Proyek tidak ditemukan.'); redirect(BASE_URL.'/pages/proyek/index.php'); }

// Jadwal
$jadwals = $db->prepare("SELECT * FROM jadwal WHERE proyek_id = ? ORDER BY tanggal_mulai ASC");
$jadwals->execute([$id]);
$jadwals = $jadwals->fetchAll();

// Laporan lapangan
$laporans = $db->prepare("
    SELECT l.*, u.nama as nama_teknisi,
    (SELECT COUNT(*) FROM foto_lapangan f WHERE f.laporan_id = l.id) as jumlah_foto
    FROM laporan_lapangan l
    JOIN users u ON l.user_id = u.id
    WHERE l.proyek_id = ?
    ORDER BY l.created_at DESC
");
$laporans->execute([$id]);
$laporans = $laporans->fetchAll();

// Material
$materials = $db->prepare("
    SELECT pm.*, k.nama_komponen, k.satuan, u.nama as nama_user
    FROM penggunaan_material pm
    JOIN komponen k ON pm.komponen_id = k.id
    JOIN users u ON pm.user_id = u.id
    WHERE pm.proyek_id = ?
    ORDER BY pm.created_at DESC
");
$materials->execute([$id]);
$materials = $materials->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Header -->
<div class="flex items-start justify-between mb-5">
    <div class="flex items-center gap-3">
        <a href="<?= BASE_URL ?>/pages/proyek/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-bold text-slate-900 tracking-tight"><?= sanitize($proyek['nama_proyek']) ?></h2>
            <p class="text-sm text-slate-400"><?= sanitize($proyek['nama_pelanggan']) ?></p>
        </div>
    </div>
    <div class="flex gap-2">
        <?php if (isAdminOrManajer()): ?>
        <a href="<?= BASE_URL ?>/pages/proyek/edit.php?id=<?= $id ?>" class="btn-secondary btn-sm">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Info Proyek -->
    <div class="space-y-4">
        <div class="card p-5">
            <h3 class="text-sm font-bold text-slate-800 tracking-tight mb-4">Informasi Proyek</h3>

            <!-- Progres Bar -->
            <div class="mb-5">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs text-slate-500">Progres Pengerjaan</span>
                    <span class="text-sm font-bold text-primary-700"><?= $proyek['progres'] ?>%</span>
                </div>
                <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-primary-500 to-primary-600 rounded-full progress-fill" style="width:<?= $proyek['progres'] ?>%"></div>
                </div>
                <div class="mt-2"><?= statusBadge($proyek['status']) ?></div>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex gap-2">
                    <i class="bi bi-geo-alt text-slate-400 mt-0.5 flex-shrink-0"></i>
                    <span class="text-slate-600"><?= sanitize($proyek['lokasi']) ?></span>
                </div>
                <div class="flex gap-2">
                    <i class="bi bi-calendar-event text-slate-400 flex-shrink-0"></i>
                    <span class="text-slate-600"><?= formatTanggal($proyek['tanggal_mulai']) ?></span>
                </div>
                <div class="flex gap-2">
                    <i class="bi bi-calendar-check text-slate-400 flex-shrink-0"></i>
                    <span class="text-slate-600"><?= formatTanggal($proyek['tanggal_selesai']) ?></span>
                </div>
                <div class="flex gap-2">
                    <i class="bi bi-person text-slate-400 flex-shrink-0"></i>
                    <span class="text-slate-600"><?= sanitize($proyek['nama_pelanggan']) ?></span>
                </div>
                <?php if ($proyek['no_telp']): ?>
                <div class="flex gap-2">
                    <i class="bi bi-telephone text-slate-400 flex-shrink-0"></i>
                    <span class="text-slate-600"><?= sanitize($proyek['no_telp']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($proyek['deskripsi']): ?>
            <div class="mt-4 p-3 bg-slate-50 rounded-lg text-xs text-slate-500">
                <?= sanitize($proyek['deskripsi']) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Update Status (Teknisi & Manajer) -->
        <?php if (!isGudang()): ?>
        <div class="card p-5">
            <h3 class="text-sm font-bold text-slate-800 tracking-tight mb-4">Perbarui Status</h3>
            <form method="POST" action="<?= BASE_URL ?>/api/proyek/update.php">
                <input type="hidden" name="id" value="<?= $id ?>">
                <div class="space-y-3">
                    <div>
                        <label class="form-label text-xs">Status Pengerjaan</label>
                        <select name="status" class="form-select text-sm">
                            <?php foreach (['survei','perencanaan','instalasi','selesai','ditunda'] as $st): ?>
                            <option value="<?= $st ?>" <?= $proyek['status'] === $st ? 'selected' : '' ?>>
                                <?= ucfirst($st) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label text-xs">Progres (%)</label>
                        <input type="number" name="progres" class="form-input text-sm"
                            value="<?= $proyek['progres'] ?>" min="0" max="100">
                    </div>
                    <button type="submit" class="btn-primary w-full justify-center text-xs">
                        <i class="bi bi-arrow-repeat"></i> Perbarui Status
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Tambah Jadwal -->
        <?php if (isAdminOrManajer()): ?>
        <div>
            <a href="<?= BASE_URL ?>/pages/jadwal/create.php?proyek_id=<?= $id ?>" class="btn-secondary w-full justify-center">
                <i class="bi bi-calendar-plus"></i> Tambah Jadwal
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Konten Detail -->
    <div class="lg:col-span-2 space-y-4">
        <!-- Jadwal Instalasi -->
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800 tracking-tight">
                    <i class="bi bi-calendar3 text-primary-600 mr-1"></i> Jadwal Instalasi
                </h3>
                <span class="text-xs text-slate-400"><?= count($jadwals) ?> jadwal</span>
            </div>
            <?php if (empty($jadwals)): ?>
            <div class="px-5 py-8 text-center">
                <i class="bi bi-calendar-x text-3xl text-slate-200 block mb-2"></i>
                <p class="text-sm text-slate-400">Belum ada jadwal instalasi</p>
            </div>
            <?php else: ?>
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="table-header">Mulai</th>
                        <th class="table-header">Selesai</th>
                        <th class="table-header">Keterangan</th>
                        <th class="table-header">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($jadwals as $j): ?>
                    <tr class="table-row">
                        <td class="table-cell text-xs"><?= formatTanggalShort($j['tanggal_mulai']) ?></td>
                        <td class="table-cell text-xs"><?= formatTanggalShort($j['tanggal_selesai']) ?></td>
                        <td class="table-cell text-xs text-slate-500"><?= sanitize($j['keterangan'] ?? '-') ?></td>
                        <td class="table-cell"><?= statusBadge($j['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Laporan Lapangan -->
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800 tracking-tight">
                    <i class="bi bi-file-earmark-text text-green-600 mr-1"></i> Laporan Lapangan
                </h3>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-400"><?= count($laporans) ?> laporan</span>
                    <?php if (isTeknisi() || isAdminOrManajer()): ?>
                    <a href="<?= BASE_URL ?>/pages/laporan/create.php?proyek_id=<?= $id ?>" class="text-xs text-primary-600 hover:text-primary-700 font-medium">
                        <i class="bi bi-plus-lg"></i> Tambah
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (empty($laporans)): ?>
            <div class="px-5 py-8 text-center">
                <i class="bi bi-file-earmark-x text-3xl text-slate-200 block mb-2"></i>
                <p class="text-sm text-slate-400">Belum ada laporan lapangan</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($laporans as $l): ?>
                <div class="px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800"><?= sanitize($l['judul_laporan']) ?></p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                <?= sanitize($l['nama_teknisi']) ?> · <?= formatTanggal($l['tanggal_laporan']) ?>
                            </p>
                            <p class="text-xs text-slate-500 mt-1.5">
                                <?= mb_strimwidth(sanitize($l['deskripsi']), 0, 120, '...') ?>
                            </p>
                            <?php if ($l['jumlah_foto'] > 0): ?>
                            <p class="text-xs text-primary-600 mt-1.5">
                                <i class="bi bi-images"></i> <?= $l['jumlah_foto'] ?> foto dokumentasi
                            </p>
                            <?php endif; ?>
                        </div>
                        <?= statusBadge($l['status_pengerjaan']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Penggunaan Material -->
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800 tracking-tight">
                    <i class="bi bi-boxes text-amber-600 mr-1"></i> Penggunaan Material
                </h3>
                <span class="text-xs text-slate-400"><?= count($materials) ?> catatan</span>
            </div>
            <?php if (empty($materials)): ?>
            <div class="px-5 py-8 text-center">
                <i class="bi bi-inbox text-3xl text-slate-200 block mb-2"></i>
                <p class="text-sm text-slate-400">Belum ada catatan penggunaan material</p>
            </div>
            <?php else: ?>
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="table-header">Komponen</th>
                        <th class="table-header">Jumlah</th>
                        <th class="table-header">Dicatat Oleh</th>
                        <th class="table-header">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($materials as $m): ?>
                    <tr class="table-row">
                        <td class="table-cell text-sm font-medium"><?= sanitize($m['nama_komponen']) ?></td>
                        <td class="table-cell">
                            <span class="text-red-600 font-medium text-sm">-<?= $m['jumlah_digunakan'] ?></span>
                            <span class="text-xs text-slate-400"> <?= sanitize($m['satuan']) ?></span>
                        </td>
                        <td class="table-cell text-xs text-slate-500"><?= sanitize($m['nama_user']) ?></td>
                        <td class="table-cell text-xs text-slate-500"><?= formatTanggalShort($m['tanggal_penggunaan']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>