<?php
$pageTitle   = 'Detail Laporan';
$currentPage = 'laporan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek','teknisi_lapangan']);

$id   = (int)($_GET['id'] ?? 0);
$db   = db();
$user = currentUser();

$stmt = $db->prepare("
    SELECT l.*, p.nama_proyek, u.nama as nama_teknisi, u.role as role_teknisi
    FROM laporan_lapangan l
    JOIN proyek p ON l.proyek_id = p.id
    JOIN users u ON l.user_id = u.id
    WHERE l.id = ?
");
$stmt->execute([$id]);
$laporan = $stmt->fetch();
if (!$laporan) { setFlash('error','Laporan tidak ditemukan.'); redirect(BASE_URL.'/pages/laporan/index.php'); }

// Cek akses — teknisi hanya bisa lihat milik sendiri
if (isTeknisi() && $laporan['user_id'] !== (int)$user['id']) {
    setFlash('error','Anda tidak memiliki akses ke laporan ini.');
    redirect(BASE_URL.'/pages/laporan/index.php');
}

$fotos = $db->prepare("SELECT * FROM foto_lapangan WHERE laporan_id = ? ORDER BY id ASC");
$fotos->execute([$id]);
$fotos = $fotos->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-3xl mx-auto">
    <!-- Header -->
    <div class="flex items-start justify-between mb-5">
        <div class="flex items-center gap-3">
            <a href="<?= BASE_URL ?>/pages/laporan/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <i class="bi bi-arrow-left text-slate-500"></i>
            </a>
            <div>
                <h2 class="text-lg font-semibold text-slate-800"><?= sanitize($laporan['judul_laporan']) ?></h2>
                <p class="text-sm text-slate-400"><?= sanitize($laporan['nama_proyek']) ?></p>
            </div>
        </div>
        <?= statusBadge($laporan['status_pengerjaan']) ?>
    </div>

    <div class="space-y-4">
        <!-- Info Laporan -->
        <div class="card p-5">
            <div class="grid grid-cols-3 gap-4 mb-5">
                <div class="text-center p-3 bg-slate-50 rounded-xl">
                    <i class="bi bi-person text-primary-600 text-xl block mb-1"></i>
                    <div class="text-sm font-medium text-slate-800"><?= sanitize($laporan['nama_teknisi']) ?></div>
                    <div class="text-xs text-slate-400">Teknisi</div>
                </div>
                <div class="text-center p-3 bg-slate-50 rounded-xl">
                    <i class="bi bi-calendar-check text-green-600 text-xl block mb-1"></i>
                    <div class="text-sm font-medium text-slate-800"><?= formatTanggal($laporan['tanggal_laporan']) ?></div>
                    <div class="text-xs text-slate-400">Tanggal Laporan</div>
                </div>
                <div class="text-center p-3 bg-slate-50 rounded-xl">
                    <i class="bi bi-images text-blue-600 text-xl block mb-1"></i>
                    <div class="text-sm font-medium text-slate-800"><?= count($fotos) ?> foto</div>
                    <div class="text-xs text-slate-400">Dokumentasi</div>
                </div>
            </div>

            <div>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Catatan Lapangan</h3>
                <div class="bg-slate-50 rounded-xl p-4 text-sm text-slate-700 leading-relaxed whitespace-pre-line">
                    <?= sanitize($laporan['deskripsi']) ?>
                </div>
            </div>
        </div>

        <!-- Gallery Foto -->
        <?php if (!empty($fotos)): ?>
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <i class="bi bi-images text-blue-500"></i>
                Dokumentasi Foto (<?= count($fotos) ?> foto)
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" id="gallery">
                <?php foreach ($fotos as $f): ?>
                <div class="group relative aspect-square cursor-pointer overflow-hidden rounded-xl border border-slate-100"
                    onclick="openLightbox('<?= BASE_URL ?>/uploads/<?= sanitize($f['path_file']) ?>')">
                    <img src="<?= BASE_URL ?>/uploads/<?= sanitize($f['path_file']) ?>"
                        alt="<?= sanitize($f['nama_file']) ?>"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'">
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center">
                        <i class="bi bi-arrows-fullscreen text-white text-xl opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Aksi -->
        <div class="flex gap-3">
            <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $laporan['proyek_id'] ?>" class="btn-secondary flex-1 justify-center">
                <i class="bi bi-diagram-3"></i> Lihat Proyek
            </a>
            <?php if (isAdmin() || $laporan['user_id'] === (int)$user['id']): ?>
            <form method="POST" action="<?= BASE_URL ?>/api/laporan/delete.php" class="flex-1"
                onsubmit="return confirm('Hapus laporan ini beserta semua foto?')">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn-danger w-full justify-center">
                    <i class="bi bi-trash"></i> Hapus Laporan
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Lightbox -->
<div id="lightbox" class="fixed inset-0 bg-black/90 z-50 hidden flex items-center justify-center p-4" onclick="closeLightbox()">
    <button class="absolute top-4 right-4 text-white text-2xl hover:text-slate-300 transition-colors" onclick="closeLightbox()">
        <i class="bi bi-x-lg"></i>
    </button>
    <img id="lightboxImg" src="" alt="Preview"
        class="max-w-full max-h-full object-contain rounded-lg"
        onclick="event.stopPropagation()">
</div>

<script>
function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.add('hidden');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>