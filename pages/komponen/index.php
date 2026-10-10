<?php
$pageTitle   = 'Komponen PLTS';
$currentPage = 'komponen';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$db     = db();
$search = $_GET['search'] ?? '';
$kat    = $_GET['kategori'] ?? '';
$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = 'nama_komponen LIKE ?'; $params[] = "%$search%"; }
if ($kat)    { $where[] = 'kategori = ?';          $params[] = $kat; }
$whereStr = implode(' AND ', $where);

$cntStmt = $db->prepare("SELECT COUNT(*) FROM komponen WHERE $whereStr");
$cntStmt->execute($params);
$totalRows = (int)$cntStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("SELECT * FROM komponen WHERE $whereStr ORDER BY kategori, nama_komponen LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$komponen = $stmt->fetchAll();

// Semua kategori + count
$kategoris = $db->query("
    SELECT kategori, COUNT(*) as total,
           SUM(CASE WHEN stok_tersedia <= stok_minimum THEN 1 ELSE 0 END) as kritis
    FROM komponen GROUP BY kategori ORDER BY kategori
")->fetchAll();

// Summary stok
$summary = $db->query("
    SELECT
        SUM(CASE WHEN stok_tersedia > stok_minimum * 1.5 THEN 1 ELSE 0 END) as aman,
        SUM(CASE WHEN stok_tersedia > stok_minimum AND stok_tersedia <= stok_minimum * 1.5 THEN 1 ELSE 0 END) as rendah,
        SUM(CASE WHEN stok_tersedia > 0 AND stok_tersedia <= stok_minimum THEN 1 ELSE 0 END) as kritis,
        SUM(CASE WHEN stok_tersedia = 0 THEN 1 ELSE 0 END) as habis
    FROM komponen
")->fetch();

// Icon per kategori
$katIcons = [
    'Panel Surya' => '☀️', 'Inverter' => '⚡', 'Baterai' => '🔋',
    'Kabel'       => '🔌', 'Mounting'  => '🔩', 'Proteksi' => '🛡️',
    'Monitoring'  => '📡', 'Lainnya'   => '📦',
];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Header -->
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">Komponen PLTS</h2>
        <p class="text-sm text-slate-400 mt-0.5"><?= $totalRows ?> komponen ditemukan</p>
    </div>
    <?php if (isAdminOrGudang()): ?>
    <a href="<?= BASE_URL ?>/pages/komponen/create.php" class="btn-primary">
        <i class="bi bi-plus-lg"></i> Tambah Komponen
    </a>
    <?php endif; ?>
</div>

<!-- Summary Stok -->
<div class="grid grid-cols-4 gap-3 mb-5">
    <?php
    $sCards = [
        ['val'=>$summary['aman'],   'label'=>'Aman',   'cls'=>'bg-green-50 border-green-200', 'dot'=>'bg-green-500', 'text'=>'text-green-700'],
        ['val'=>$summary['rendah'], 'label'=>'Rendah', 'cls'=>'bg-amber-50 border-amber-200', 'dot'=>'bg-amber-500', 'text'=>'text-amber-700'],
        ['val'=>$summary['kritis'], 'label'=>'Kritis', 'cls'=>'bg-orange-50 border-orange-200','dot'=>'bg-orange-500','text'=>'text-orange-700'],
        ['val'=>$summary['habis'],  'label'=>'Habis',  'cls'=>'bg-red-50 border-red-200',     'dot'=>'bg-red-500',   'text'=>'text-red-700'],
    ];
    foreach ($sCards as $s): ?>
    <div class="border rounded-xl p-3 <?= $s['cls'] ?> cursor-pointer hover:shadow-sm transition-shadow"
        onclick="filterByStatus('<?= strtolower($s['label']) ?>')">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-2 h-2 rounded-full <?= $s['dot'] ?>"></div>
            <span class="text-xs font-medium <?= $s['text'] ?>"><?= $s['label'] ?></span>
        </div>
        <div class="text-2xl font-bold text-slate-800"><?= $s['val'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Search + Filter Kategori Tab -->
<div class="card p-4 mb-5">
    <form method="GET" id="filterForm">
        <!-- Search -->
        <div class="relative mb-4">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" name="search" value="<?= sanitize($search) ?>"
                placeholder="Cari nama komponen..."
                class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            <input type="hidden" name="kategori" id="katInput" value="<?= sanitize($kat) ?>">
        </div>

        <!-- Tab Kategori -->
        <div class="flex gap-2 flex-wrap">
            <button type="button" onclick="setKategori('')"
                class="kat-tab flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all border
                <?= $kat==='' ? 'bg-primary-900 text-white border-primary-900' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' ?>">
                🔍 Semua
                <span class="text-xs opacity-70">(<?= array_sum(array_column($kategoris,'total')) ?>)</span>
            </button>
            <?php foreach ($kategoris as $k): ?>
            <button type="button" onclick="setKategori('<?= addslashes($k['kategori']) ?>')"
                class="kat-tab flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all border relative
                <?= $kat===$k['kategori'] ? 'bg-primary-900 text-white border-primary-900' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' ?>">
                <?= $katIcons[$k['kategori']] ?? '📦' ?>
                <?= sanitize($k['kategori']) ?>
                <span class="text-xs opacity-70">(<?= $k['total'] ?>)</span>
                <?php if ($k['kritis'] > 0): ?>
                <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                    <?= $k['kritis'] ?>
                </span>
                <?php endif; ?>
            </button>
            <?php endforeach; ?>
        </div>
    </form>
</div>

<!-- Grid Komponen -->
<?php if (empty($komponen)): ?>
<div class="card px-5 py-16 text-center">
    <i class="bi bi-search text-5xl text-slate-200 block mb-3"></i>
    <p class="text-slate-400">Tidak ada komponen ditemukan</p>
    <?php if ($search || $kat): ?>
    <a href="<?= BASE_URL ?>/pages/komponen/index.php" class="btn-secondary btn-sm mt-3 inline-flex">Reset Filter</a>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php foreach ($komponen as $k):
        [$cls, $label, $statusKey] = stokStatus($k['stok_tersedia'], $k['stok_minimum']);
        $pct = $k['stok_minimum'] > 0 ? min(100, round($k['stok_tersedia'] / $k['stok_minimum'] * 100)) : 100;
        $barColor = match($statusKey) {
            'habis'  => 'bg-slate-400',
            'kritis' => 'bg-red-500',
            'rendah' => 'bg-amber-500',
            default  => 'bg-green-500'
        };
        $borderClass = in_array($statusKey,['kritis','habis']) ? 'border-red-200' : 'border-slate-100';
        $katIcon = $katIcons[$k['kategori']] ?? '📦';
    ?>
    <div class="card border <?= $borderClass ?> p-5 card-hover <?= $statusKey==='kritis'||$statusKey==='habis' ? 'stok-kritis-pulse' : '' ?>">
        <!-- Header Card -->
        <div class="flex items-start justify-between mb-3">
            <div class="text-2xl"><?= $katIcon ?></div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $cls ?>">
                <?= $label ?>
            </span>
        </div>

        <!-- Nama & Kategori -->
        <h4 class="text-sm font-semibold text-slate-800 mb-0.5 leading-tight line-clamp-2">
            <?= sanitize($k['nama_komponen']) ?>
        </h4>
        <p class="text-xs text-slate-400 mb-4"><?= sanitize($k['kategori']) ?></p>

        <!-- Stok Display -->
        <div class="mb-3">
            <!-- Inline Edit Stok -->
            <div class="flex items-center gap-2 mb-2">
                <div class="flex-1">
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-bold <?= in_array($statusKey,['kritis','habis']) ? 'text-red-600' : 'text-slate-800' ?>"
                            id="stokDisplay_<?= $k['id'] ?>">
                            <?= $k['stok_tersedia'] ?>
                        </span>
                        <span class="text-sm text-slate-400"><?= sanitize($k['satuan']) ?></span>
                    </div>
                    <!-- Edit inline form (hidden by default) -->
                    <div id="stokEdit_<?= $k['id'] ?>" class="hidden flex items-center gap-2 mt-1">
                        <input type="number"
                            id="stokInput_<?= $k['id'] ?>"
                            class="w-20 px-2 py-1 border-2 border-primary-400 rounded-lg text-sm font-bold text-center focus:outline-none"
                            value="<?= $k['stok_tersedia'] ?>" min="0">
                        <button onclick="saveStok(<?= $k['id'] ?>)"
                            class="p-1.5 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                            <i class="bi bi-check text-sm"></i>
                        </button>
                        <button onclick="cancelEdit(<?= $k['id'] ?>)"
                            class="p-1.5 bg-slate-200 text-slate-600 rounded-lg hover:bg-slate-300 transition-colors">
                            <i class="bi bi-x text-sm"></i>
                        </button>
                    </div>
                </div>
                <?php if (isAdminOrGudang()): ?>
                <button onclick="toggleEdit(<?= $k['id'] ?>)"
                    id="editBtn_<?= $k['id'] ?>"
                    class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-primary-600 transition-colors"
                    title="Edit stok langsung">
                    <i class="bi bi-pencil text-sm"></i>
                </button>
                <?php endif; ?>
            </div>
            <p class="text-xs text-slate-400">Min: <?= $k['stok_minimum'] ?> <?= sanitize($k['satuan']) ?></p>
        </div>

        <!-- Progress Bar Stok -->
        <div class="mb-4">
            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full <?= $barColor ?> rounded-full transition-all duration-500"
                    style="width:<?= min(100,$pct) ?>%"></div>
            </div>
            <div class="flex justify-between mt-1">
                <span class="text-xs text-slate-400">0</span>
                <span class="text-xs text-slate-400"><?= $pct ?>% dari minimum</span>
            </div>
        </div>

        <!-- Aksi -->
        <?php if (isAdminOrGudang()): ?>
        <div class="flex gap-2 pt-3 border-t border-slate-50">
            <a href="<?= BASE_URL ?>/pages/komponen/edit.php?id=<?= $k['id'] ?>"
                class="flex-1 btn-secondary btn-sm justify-center">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <form method="POST" action="<?= BASE_URL ?>/api/komponen/delete.php" class="flex-1"
                onsubmit="event.preventDefault(); confirmDelete(this, 'Hapus komponen <?= addslashes($k['nama_komponen']) ?>?')">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                <button type="submit" class="w-full btn-secondary btn-sm justify-center text-red-500 hover:text-red-700">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="flex items-center justify-between mt-5">
    <p class="text-xs text-slate-400">
        Menampilkan <?= $offset+1 ?>–<?= min($offset+$perPage,$totalRows) ?> dari <?= $totalRows ?>
    </p>
    <div class="flex gap-1.5">
        <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
        <a href="?page=<?= $pg ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kat) ?>"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-xs transition-colors
            <?= $pg===$page ? 'bg-primary-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            <?= $pg ?>
        </a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<!-- FAB — Tambah Stok Masuk -->
<?php if (isAdminOrGudang()): ?>
<div class="fixed bottom-8 right-8 z-40">
    <div id="fabMenu" class="hidden mb-3 space-y-2">
        <a href="<?= BASE_URL ?>/pages/material/create.php"
            class="flex items-center gap-2 bg-white border border-slate-200 shadow-lg rounded-full px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-all whitespace-nowrap">
            <i class="bi bi-arrow-left-right text-blue-500"></i>
            Catat Penggunaan
        </a>
        <a href="<?= BASE_URL ?>/pages/komponen/create.php"
            class="flex items-center gap-2 bg-white border border-slate-200 shadow-lg rounded-full px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-all whitespace-nowrap">
            <i class="bi bi-plus-circle text-green-500"></i>
            Tambah Komponen
        </a>
        <button onclick="openModalStok()"
            class="flex items-center gap-2 bg-white border border-slate-200 shadow-lg rounded-full px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-all whitespace-nowrap w-full">
            <i class="bi bi-box-arrow-in-down text-purple-500"></i>
            Stok Masuk
        </button>
    </div>
    <button id="fabBtn" onclick="toggleFab()"
        class="w-14 h-14 bg-primary-900 hover:bg-primary-800 text-white rounded-full shadow-2xl
        flex items-center justify-center transition-all duration-200 hover:scale-110">
        <i class="bi bi-plus-lg text-2xl transition-transform duration-300" id="fabIcon"></i>
    </button>
</div>

<!-- Modal Stok Masuk -->
<div id="modalStokMasuk" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="font-semibold text-slate-800">Stok Masuk</h3>
                <p class="text-xs text-slate-400 mt-0.5">Tambahkan stok komponen yang baru diterima</p>
            </div>
            <button onclick="closeModalStok()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/api/komponen/stok-masuk.php">
            <?= csrfField() ?>
            <div class="space-y-4">
                <div>
                    <label class="form-label">Komponen <span class="text-red-500">*</span></label>
                    <select name="komponen_id" class="form-select" required id="modalKomponenSel"
                        onchange="updateModalStokInfo(this)">
                        <option value="">-- Pilih Komponen --</option>
                        <?php
                        $allKomponen = $db->query("SELECT * FROM komponen ORDER BY nama_komponen")->fetchAll();
                        foreach ($allKomponen as $k): ?>
                        <option value="<?= $k['id'] ?>"
                            data-stok="<?= $k['stok_tersedia'] ?>"
                            data-satuan="<?= sanitize($k['satuan']) ?>">
                            <?= sanitize($k['nama_komponen']) ?> (<?= $k['stok_tersedia'] ?> <?= sanitize($k['satuan']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Info stok saat ini -->
                <div id="modalStokInfo" class="hidden bg-blue-50 border border-blue-200 rounded-xl px-4 py-3">
                    <p class="text-xs text-blue-700 font-medium">Stok saat ini:
                        <span id="modalStokCurrent" class="font-bold"></span>
                    </p>
                </div>

                <div>
                    <label class="form-label">Jumlah Masuk <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 flex border border-slate-200 rounded-xl overflow-hidden">
                            <button type="button" onclick="adjustQty(-1)"
                                class="px-4 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold transition-colors">
                                −
                            </button>
                            <input type="number" name="jumlah_masuk" id="qtyInput"
                                class="flex-1 text-center border-x border-slate-200 py-2.5 text-sm font-semibold focus:outline-none"
                                value="10" min="1">
                            <button type="button" onclick="adjustQty(1)"
                                class="px-4 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold transition-colors">
                                +
                            </button>
                        </div>
                        <span class="text-sm text-slate-500" id="modalSatuan">unit</span>
                    </div>
                </div>

                <div>
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="keterangan" class="form-input"
                        placeholder="Contoh: Pengadaan dari supplier ABC">
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeModalStok()" class="btn-secondary flex-1 justify-center">
                        Batal
                    </button>
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="bi bi-box-arrow-in-down"></i> Simpan Stok Masuk
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
// ============================================================
// TAB KATEGORI FILTER
// ============================================================
function setKategori(kat) {
    document.getElementById('katInput').value = kat;
    document.getElementById('filterForm').submit();
}

// ============================================================
// INLINE EDIT STOK
// ============================================================
const originalStok = {};

function toggleEdit(id) {
    const display = document.getElementById('stokDisplay_' + id);
    const editEl  = document.getElementById('stokEdit_' + id);
    originalStok[id] = parseInt(document.getElementById('stokInput_' + id).value);

    display.classList.toggle('hidden');
    editEl.classList.toggle('hidden');

    if (!editEl.classList.contains('hidden')) {
        document.getElementById('stokInput_' + id).focus();
        document.getElementById('stokInput_' + id).select();
    }
}

function cancelEdit(id) {
    document.getElementById('stokDisplay_' + id).classList.remove('hidden');
    document.getElementById('stokEdit_' + id).classList.add('hidden');
    document.getElementById('stokInput_' + id).value = originalStok[id] || 0;
}

function saveStok(id) {
    const input = document.getElementById('stokInput_' + id);
    const val   = parseInt(input.value);

    if (isNaN(val) || val < 0) {
        Toast?.show('Jumlah stok tidak valid', 'error');
        return;
    }

    // Kirim via fetch AJAX
    const fd = new FormData();
    fd.append('id', id);
    fd.append('stok_tersedia', val);
    fd.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');

    fetch('<?= BASE_URL ?>/api/komponen/update-stok.php', { method:'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('stokDisplay_' + id).textContent = val;
                document.getElementById('stokDisplay_' + id).classList.remove('hidden');
                document.getElementById('stokEdit_' + id).classList.add('hidden');
                Toast?.show('Stok berhasil diperbarui', 'success');
                // Reload setelah 1.5 detik untuk update badge
                setTimeout(() => location.reload(), 1500);
            } else {
                Toast?.show(data.message || 'Gagal memperbarui stok', 'error');
            }
        })
        .catch(() => Toast?.show('Koneksi gagal', 'error'));
}

// Enter untuk save
document.querySelectorAll('[id^="stokInput_"]').forEach(input => {
    input.addEventListener('keydown', e => {
        const id = input.id.replace('stokInput_', '');
        if (e.key === 'Enter')  saveStok(id);
        if (e.key === 'Escape') cancelEdit(id);
    });
});

// ============================================================
// FAB BUTTON
// ============================================================
let fabOpen = false;
function toggleFab() {
    fabOpen = !fabOpen;
    document.getElementById('fabMenu').classList.toggle('hidden', !fabOpen);
    document.getElementById('fabIcon').style.transform = fabOpen ? 'rotate(45deg)' : 'rotate(0)';
}
document.addEventListener('click', e => {
    if (!e.target.closest('.fixed.bottom-8')) {
        if (fabOpen) toggleFab();
    }
});

// ============================================================
// MODAL STOK MASUK
// ============================================================
function openModalStok() {
    document.getElementById('modalStokMasuk').style.display = 'flex';
    document.getElementById('modalStokMasuk').classList.remove('hidden');
    if (fabOpen) toggleFab();
}
function closeModalStok() {
    document.getElementById('modalStokMasuk').classList.add('hidden');
    document.getElementById('modalStokMasuk').style.display = 'none';
}
function updateModalStokInfo(sel) {
    const opt    = sel.options[sel.selectedIndex];
    const stok   = opt.dataset.stok || 0;
    const satuan = opt.dataset.satuan || 'unit';
    const info   = document.getElementById('modalStokInfo');
    info.classList.toggle('hidden', !sel.value);
    document.getElementById('modalStokCurrent').textContent = `${stok} ${satuan}`;
    document.getElementById('modalSatuan').textContent = satuan;
}
function adjustQty(delta) {
    const input = document.getElementById('qtyInput');
    input.value = Math.max(1, (parseInt(input.value) || 0) + delta);
}

// ============================================================
// FILTER BY STATUS (dari summary card)
// ============================================================
function filterByStatus(status) {
    // Kita bisa tambahkan filter di URL jika perlu
    // Untuk sekarang scroll ke komponen dengan status tsb
    const map = { 'aman':'Aman', 'rendah':'Rendah', 'kritis':'Kritis', 'habis':'Habis' };
    Toast?.show(`Filter: Stok ${map[status] || status}`, 'info');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>