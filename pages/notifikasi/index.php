<?php
// pages/notifikasi/index.php
$pageTitle   = 'Notifikasi';
$currentPage = 'notifikasi';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$user = currentUser();
$db   = db();

// Tandai semua dibaca jika ada request
if (isset($_GET['baca_semua'])) {
    $db->prepare("UPDATE notifikasi SET status_baca='sudah_dibaca' WHERE user_id=?")->execute([$user['id']]);
    setFlash('success','Semua notifikasi ditandai sudah dibaca.');
    redirect(BASE_URL.'/pages/notifikasi/index.php');
}

$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$total = $db->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id=?");
$total->execute([$user['id']]);
$totalRows  = (int)$total->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("SELECT * FROM notifikasi WHERE user_id=? ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute([$user['id']]);
$notifikasis = $stmt->fetchAll();

$belumDibaca = countNotifBelumDibaca($user['id']);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-lg font-bold text-slate-900 tracking-tight">Notifikasi</h2>
            <p class="text-[13px] text-slate-500 mt-1"><?= $belumDibaca ?> belum dibaca</p>
        </div>
        <?php if ($belumDibaca > 0): ?>
        <a href="?baca_semua=1" class="btn-secondary btn-sm">
            <i class="bi bi-check-all"></i> Tandai Semua Dibaca
        </a>
        <?php endif; ?>
    </div>

    <div class="card overflow-hidden">
        <?php if (empty($notifikasis)): ?>
        <div class="px-5 py-16 text-center">
            <span class="empty-icon mb-3 !w-16 !h-16"><i class="bi bi-bell-slash"></i></span>
            <p class="text-slate-400">Tidak ada notifikasi</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-100">
            <?php foreach ($notifikasis as $n): ?>
            <div class="flex gap-4 px-5 py-4 <?= $n['status_baca'] === 'belum_dibaca' ? 'bg-blue-50/50' : '' ?> hover:bg-slate-50 transition-colors">
                <div class="flex-shrink-0 mt-0.5">
                    <?php if ($n['status_baca'] === 'belum_dibaca'): ?>
                    <div class="w-2.5 h-2.5 rounded-full bg-primary-600 mt-1.5"></div>
                    <?php else: ?>
                    <div class="w-2.5 h-2.5 rounded-full bg-slate-200 mt-1.5"></div>
                    <?php endif; ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800"><?= sanitize($n['judul']) ?></p>
                    <p class="text-sm text-slate-500 mt-0.5"><?= sanitize($n['pesan']) ?></p>
                    <p class="text-xs text-slate-400 mt-1.5"><?= timeAgo($n['created_at']) ?></p>
                </div>
                <?php if ($n['status_baca'] === 'belum_dibaca'): ?>
                <form method="POST" action="<?= BASE_URL ?>/api/notifikasi/baca.php" class="flex-shrink-0">
                    <input type="hidden" name="id" value="<?= $n['id'] ?>">
                    <button type="submit" class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition-colors" title="Tandai dibaca">
                        <i class="bi bi-check text-sm"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
        <div class="px-5 py-3 border-t border-slate-100 flex justify-center gap-1">
            <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
            <a href="?page=<?= $pg ?>"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-xs
                <?= $pg === $page ? 'bg-primary-600 text-white shadow-sm shadow-primary-600/30' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                <?= $pg ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>