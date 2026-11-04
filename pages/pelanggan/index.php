<?php
// pages/pelanggan/index.php — DIREKTORI PELANGGAN & KLIEN ENTERPRISE
$pageTitle   = 'Data Pelanggan';
$currentPage = 'pelanggan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$db     = db();
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? '');

$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = '(pl.nama_pelanggan LIKE ? OR pl.alamat LIKE ? OR pl.no_telp LIKE ? OR pl.email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$having = '';
if ($filter === 'aktif') {
    $having = 'HAVING proyek_aktif > 0';
} elseif ($filter === 'selesai') {
    $having = 'HAVING proyek_selesai > 0 AND proyek_aktif = 0';
} elseif ($filter === 'baru') {
    $having = 'HAVING total_proyek = 0';
}

$whereStr = implode(' AND ', $where);

// Query utama dengan agregasi jumlah proyek per pelanggan
$sqlBase = "
    SELECT pl.*,
           COUNT(p.id) AS total_proyek,
           COALESCE(SUM(CASE WHEN p.status IN ('survei','perencanaan','instalasi') THEN 1 ELSE 0 END), 0) AS proyek_aktif,
           COALESCE(SUM(CASE WHEN p.status = 'selesai' THEN 1 ELSE 0 END), 0) AS proyek_selesai,
           COALESCE(ROUND(AVG(p.progres)), 0) AS rata_progres
    FROM pelanggan pl
    LEFT JOIN proyek p ON p.pelanggan_id = pl.id
    WHERE $whereStr
    GROUP BY pl.id
    $having
";

$cntStmt = $db->prepare("SELECT COUNT(*) FROM ($sqlBase) AS sub");
$cntStmt->execute($params);
$totalRows  = (int)$cntStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $db->prepare("$sqlBase ORDER BY proyek_aktif DESC, pl.nama_pelanggan ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$pelanggans = $stmt->fetchAll();

// Statistik Ringkas
$summary = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM pelanggan) AS total_klien,
        (SELECT COUNT(DISTINCT pelanggan_id) FROM proyek WHERE status IN ('survei','perencanaan','instalasi')) AS klien_aktif,
        (SELECT COUNT(*) FROM proyek) AS total_proyek,
        (SELECT COUNT(*) FROM proyek WHERE status = 'selesai') AS proyek_tuntas
")->fetch();

