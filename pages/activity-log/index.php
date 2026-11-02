<?php
// pages/activity-log/index.php — AUDIT TRAIL & LOG AKTIVITAS SISTEM
$pageTitle   = 'Activity Log';
$currentPage = 'activity-log';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin']);

$db     = db();
$tipe   = $_GET['tipe'] ?? '';
$modul  = $_GET['modul'] ?? '';
$search = trim($_GET['search'] ?? '');

$perPage = 25;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($tipe !== '') {
    $where[]  = 'a.tipe = ?';
    $params[] = $tipe;
}
if ($modul !== '') {
    $where[]  = 'a.modul = ?';
    $params[] = $modul;
}
if ($search !== '') {
    $where[]  = '(a.deskripsi LIKE ? OR u.nama LIKE ? OR a.aksi LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereStr = implode(' AND ', $where);

$cnt = $db->prepare("SELECT COUNT(*) FROM activity_log a JOIN users u ON a.user_id = u.id WHERE $whereStr");
$cnt->execute($params);
$totalRows  = (int)$cnt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $db->prepare("
    SELECT a.*, u.nama, u.role
    FROM activity_log a
    JOIN users u ON a.user_id = u.id
    WHERE $whereStr
    ORDER BY a.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$moduls = $db->query("SELECT DISTINCT modul FROM activity_log WHERE modul IS NOT NULL AND modul != '' ORDER BY modul")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-6xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <div class="flex items-center gap-3.5">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-600 to-indigo-700 text-white flex items-center justify-center shadow-lg shadow-primary-600/25">
                <i class="bi bi-activity text-lg"></i>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Audit Trail & Activity Log</h2>
                <p class="text-xs text-slate-500 mt-0.5"><?= number_format($totalRows) ?> rekaman aktivitas pengguna tercatat di sistem</p>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card p-4 mb-5">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[220px]">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="<?= sanitize($search) ?>"
                    placeholder="Cari nama pengguna, aksi, atau deskripsi..."
                    class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            <select name="tipe" class="py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700" data-auto-submit>
                <option value="">Semua Tipe Aksi</option>
                <?php foreach (['create','update','delete','login','logout','export','bulk'] as $t): ?>
                <option value="<?= $t ?>" <?= $tipe === $t ? 'selected' : '' ?>><?= strtoupper($t) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="modul" class="py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700" data-auto-submit>
                <option value="">Semua Modul</option>
                <?php foreach ($moduls as $m): ?>
                <option value="<?= sanitize($m) ?>" <?= $modul === $m ? 'selected' : '' ?>><?= ucfirst(sanitize($m)) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
            <?php if ($search !== '' || $tipe !== '' || $modul !== ''): ?>
            <a href="<?= BASE_URL ?>/pages/activity-log/index.php" class="btn-secondary btn-sm text-red-600">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Daftar Log -->
    <div class="card overflow-hidden">
        <div class="divide-y divide-slate-100">
            <?php if (empty($logs)): ?>
            <div class="py-14 text-center text-sm text-slate-400">Tidak ada riwayat aktivitas yang cocok.</div>
            <?php else: ?>
            <?php foreach ($logs as $a):
                [$icon, $bg] = getActivityIcon($a['tipe']);
            ?>
            <div class="px-5 py-3.5 hover:bg-slate-50/80 transition-colors flex items-center justify-between gap-4">
                <div class="flex items-start gap-3.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl <?= $bg ?> flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="bi <?= $icon ?> text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-slate-800">
                            <span class="font-bold text-slate-900"><?= sanitize($a['nama']) ?></span>
                            <span class="text-slate-600"> — <?= sanitize($a['deskripsi']) ?></span>
                        </p>
                        <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-400 flex-wrap">
                            <?php if ($a['modul']): ?>
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded font-bold uppercase text-[10px]"><?= sanitize($a['modul']) ?></span>
                            <?php endif; ?>
                            <span>Aksi: <code class="text-slate-600"><?= sanitize($a['aksi']) ?></code></span>
                            <span>· IP: <?= sanitize($a['ip_address'] ?? '-') ?></span>
                        </div>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="text-xs font-semibold text-slate-600"><?= timeAgo($a['created_at']) ?></div>
                    <div class="text-[11px] text-slate-400"><?= $a['created_at'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>