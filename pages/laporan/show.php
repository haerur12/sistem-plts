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
    SELECT l.*, p.nama_proyek, p.id as proyek_id,
           p.status as status_proyek, p.progres,
           u.nama as nama_teknisi, u.role as role_teknisi
    FROM laporan_lapangan l
    JOIN proyek p ON l.proyek_id = p.id
    JOIN users u ON l.user_id = u.id
    WHERE l.id = ?
");
$stmt->execute([$id]);
$laporan = $stmt->fetch();
if (!$laporan) { setFlash('error','Laporan tidak ditemukan.'); redirect(BASE_URL.'/pages/laporan/index.php'); }

if (isTeknisi() && $laporan['user_id'] !== (int)$user['id']) {
    setFlash('error','Anda tidak memiliki akses ke laporan ini.');
    redirect(BASE_URL.'/pages/laporan/index.php');
}

$fotos = $db->prepare("SELECT * FROM foto_lapangan WHERE laporan_id = ? ORDER BY id ASC");
$fotos->execute([$id]);
$fotos = $fotos->fetchAll();

// Laporan lain di proyek yang sama untuk navigasi
$laporanProyek = $db->prepare("
    SELECT l.id, l.judul_laporan, l.status_pengerjaan, l.tanggal_laporan
    FROM laporan_lapangan l
    WHERE l.proyek_id = ? ORDER BY l.created_at DESC LIMIT 5
");
$laporanProyek->execute([$laporan['proyek_id']]);
$laporanProyek = $laporanProyek->fetchAll();

// Komentar/catatan (gunakan tabel activity_log sebagai komentar)
// Buat tabel komentar_laporan jika belum ada
try {
    $db->exec("CREATE TABLE IF NOT EXISTS komentar_laporan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        laporan_id INT NOT NULL,
        user_id INT NOT NULL,
        komentar TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (laporan_id) REFERENCES laporan_lapangan(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
} catch(\Exception $e) {}

$komentars = $db->prepare("
    SELECT k.*, u.nama as nama_user, u.role as role_user
    FROM komentar_laporan k
    JOIN users u ON k.user_id = u.id
    WHERE k.laporan_id = ?
    ORDER BY k.created_at ASC
");
$komentars->execute([$id]);
$komentars = $komentars->fetchAll();

// Proses tambah komentar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['komentar'])) {
    $komentar = trim($_POST['komentar'] ?? '');
    if ($komentar) {
        $stmt = $db->prepare("INSERT INTO komentar_laporan (laporan_id, user_id, komentar) VALUES (?,?,?)");
        $stmt->execute([$id, $user['id'], $komentar]);

        // Notif ke teknisi jika yang komentar bukan teknisi
        if (!isTeknisi() && $laporan['user_id'] !== (int)$user['id']) {
            kirimNotifikasi($laporan['user_id'], 'Komentar Baru', $user['nama'].' menambahkan catatan di laporan "'.$laporan['judul_laporan'].'".');
        }
        setFlash('success','Komentar berhasil ditambahkan.');
        redirect(BASE_URL.'/pages/laporan/show.php?id='.$id.'#komentar');
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- ===== KOLOM KIRI — DETAIL ===== -->
    <div class="lg:col-span-2 space-y-4">

        <!-- Header Laporan -->
        <div class="card p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-file-earmark-text text-green-600 text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800"><?= sanitize($laporan['judul_laporan']) ?></h2>
                            <div class="flex flex-wrap items-center gap-3 mt-1.5">
                                <span class="flex items-center gap-1.5 text-xs text-slate-400">
                                    <i class="bi bi-diagram-3"></i>
                                    <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $laporan['proyek_id'] ?>"
                                        class="hover:text-primary-700 font-medium">
                                        <?= sanitize($laporan['nama_proyek']) ?>
                                    </a>
                                </span>
                                <span class="flex items-center gap-1.5 text-xs text-slate-400">
                                    <i class="bi bi-person"></i><?= sanitize($laporan['nama_teknisi']) ?>
                                </span>
                                <span class="flex items-center gap-1.5 text-xs text-slate-400">
                                    <i class="bi bi-calendar"></i><?= formatTanggal($laporan['tanggal_laporan']) ?>
                                </span>
                            </div>
                        </div>
                        <?= statusBadge($laporan['status_pengerjaan']) ?>
                    </div>

                    <!-- Stat mini -->
                    <div class="flex items-center gap-4 mt-4 pt-4 border-t border-slate-50">
                        <div class="text-center">
                            <div class="text-xl font-bold text-slate-800"><?= count($fotos) ?></div>
                            <div class="text-xs text-slate-400">Foto</div>
                        </div>
                        <div class="w-px h-8 bg-slate-100"></div>
                        <div class="text-center">
                            <div class="text-xl font-bold text-slate-800"><?= count($komentars) ?></div>
                            <div class="text-xs text-slate-400">Komentar</div>
                        </div>
                        <div class="w-px h-8 bg-slate-100"></div>
                        <div class="text-center">
                            <div class="text-sm font-bold text-slate-800"><?= timeAgo($laporan['created_at']) ?></div>
                            <div class="text-xs text-slate-400">Diunggah</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catatan Lapangan -->
        <div class="card p-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                <i class="bi bi-journal-text text-primary-600"></i>
                Catatan Lapangan
            </h3>
            <div class="bg-slate-50 rounded-xl p-4 text-sm text-slate-700 leading-relaxed whitespace-pre-line">
                <?= sanitize($laporan['deskripsi']) ?>
            </div>
        </div>

        <!-- Gallery Foto -->
        <?php if (!empty($fotos)): ?>
        <div class="card p-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <i class="bi bi-images text-blue-500"></i>
                Dokumentasi Foto
                <span class="ml-auto text-xs font-normal text-slate-400"><?= count($fotos) ?> foto</span>
            </h3>

            <!-- Grid Gallery -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                <?php foreach ($fotos as $i => $f): ?>
                <div class="relative aspect-square rounded-xl overflow-hidden group cursor-pointer bg-slate-100"
                    onclick="openLightbox(<?= $i ?>)">
                    <img src="<?= BASE_URL ?>/uploads/<?= sanitize($f['path_file']) ?>"
                        alt="Foto <?= $i+1 ?>"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        loading="lazy">
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center">
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity bg-white/90 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-800">
                            <i class="bi bi-arrows-fullscreen me-1"></i>Perbesar
                        </div>
                    </div>
                    <div class="absolute bottom-2 right-2 bg-black/60 text-white text-xs px-1.5 py-0.5 rounded">
                        <?= $i+1 ?>/<?= count($fotos) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Komentar / Feedback -->
        <div class="card p-6" id="komentar">
            <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <i class="bi bi-chat-dots text-purple-500"></i>
                Catatan & Feedback
                <span class="ml-auto text-xs font-normal text-slate-400"><?= count($komentars) ?> catatan</span>
            </h3>

            <!-- List Komentar -->
            <?php if (empty($komentars)): ?>
            <div class="text-center py-6">
                <i class="bi bi-chat text-3xl text-slate-200 block mb-2"></i>
                <p class="text-sm text-slate-400">Belum ada catatan atau feedback</p>
            </div>
            <?php else: ?>
            <div class="space-y-3 mb-5">
                <?php foreach ($komentars as $k):
                    $roleColors2 = ['admin'=>'bg-red-500','manajer_proyek'=>'bg-blue-500','teknisi_lapangan'=>'bg-green-500','gudang'=>'bg-purple-500'];
                    $kc = $roleColors2[$k['role_user']] ?? 'bg-slate-400';
                ?>
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded-full <?= $kc ?> flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        <?= strtoupper(substr($k['nama_user'],0,1)) ?>
                    </div>
                    <div class="flex-1">
                        <div class="bg-slate-50 rounded-xl rounded-tl-none px-4 py-3">
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="text-xs font-semibold text-slate-700"><?= sanitize($k['nama_user']) ?></span>
                                <?php
                                $rlabel = ['admin'=>'Admin','manajer_proyek'=>'Manajer','teknisi_lapangan'=>'Teknisi','gudang'=>'Gudang'];
                                $rl     = $rlabel[$k['role_user']] ?? $k['role_user'];
                                ?>
                                <span class="text-xs px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-500"><?= $rl ?></span>
                            </div>
                            <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line"><?= sanitize($k['komentar']) ?></p>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 ml-2"><?= timeAgo($k['created_at']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Form Tambah Komentar -->
            <form method="POST" action="?id=<?= $id ?>#komentar" class="flex gap-3">
                <?= csrfField() ?>
                <div class="w-8 h-8 rounded-full <?= $roleColors[$user['role']] ?? 'bg-slate-400' ?> flex items-center justify-center text-white text-xs font-bold flex-shrink-0 mt-1">
                    <?php
                    $roleColors = ['admin'=>'bg-red-500','manajer_proyek'=>'bg-blue-500','teknisi_lapangan'=>'bg-green-500','gudang'=>'bg-purple-500'];
                    echo strtoupper(substr($user['nama'],0,1));
                    ?>
                </div>
                <div class="flex-1">
                    <textarea name="komentar" rows="2"
                        class="form-input resize-none text-sm"
                        placeholder="<?= isTeknisi() ? 'Tambahkan catatan atau update...' : 'Berikan feedback atau catatan untuk teknisi...' ?>"
                        required></textarea>
                    <div class="flex justify-end mt-2">
                        <button type="submit" class="btn-primary btn-sm">
                            <i class="bi bi-send"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Aksi -->
        <div class="flex gap-3">
            <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $laporan['proyek_id'] ?>" class="btn-secondary flex-1 justify-center">
                <i class="bi bi-diagram-3"></i> Lihat Proyek
            </a>
            <?php if (isAdmin() || $laporan['user_id'] === (int)$user['id']): ?>
            <form method="POST" action="<?= BASE_URL ?>/api/laporan/delete.php" class="flex-1"
                onsubmit="event.preventDefault(); confirmDelete(this, 'Hapus laporan ini beserta <?= count($fotos) ?> foto?')">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn-danger w-full justify-center">
                    <i class="bi bi-trash"></i> Hapus Laporan
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== KOLOM KANAN — SIDEBAR INFO ===== -->
    <div class="space-y-4">
        <!-- Status Proyek -->
        <div class="card p-5">
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Status Proyek</h3>
            <div class="mb-2"><?= statusBadge($laporan['status_proyek']) ?></div>
            <div class="flex items-center gap-2 mt-3">
                <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-primary-600 rounded-full" style="width:<?= $laporan['progres'] ?>%"></div>
                </div>
                <span class="text-xs font-semibold text-slate-700"><?= $laporan['progres'] ?>%</span>
            </div>
        </div>

        <!-- Laporan Lain di Proyek Ini -->
        <?php if (count($laporanProyek) > 1): ?>
        <div class="card p-5">
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">
                Laporan Lain di Proyek Ini
            </h3>
            <div class="space-y-2">
                <?php foreach ($laporanProyek as $lp): ?>
                <a href="<?= BASE_URL ?>/pages/laporan/show.php?id=<?= $lp['id'] ?>"
                    class="flex items-center gap-2 p-2 rounded-lg hover:bg-slate-50 transition-colors
                    <?= $lp['id']==$id ? 'bg-primary-50 border border-primary-200' : '' ?>">
                    <div class="w-2 h-2 rounded-full flex-shrink-0
                        <?= $lp['id']==$id ? 'bg-primary-600' : 'bg-slate-300' ?>"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-slate-700 truncate"><?= sanitize($lp['judul_laporan']) ?></p>
                        <p class="text-xs text-slate-400"><?= formatTanggalShort($lp['tanggal_laporan']) ?></p>
                    </div>
                    <?= statusBadge($lp['status_pengerjaan']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card p-5">
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Aksi Cepat</h3>
            <div class="space-y-2">
                <a href="<?= BASE_URL ?>/pages/laporan/create.php?proyek_id=<?= $laporan['proyek_id'] ?>"
                    class="flex items-center gap-2 w-full px-3 py-2.5 bg-primary-50 text-primary-700 rounded-lg hover:bg-primary-100 transition-colors text-sm font-medium">
                    <i class="bi bi-plus-circle"></i> Laporan Baru di Proyek Ini
                </a>
                <a href="<?= BASE_URL ?>/pages/laporan/index.php"
                    class="flex items-center gap-2 w-full px-3 py-2.5 bg-slate-50 text-slate-600 rounded-lg hover:bg-slate-100 transition-colors text-sm">
                    <i class="bi bi-list-ul"></i> Semua Laporan Saya
                </a>
                <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $laporan['proyek_id'] ?>"
                    class="flex items-center gap-2 w-full px-3 py-2.5 bg-slate-50 text-slate-600 rounded-lg hover:bg-slate-100 transition-colors text-sm">
                    <i class="bi bi-diagram-3"></i> Detail Proyek
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ===== LIGHTBOX ===== -->
<div id="lightbox" class="fixed inset-0 bg-black/95 z-[999] hidden flex-col" onclick="closeLightbox()">
    <!-- Toolbar -->
    <div class="flex items-center justify-between px-6 py-4 flex-shrink-0" onclick="event.stopPropagation()">
        <span class="text-white/70 text-sm" id="lightboxLabel">Foto 1 dari <?= count($fotos) ?></span>
        <div class="flex items-center gap-3">
            <a id="lightboxDownload" href="#" download class="text-white/60 hover:text-white transition-colors">
                <i class="bi bi-download"></i>
            </a>
            <button onclick="closeLightbox()" class="text-white/60 hover:text-white transition-colors">
                <i class="bi bi-x-lg text-xl"></i>
            </button>
        </div>
    </div>

    <!-- Gambar -->
    <div class="flex-1 flex items-center justify-center px-4 pb-4 relative" onclick="event.stopPropagation()">
        <!-- Prev -->
        <button id="prevBtn" onclick="lightboxPrev()"
            class="absolute left-4 z-10 w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center transition-colors">
            <i class="bi bi-chevron-left"></i>
        </button>

        <img id="lightboxImg" src="" alt=""
            class="max-h-full max-w-full object-contain rounded-xl shadow-2xl transition-opacity duration-300">

        <!-- Next -->
        <button id="nextBtn" onclick="lightboxNext()"
            class="absolute right-4 z-10 w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center transition-colors">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>

    <!-- Thumbnail Strip -->
    <div class="flex gap-2 px-6 pb-5 overflow-x-auto flex-shrink-0 justify-center" onclick="event.stopPropagation()">
        <?php foreach ($fotos as $i => $f): ?>
        <div class="flex-shrink-0 w-14 h-14 rounded-lg overflow-hidden cursor-pointer border-2 transition-all
            thumb-item" id="thumb_<?= $i ?>" onclick="openLightbox(<?= $i ?>)">
            <img src="<?= BASE_URL ?>/uploads/<?= sanitize($f['path_file']) ?>"
                class="w-full h-full object-cover" loading="lazy">
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
const fotos = <?= json_encode(array_map(fn($f) => [
    'src'  => BASE_URL . '/uploads/' . $f['path_file'],
    'name' => $f['nama_file'],
], $fotos)) ?>;

let currentFoto = 0;

function openLightbox(idx) {
    currentFoto = idx;
    updateLightbox();
    document.getElementById('lightbox').classList.remove('hidden');
    document.getElementById('lightbox').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
    document.getElementById('lightbox').classList.add('hidden');
    document.body.style.overflow = '';
}
function updateLightbox() {
    const img  = document.getElementById('lightboxImg');
    const lbl  = document.getElementById('lightboxLabel');
    const dl   = document.getElementById('lightboxDownload');
    const foto = fotos[currentFoto];

    img.style.opacity = '0';
    img.src           = foto.src;
    img.onload        = () => img.style.opacity = '1';
    lbl.textContent   = `Foto ${currentFoto+1} dari ${fotos.length}`;
    dl.href           = foto.src;
    dl.download       = foto.name;

    // Update thumbnail strip
    document.querySelectorAll('.thumb-item').forEach((t,i) => {
        t.style.borderColor = i === currentFoto ? '#fff' : 'transparent';
        t.style.opacity     = i === currentFoto ? '1' : '0.5';
    });

    // Prev/next visibility
    document.getElementById('prevBtn').style.opacity = currentFoto > 0 ? '1' : '0.2';
    document.getElementById('nextBtn').style.opacity = currentFoto < fotos.length-1 ? '1' : '0.2';
}
function lightboxPrev() { if (currentFoto > 0) { currentFoto--; updateLightbox(); } }
function lightboxNext() { if (currentFoto < fotos.length-1) { currentFoto++; updateLightbox(); } }

// Keyboard navigation
document.addEventListener('keydown', e => {
    const lb = document.getElementById('lightbox');
    if (lb.style.display === 'none' || lb.classList.contains('hidden')) return;
    if (e.key === 'ArrowLeft')  lightboxPrev();
    if (e.key === 'ArrowRight') lightboxNext();
    if (e.key === 'Escape')     closeLightbox();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>