// Helper format nomor WA Indonesia (08xx -> 628xx)
$formatWaNumber = function (?string $phone): string {
    $clean = preg_replace('/[^0-9]/', '', (string)$phone);
    if (str_starts_with($clean, '0')) {
        return '62' . substr($clean, 1);
    }
    return $clean;
};

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Data Master Pelanggan</span>
    </nav>

    <!-- Header Halaman -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3.5">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-600 to-indigo-700 text-white flex items-center justify-center shadow-lg shadow-primary-600/25">
                <i class="bi bi-buildings-fill text-lg"></i>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Manajemen Klien & Pelanggan PLTS</h2>
                <p class="text-xs text-slate-500 mt-0.5">Kelola direktori perusahaan klien, kontak PIC, lokasi instalasi, dan riwayat kontrak proyek</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Toggle Tampilan Grid vs Tabel -->
            <div class="inline-flex bg-slate-200/70 p-1 rounded-xl">
                <button type="button" id="btnViewTable" onclick="setClientView('table')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white text-slate-900 shadow-2xs flex items-center gap-1.5">
                    <i class="bi bi-table"></i> Tabel
                </button>
                <button type="button" id="btnViewGrid" onclick="setClientView('grid')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900 flex items-center gap-1.5">
                    <i class="bi bi-grid-fill"></i> Kartu
                </button>
            </div>

            <a href="<?= BASE_URL ?>/api/pelanggan/eksport.php?all=1" class="btn-secondary btn-sm">
                <i class="bi bi-file-earmark-spreadsheet text-emerald-600"></i> Export Semua CSV
            </a>
            <a href="<?= BASE_URL ?>/pages/pelanggan/create.php" class="btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Tambah Pelanggan
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        <div class="card p-4 stat-card" style="--stat-accent: #4f46e5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Pelanggan</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int)$summary['total_klien'] ?></p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="bi bi-people-fill text-lg"></i>
                </span>
            </div>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: #10b981">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Klien Proyek Aktif</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int)$summary['klien_aktif'] ?></p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="bi bi-lightning-charge-fill text-lg"></i>
                </span>
            </div>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: #0ea5e9">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Kontrak Proyek</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int)$summary['total_proyek'] ?></p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i class="bi bi-diagram-3-fill text-lg"></i>
                </span>
            </div>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: #f59e0b">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Proyek Selesai</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int)$summary['proyek_tuntas'] ?></p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="bi bi-patch-check-fill text-lg"></i>
                </span>
            </div>
        </div>
    </div>

    <!-- Filter, Pencarian & Bulk Action Bar -->
    <div class="card p-4 mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex-1 min-w-[260px] flex flex-wrap items-center gap-2.5">
            <div class="relative flex-1 min-w-[220px]">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="<?= sanitize($search) ?>"
                    placeholder="Cari nama perusahaan/pelanggan, alamat, telepon, atau email..."
                    class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:bg-white transition-all">
            </div>

            <select name="filter" class="py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700" data-auto-submit>
                <option value="">Semua Status Klien</option>
                <option value="aktif" <?= $filter === 'aktif' ? 'selected' : '' ?>>Memiliki Proyek Aktif</option>
                <option value="selesai" <?= $filter === 'selesai' ? 'selected' : '' ?>>Proyek Selesai</option>
                <option value="baru" <?= $filter === 'baru' ? 'selected' : '' ?>>Belum Ada Proyek</option>
            </select>

            <button type="submit" class="btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
            <?php if ($search !== '' || $filter !== ''): ?>
            <a href="<?= BASE_URL ?>/pages/pelanggan/index.php" class="btn-secondary btn-sm text-red-600">Reset</a>
            <?php endif; ?>
        </form>

        <!-- Bulk Action Bar -->
        <div id="bulkBarPelanggan" class="hidden items-center gap-2 bg-indigo-50 border border-indigo-200 px-3 py-1.5 rounded-xl">
            <span class="text-xs font-bold text-indigo-800"><span id="selectedCountPelanggan">0</span> dipilih</span>
            <button type="button" onclick="exportSelectedPelanggan()" class="btn-secondary btn-sm !py-1 !px-2.5 text-xs">
                <i class="bi bi-download"></i> Export CSV
            </button>
            <button type="button" onclick="hapusBulkPelanggan()" class="btn-danger btn-sm !py-1 !px-2.5 text-xs">
                <i class="bi bi-trash"></i> Hapus
            </button>
        </div>
    </div>

    <!-- Form Tersembunyi untuk Bulk Delete -->
    <form method="POST" action="<?= BASE_URL ?>/api/pelanggan/bulk.php" id="formBulkPelanggan" class="hidden">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="delete">
        <div id="bulkHiddenInputs"></div>
    </form>

    <?php if (empty($pelanggans)): ?>
    <div class="card py-16 px-6 text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl">
            <i class="bi bi-buildings"></i>
        </div>
        <h3 class="text-sm font-bold text-slate-800">Tidak Ada Data Pelanggan</h3>
        <p class="text-xs text-slate-400 mt-1">Belum ada pelanggan terdaftar atau tidak ditemukan yang cocok dengan filter pencarian.</p>
    </div>
    <?php else: ?>

    <!-- ==========================================================
         VIEW 1: TABEL PRESISI PELANGGAN
    =========================================================== -->
    <div id="clientTableView" class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="table-header w-10">
                            <input type="checkbox" id="checkAllPelanggan" class="w-4 h-4 rounded text-primary-600" onchange="toggleAllPelanggan(this)">
                        </th>
                        <th class="table-header">Nama Pelanggan / Perusahaan</th>
                        <th class="table-header">Alamat Lokasi</th>
                        <th class="table-header">Kontak & Pintasan</th>
                        <th class="table-header">Portofolio Proyek</th>
                        <th class="table-header text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($pelanggans as $p):
                        $inisial = strtoupper(substr($p['nama_pelanggan'], 0, 2));
                        $waNum   = $formatWaNumber($p['no_telp']);
                    ?>
                    <tr class="table-row">
                        <td class="table-cell" onclick="event.stopPropagation()">
                            <input type="checkbox" value="<?= $p['id'] ?>" class="pelanggan-cb w-4 h-4 rounded text-primary-600" onchange="updateBulkPelangganUI()">
                        </td>
                        <td class="table-cell">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-600 to-indigo-700 text-white font-bold text-xs flex items-center justify-center flex-shrink-0 shadow-2xs">
                                    <?= sanitize($inisial) ?>
                                </div>
                                <div class="min-w-0">
                                    <a href="<?= BASE_URL ?>/pages/pelanggan/edit.php?id=<?= $p['id'] ?>" class="text-sm font-bold text-slate-900 hover:text-primary-600 truncate block">
                                        <?= sanitize($p['nama_pelanggan']) ?>
                                    </a>
                                    <span class="text-[11px] text-slate-400">
                                        <?= !empty($p['email']) ? sanitize($p['email']) : 'Terdaftar ' . formatTanggalShort($p['created_at']) ?>
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="table-cell">
                            <p class="text-xs text-slate-700 max-w-xs line-clamp-2"><?= sanitize($p['alamat']) ?></p>
                            <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($p['nama_pelanggan'] . ' ' . $p['alamat']) ?>"
                               target="_blank" onclick="event.stopPropagation()"
                               class="text-[11px] font-semibold text-primary-600 hover:underline inline-flex items-center gap-1 mt-1">
                                <i class="bi bi-geo-alt-fill text-amber-500"></i> Cari di Maps
                            </a>
                        </td>
                        <td class="table-cell whitespace-nowrap" onclick="event.stopPropagation()">
                            <div class="text-xs font-bold text-slate-800 mb-1"><?= sanitize($p['no_telp']) ?></div>
                            <div class="flex items-center gap-1.5">
                                <?php if ($waNum !== ''): ?>
                                <a href="https://wa.me/<?= $waNum ?>" target="_blank"
                                   class="px-2 py-0.5 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-[11px] font-bold inline-flex items-center gap-1 transition-colors">
                                    <i class="bi bi-whatsapp"></i> Chat WA
                                </a>
                                <?php endif; ?>
                                <?php if (!empty($p['email'])): ?>
                                <a href="mailto:<?= sanitize($p['email']) ?>"
                                   class="px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-600 text-[11px] font-semibold inline-flex items-center gap-1 transition-colors">
                                    <i class="bi bi-envelope"></i> Email
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="table-cell whitespace-nowrap">
                            <?php if ((int)$p['total_proyek'] === 0): ?>
                            <span class="badge-modern bg-slate-100 text-slate-500">Belum Ada Proyek</span>
                            <?php else: ?>
                            <div class="flex items-center gap-1.5">
                                <?php if ((int)$p['proyek_aktif'] > 0): ?>
                                <span class="badge-modern bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span> <?= (int)$p['proyek_aktif'] ?> Aktif
                                </span>
                                <?php endif; ?>
                                <?php if ((int)$p['proyek_selesai'] > 0): ?>
                                <span class="badge-modern bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                                    ✓ <?= (int)$p['proyek_selesai'] ?> Selesai
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-[10px] text-slate-400 mt-1">Rata-rata progres: <strong><?= (int)$p['rata_progres'] ?>%</strong></div>
                            <?php endif; ?>
                        </td>
                        <td class="table-cell text-right whitespace-nowrap" onclick="event.stopPropagation()">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="<?= BASE_URL ?>/pages/proyek/create.php?pelanggan_id=<?= $p['id'] ?>"
                                   class="btn-secondary btn-sm text-primary-700 hover:bg-primary-50" title="Buat Proyek untuk Pelanggan Ini">
                                    <i class="bi bi-plus-circle"></i> + Proyek
                                </a>
                                <a href="<?= BASE_URL ?>/pages/pelanggan/edit.php?id=<?= $p['id'] ?>" class="btn-secondary btn-sm" title="Edit Pelanggan">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="<?= BASE_URL ?>/api/pelanggan/delete.php" class="inline"
                                      onsubmit="event.preventDefault(); confirmDelete(this, 'Hapus pelanggan <?= addslashes(sanitize($p['nama_pelanggan'])) ?>?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-secondary btn-sm text-red-600 hover:bg-red-50" title="Hapus Pelanggan">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==========================================================
         VIEW 2: GRID KARTU PELANGGAN (TERSEMBUNYI SECARA DEFAULT)
    =========================================================== -->
    <div id="clientGridView" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 hidden">
        <?php foreach ($pelanggans as $p):
            $inisial = strtoupper(substr($p['nama_pelanggan'], 0, 2));
            $waNum   = $formatWaNumber($p['no_telp']);
        ?>
        <div class="card p-5 flex flex-col justify-between hover:shadow-lg transition-all">
            <div>
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-600 to-indigo-700 text-white font-bold text-sm flex items-center justify-center flex-shrink-0 shadow-sm">
                            <?= sanitize($inisial) ?>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-sm font-bold text-slate-900 truncate" title="<?= sanitize($p['nama_pelanggan']) ?>">
                                <?= sanitize($p['nama_pelanggan']) ?>
                            </h4>
                            <p class="text-xs text-slate-400 truncate"><?= sanitize($p['email'] ?: 'Email belum diisi') ?></p>
                        </div>
                    </div>
                    <span class="badge-modern bg-slate-100 text-slate-700 flex-shrink-0">
                        <?= (int)$p['total_proyek'] ?> Proyek
                    </span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs text-slate-600 space-y-1.5 mb-4">
                    <div class="flex items-start gap-2">
                        <i class="bi bi-geo-alt-fill text-amber-500 mt-0.5"></i>
                        <span class="line-clamp-2"><?= sanitize($p['alamat']) ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="bi bi-telephone-fill text-emerald-500"></i>
                        <span class="font-semibold text-slate-800"><?= sanitize($p['no_telp']) ?></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100 gap-2">
                <div class="flex items-center gap-1.5">
                    <?php if ($waNum !== ''): ?>
                    <a href="https://wa.me/<?= $waNum ?>" target="_blank"
                       class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold inline-flex items-center gap-1">
                        <i class="bi bi-whatsapp"></i> WA
                    </a>
                    <?php endif; ?>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($p['nama_pelanggan'] . ' ' . $p['alamat']) ?>"
                       target="_blank"
                       class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold inline-flex items-center gap-1">
                        <i class="bi bi-map"></i> Maps
                    </a>
                </div>

                <div class="flex items-center gap-1.5">
                    <a href="<?= BASE_URL ?>/pages/pelanggan/edit.php?id=<?= $p['id'] ?>" class="btn-secondary btn-sm">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-between mt-5">
        <p class="text-xs text-slate-400">Menampilkan <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalRows) ?> dari <?= $totalRows ?> pelanggan</p>
        <div class="flex gap-1.5">
            <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
            <a href="?page=<?= $pg ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>"
               class="w-8 h-8 flex items-center justify-center rounded-xl text-xs font-bold transition-colors
               <?= $pg === $page ? 'bg-primary-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
                <?= $pg ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function setClientView(mode) {
    const isTable = mode === 'table';
    document.getElementById('clientTableView')?.classList.toggle('hidden', !isTable);
    document.getElementById('clientGridView')?.classList.toggle('hidden', isTable);

    document.getElementById('btnViewTable').className = isTable
        ? 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white text-slate-900 shadow-2xs flex items-center gap-1.5'
        : 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900 flex items-center gap-1.5';
    document.getElementById('btnViewGrid').className = !isTable
        ? 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white text-slate-900 shadow-2xs flex items-center gap-1.5'
        : 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900 flex items-center gap-1.5';

    localStorage.setItem('plts_client_view', mode);
}

