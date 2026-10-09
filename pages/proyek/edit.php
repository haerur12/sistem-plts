<?php
// pages/proyek/edit.php
$pageTitle   = 'Edit Proyek';
$currentPage = 'proyek';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek']);

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','ID tidak valid.'); redirect(BASE_URL.'/pages/proyek/index.php'); }

$db = db();
$stmt = $db->prepare("SELECT p.*, pl.nama_pelanggan FROM proyek p JOIN pelanggan pl ON p.pelanggan_id = pl.id WHERE p.id = ?");
$stmt->execute([$id]);
$proyek = $stmt->fetch();
if (!$proyek) { setFlash('error','Proyek tidak ditemukan.'); redirect(BASE_URL.'/pages/proyek/index.php'); }

$pelanggans = $db->query("SELECT * FROM pelanggan ORDER BY nama_pelanggan ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $id ?>" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-bold text-slate-900 tracking-tight">Edit Proyek</h2>
            <p class="text-sm text-slate-400"><?= sanitize($proyek['nama_proyek']) ?></p>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/proyek/update.php">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="space-y-5">

                <?php if (isAdmin()): ?>
                <div>
                    <label class="form-label">Pelanggan <span class="text-red-500">*</span></label>
                    <select name="pelanggan_id" class="form-select" required>
                        <?php foreach ($pelanggans as $pl): ?>
                        <option value="<?= $pl['id'] ?>" <?= $proyek['pelanggan_id'] == $pl['id'] ? 'selected' : '' ?>>
                            <?= sanitize($pl['nama_pelanggan']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Nama Proyek <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_proyek" class="form-input"
                        value="<?= sanitize($proyek['nama_proyek']) ?>" required>
                </div>

                <div>
                    <label class="form-label">Lokasi <span class="text-red-500">*</span></label>
                    <textarea name="lokasi" class="form-input" rows="2" required><?= sanitize($proyek['lokasi']) ?></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Latitude</label>
                        <input type="number" name="latitude" class="form-input" step="any" min="-90" max="90"
                            value="<?= $proyek['latitude'] !== null ? sanitize((string)$proyek['latitude']) : '' ?>"
                            placeholder="-6.2088">
                    </div>
                    <div>
                        <label class="form-label">Longitude</label>
                        <input type="number" name="longitude" class="form-input" step="any" min="-180" max="180"
                            value="<?= $proyek['longitude'] !== null ? sanitize((string)$proyek['longitude']) : '' ?>"
                            placeholder="106.8456">
                    </div>
                </div>
                <p class="text-xs text-slate-400 -mt-3">Isi kedua koordinat agar lokasi proyek dapat ditampilkan di peta.</p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-input" value="<?= $proyek['tanggal_mulai'] ?>" required>
                    </div>
                    <div>
                        <label class="form-label">Target Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-input" value="<?= $proyek['tanggal_selesai'] ?>" required>
                    </div>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['survei','perencanaan','instalasi','selesai','ditunda'] as $st): ?>
                            <option value="<?= $st ?>" <?= $proyek['status'] === $st ? 'selected' : '' ?>>
                                <?= ucfirst($st) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Progres (%)</label>
                        <input type="number" name="progres" class="form-input"
                            value="<?= $proyek['progres'] ?>" min="0" max="100">
                    </div>
                </div>

                <?php if (isAdmin()): ?>
                <div>
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" class="form-input" rows="3"><?= sanitize($proyek['deskripsi'] ?? '') ?></textarea>
                </div>
                <?php endif; ?>

                <div class="flex gap-3 pt-2">
                    <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $id ?>" class="btn-secondary flex-1 justify-center">Batal</a>
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="bi bi-check-lg"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>