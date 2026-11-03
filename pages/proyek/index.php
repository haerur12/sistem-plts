<?php
// pages/proyek/index.php — DAFTAR PROYEK + TIM PERSONEL DITEMPATKAN
$pageTitle   = 'Manajemen Proyek';
$currentPage = 'proyek';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'manajer_proyek']);

$db     = db();
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = '(p.nama_proyek LIKE ? OR p.lokasi LIKE ? OR pl.nama_pelanggan LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status !== '') {
    $where[]  = 'p.status = ?';
    $params[] = $status;
}

$whereStr = implode(' AND ', $where);

$cntStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM proyek p 
    JOIN pelanggan pl ON p.pelanggan_id = pl.id 
    WHERE $whereStr
");
$cntStmt->execute($params);
$totalRows  = (int)$cntStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $db->prepare("
    SELECT p.*, pl.nama_pelanggan,
           (SELECT COUNT(*) FROM laporan_lapangan l WHERE l.proyek_id = p.id) AS total_laporan
    FROM proyek p
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    WHERE $whereStr
    ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$proyeks = $stmt->fetchAll();

// Ambil daftar tim per proyek untuk ditampilkan sebagai Stacked Avatars
$timPerProyek = [];
if (!empty($proyeks)) {
    try {
        $proyekIds = array_column($proyeks, 'id');
        $inQuery   = implode(',', array_fill(0, count($proyekIds), '?'));
        $stmtTim   = $db->prepare("SELECT proyek_id, nama_personel, peran_proyek FROM proyek_tim WHERE proyek_id IN ($inQuery) ORDER BY id ASC");
        $stmtTim->execute($proyekIds);
        foreach ($stmtTim->fetchAll() as $t) {
            $timPerProyek[(int)$t['proyek_id']][] = $t;
        }
    } catch (Exception $e) {
        $timPerProyek = [];
    }
}

// Statistik Status Proyek
$statCounts = $db->query("SELECT status, COUNT(*) as jml FROM proyek GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$semuaCount = array_sum($statCounts);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header Halaman -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <div class="flex items-center gap-3.5">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-600 to-indigo-700 text-white flex items-center justify-center shadow-lg shadow-primary-600/25">
                <i class="bi bi-diagram-3-fill text-lg"></i>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Manajemen Proyek PLTS</h2>
                <p class="text-xs text-slate-500 mt-0.5">Kelola daftar proyek instalasi, penempatan tim lapangan, dan progres capaian</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= BASE_URL ?>/pages/proyek/kanban.php" class="btn-secondary btn-sm">
                <i class="bi bi-kanban"></i> Kanban Board
            </a>
            <a href="<?= BASE_URL ?>/pages/proyek/gantt.php" class="btn-secondary btn-sm">
                <i class="bi bi-calendar-range"></i> Gantt Chart
            </a>
            <?php if (isAdmin()): ?>
            <a href="<?= BASE_URL ?>/pages/proyek/create.php" class="btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Tambah Proyek
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab Status Cepat -->
    <div class="flex items-center gap-2 flex-wrap mb-4">
        <?php
        $tabs = [
            ''            => ['Semua Proyek', $semuaCount],
            'survei'      => ['Survei', (int)($statCounts['survei'] ?? 0)],
            'perencanaan' => ['Perencanaan', (int)($statCounts['perencanaan'] ?? 0)],
            'instalasi'   => ['Instalasi', (int)($statCounts['instalasi'] ?? 0)],
            'selesai'     => ['Selesai', (int)($statCounts['selesai'] ?? 0)],
            'ditunda'     => ['Ditunda', (int)($statCounts['ditunda'] ?? 0)],
        ];
        foreach ($tabs as $key => [$lbl, $jml]):
            $isAct = ($status === $key);
        ?>
        <a href="?status=<?= urlencode($key) ?>&search=<?= urlencode($search) ?>"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold border transition-all flex items-center gap-1.5
           <?= $isAct ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' ?>">
            <?= $lbl ?>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] <?= $isAct ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' ?>"><?= $jml ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Search & Bulk Action Bar -->
    <div class="card p-4 mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex-1 min-w-[240px] flex gap-2">
            <input type="hidden" name="status" value="<?= sanitize($status) ?>">
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="<?= sanitize($search) ?>"
                    placeholder="Cari nama proyek, pelanggan, atau lokasi..."
                    class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:bg-white transition-all">
            </div>
            <button type="submit" class="btn-primary btn-sm"><i class="bi bi-search"></i> Cari</button>
            <?php if ($search !== '' || $status !== ''): ?>
            <a href="<?= BASE_URL ?>/pages/proyek/index.php" class="btn-secondary btn-sm text-red-600">Reset</a>
            <?php endif; ?>
        </form>

        <!-- Bulk Action Bar (Muncul saat ada checkbox dicentang) -->
        <div id="bulkActionBar" class="hidden items-center gap-2 bg-indigo-50 border border-indigo-200 px-3 py-1.5 rounded-xl">
            <span class="text-xs font-bold text-indigo-800"><span id="selectedCount">0</span> dipilih</span>
            <button type="button" onclick="exportSelectedProyek()" class="btn-secondary btn-sm !py-1 !px-2.5 text-xs">
                <i class="bi bi-download"></i> Export CSV
            </button>
        </div>
    </div>

    <!-- Tabel Daftar Proyek -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="table-header w-10">
                            <input type="checkbox" id="checkAllProyek" class="w-4 h-4 rounded text-primary-600" onchange="toggleSelectAll(this)">
                        </th>
                        <th class="table-header">Proyek & Pelanggan</th>
                        <th class="table-header">Lokasi</th>
                        <th class="table-header">Tim Ditempatkan</th>
                        <th class="table-header">Periode Kontrak</th>
                        <th class="table-header">Status</th>
                        <th class="table-header w-40">Progres</th>
                        <th class="table-header text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($proyeks)): ?>
                    <tr>
                        <td colspan="8" class="table-cell text-center py-14 text-slate-400">
                            Tidak ada data proyek yang ditemukan.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($proyeks as $p):
                        $pid = (int)$p['id'];
                        $anggotaTim = $timPerProyek[$pid] ?? [];
                    ?>
                    <tr class="table-row">
                        <td class="table-cell" onclick="event.stopPropagation()">
                            <input type="checkbox" value="<?= $pid ?>" class="proyek-checkbox w-4 h-4 rounded text-primary-600" onchange="updateBulkUI()">
                        </td>
                        <td class="table-cell">
                            <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $pid ?>" class="font-bold text-slate-900 hover:text-primary-600 text-sm block">
                                <?= sanitize($p['nama_proyek']) ?>
                            </a>
                            <span class="text-xs text-slate-400 flex items-center gap-1 mt-0.5">
                                <i class="bi bi-building"></i> <?= sanitize($p['nama_pelanggan']) ?>
                            </span>
                        </td>
                        <td class="table-cell">
                            <div class="text-xs text-slate-700 max-w-[180px] truncate" title="<?= sanitize($p['lokasi']) ?>">
                                <?= sanitize($p['lokasi']) ?>
                            </div>
                            <?php if (!empty($p['latitude']) && !empty($p['longitude'])): ?>
                            <a href="https://www.google.com/maps?q=<?= urlencode($p['latitude'] . ',' . $p['longitude']) ?>"
                               target="_blank" onclick="event.stopPropagation()"
                               class="text-[11px] font-semibold text-primary-600 hover:underline inline-flex items-center gap-1 mt-0.5">
                                <i class="bi bi-geo-alt-fill text-red-500"></i> Buka Peta
                            </a>
                            <?php endif; ?>
                        </td>
                        <td class="table-cell whitespace-nowrap">
                            <?php if (empty($anggotaTim)): ?>
                            <span class="text-xs text-slate-400 italic">Belum diatur</span>
                            <?php else: ?>
                            <div class="flex items-center -space-x-2 overflow-hidden">
                                <?php
                                $maxTampil = 4;
                                foreach (array_slice($anggotaTim, 0, $maxTampil) as $idx => $agt):
                                    $inisial = strtoupper(substr($agt['nama_personel'], 0, 1));
                                    $bgAvatar = ['bg-indigo-600', 'bg-emerald-600', 'bg-sky-600', 'bg-amber-600'][$idx % 4];
                                ?>
                                <div class="w-7 h-7 rounded-full <?= $bgAvatar ?> text-white text-[11px] font-bold flex items-center justify-center ring-2 ring-white"
                                     title="<?= sanitize($agt['nama_personel'] . ' — ' . $agt['peran_proyek']) ?>">
                                    <?= $inisial ?>
                                </div>
                                <?php endforeach; ?>
                                <?php if (count($anggotaTim) > $maxTampil): ?>
                                <div class="w-7 h-7 rounded-full bg-slate-200 text-slate-700 text-[10px] font-extrabold flex items-center justify-center ring-2 ring-white"
                                     title="+<?= count($anggotaTim) - $maxTampil ?> personel lainnya">
                                    +<?= count($anggotaTim) - $maxTampil ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <span class="text-[10px] text-slate-400 block mt-1"><?= count($anggotaTim) ?> personel</span>
                            <?php endif; ?>
                        </td>
                        <td class="table-cell whitespace-nowrap text-xs text-slate-600">
                            <div><?= formatTanggalShort($p['tanggal_mulai']) ?></div>
                            <div class="text-slate-400">s/d <?= formatTanggalShort($p['tanggal_selesai']) ?></div>
                        </td>
                        <td class="table-cell whitespace-nowrap">
                            <?= statusBadge($p['status']) ?>
                        </td>
                        <td class="table-cell">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 progress-track rounded-full overflow-hidden">
                                    <div class="h-full progress-fill rounded-full" style="width: <?= (int)$p['progres'] ?>%"></div>
                                </div>
                                <span class="text-xs font-extrabold text-slate-700 w-9 text-right"><?= (int)$p['progres'] ?>%</span>
                            </div>
                            <span class="text-[10px] text-slate-400 mt-0.5 block"><?= (int)$p['total_laporan'] ?> laporan lapangan</span>
                        </td>
                        <td class="table-cell text-right whitespace-nowrap" onclick="event.stopPropagation()">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $pid ?>" class="btn-secondary btn-sm" title="Lihat Detail & Tim">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                                <?php if (isAdmin()): ?>
                                <a href="<?= BASE_URL ?>/pages/proyek/edit.php?id=<?= $pid ?>" class="btn-secondary btn-sm" title="Edit Proyek & Tim">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="px-5 py-3.5 border-t border-slate-100 flex items-center justify-between">
            <p class="text-xs text-slate-400">Menampilkan <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalRows) ?> dari <?= $totalRows ?> proyek</p>
            <div class="flex gap-1.5">
                <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
                <a href="?page=<?= $pg ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>"
                   class="w-8 h-8 flex items-center justify-center rounded-xl text-xs font-bold transition-colors
                   <?= $pg === $page ? 'bg-primary-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                    <?= $pg ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleSelectAll(master) {
    document.querySelectorAll('.proyek-checkbox').forEach(cb => cb.checked = master.checked);
    updateBulkUI();
}

function updateBulkUI() {
    const checked = Array.from(document.querySelectorAll('.proyek-checkbox:checked')).map(cb => cb.value);
    const bar = document.getElementById('bulkActionBar');
    document.getElementById('selectedCount').textContent = checked.length;
    bar.classList.toggle('hidden', checked.length === 0);
    bar.classList.toggle('flex', checked.length > 0);
}

function exportSelectedProyek() {
    const checked = Array.from(document.querySelectorAll('.proyek-checkbox:checked')).map(cb => cb.value);
    if (!checked.length) return;
    window.location.href = '<?= BASE_URL ?>/api/proyek/export.php?ids=' + checked.join(',');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>