document.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('plts_client_view') || 'table';
    setClientView(saved);
});

function toggleAllPelanggan(master) {
    document.querySelectorAll('.pelanggan-cb').forEach(cb => cb.checked = master.checked);
    updateBulkPelangganUI();
}

function updateBulkPelangganUI() {
    const checked = Array.from(document.querySelectorAll('.pelanggan-cb:checked')).map(cb => cb.value);
    const bar = document.getElementById('bulkBarPelanggan');
    document.getElementById('selectedCountPelanggan').textContent = checked.length;
    bar.classList.toggle('hidden', checked.length === 0);
    bar.classList.toggle('flex', checked.length > 0);
}

function exportSelectedPelanggan() {
    const checked = Array.from(document.querySelectorAll('.pelanggan-cb:checked')).map(cb => cb.value);
    if (!checked.length) return;
    window.location.href = '<?= BASE_URL ?>/api/pelanggan/eksport.php?ids=' + checked.join(',');
}

function hapusBulkPelanggan() {
    const checked = Array.from(document.querySelectorAll('.pelanggan-cb:checked')).map(cb => cb.value);
    if (!checked.length) return;
    const container = document.getElementById('bulkHiddenInputs');
    container.innerHTML = checked.map(id => `<input type="hidden" name="ids[]" value="${id}">`).join('');
    confirmDelete('formBulkPelanggan', `Yakin ingin menghapus ${checked.length} pelanggan terpilih?`);
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>