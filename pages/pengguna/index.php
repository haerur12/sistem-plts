<?php
// pages/pengguna/index.php — CARD GRID VISUAL
$pageTitle   = 'Data Pengguna';
$currentPage = 'pengguna';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin']);

$db = db();

$filterRole   = $_GET['role'] ?? '';
$search       = $_GET['search'] ?? '';

$where  = ['1=1'];
$params = [];
if ($filterRole) { $where[] = 'role = ?'; $params[] = $filterRole; }
if ($search)     { $where[] = '(nama LIKE ? OR email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereStr = implode(' AND ', $where);

$stmt = $db->prepare("SELECT * FROM users WHERE $whereStr ORDER BY nama ASC");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Hitung per role
$roleCounts = $db->query("SELECT role, COUNT(*) as total FROM users GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);

$roleLabels = [
    'admin'            => ['Admin',            'from-rose-500 to-red-600',       'bg-rose-50 text-rose-700 ring-rose-200'],
    'manajer_proyek'   => ['Manajer Proyek',   'from-sky-500 to-blue-600',       'bg-sky-50 text-sky-700 ring-sky-200'],
    'teknisi_lapangan' => ['Teknisi Lapangan', 'from-emerald-500 to-green-600',  'bg-emerald-50 text-emerald-700 ring-emerald-200'],
    'gudang'           => ['Gudang',           'from-violet-500 to-purple-600',  'bg-violet-50 text-violet-700 ring-violet-200'],
];

$tabs = [
    ''       => ['Semua',      'bi-people-fill',     (int) array_sum($roleCounts)],
    'admin'  => ['Admin',      'bi-shield-lock',     (int)($roleCounts['admin'] ?? 0)],
    'manajer_proyek'   => ['Manajer',   'bi-briefcase',       (int)($roleCounts['manajer_proyek'] ?? 0)],
    'teknisi_lapangan' => ['Teknisi',   'bi-tools',           (int)($roleCounts['teknisi_lapangan'] ?? 0)],
    'gudang' => ['Gudang',    'bi-warehouse',       (int)($roleCounts['gudang'] ?? 0)],
];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div>
        <h2 class="text-lg font-bold text-slate-900 tracking-tight">Data Pengguna</h2>
        <p class="text-[13px] text-slate-500 mt-1"><?= count($users) ?> pengguna terdaftar</p>
    </div>
    <a href="<?= BASE_URL ?>/pages/pengguna/create.php" class="btn-primary">
        <i class="bi bi-plus-lg"></i> Tambah Pengguna
    </a>
</div>

<!-- Tab Filter Role -->
<div class="card p-2 mb-4">
    <div class="flex gap-1 overflow-x-auto">
        <?php foreach ($tabs as $val => [$label, $icon, $count]): ?>
        <a href="?role=<?= urlencode($val) ?>&search=<?= urlencode($search) ?>"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors
            <?= $filterRole === $val ? 'bg-primary-600 text-white shadow-sm shadow-primary-600/30' : 'text-slate-600 hover:bg-slate-100' ?>">
            <i class="bi <?= $icon ?>"></i>
            <span><?= $label ?></span>
            <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold <?= $filterRole === $val ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600' ?>">
                <?= $count ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Search -->
<div class="card p-4 mb-4">
    <form method="GET" class="flex gap-3">
        <input type="hidden" name="role" value="<?= sanitize($filterRole) ?>">
        <div class="flex-1 relative">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" name="search" value="<?= sanitize($search) ?>"
                placeholder="Cari nama atau email..."
                class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-4 focus:ring-primary-500/15 focus:border-primary-500 transition-all">
        </div>
        <button type="submit" class="btn-primary"><i class="bi bi-funnel"></i> Cari</button>
        <?php if ($search): ?>
        <a href="?role=<?= urlencode($filterRole) ?>" class="btn-secondary"><i class="bi bi-x-lg"></i> Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Card Grid -->
<?php if (empty($users)): ?>
<div class="card px-5 py-16 text-center">
    <span class="empty-icon mb-3 !w-16 !h-16"><i class="bi bi-people"></i></span>
    <p class="text-slate-400">Tidak ada pengguna ditemukan</p>
</div>
<?php else: ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php foreach ($users as $u):
        [$roleLabel, $roleColor, $roleBadge] = $roleLabels[$u['role']] ?? ['Unknown','from-slate-500 to-slate-600','bg-slate-100 text-slate-600 ring-slate-200'];
        $inisial = strtoupper(substr($u['nama'], 0, 2));
        $status  = isUserOnline($u['id']);
    ?>
    <div class="card card-hover overflow-hidden group">
        <!-- Header gradient -->
        <div class="h-20 bg-gradient-to-br <?= $roleColor ?> relative">
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 50%, white 1px, transparent 1px); background-size: 20px 20px;"></div>
            <!-- Status badge -->
            <div class="absolute top-3 right-3">
                <?php if ($u['status'] === 'aktif'): ?>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-white/20 backdrop-blur rounded-full text-[10px] font-bold text-white">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-300"></span>Aktif
                </span>
                <?php else: ?>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-black/20 backdrop-blur rounded-full text-[10px] font-bold text-white/80">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>Nonaktif
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Avatar -->
        <div class="px-5 -mt-10 relative">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br <?= $roleColor ?> flex items-center justify-center text-white font-bold text-2xl shadow-lg ring-4 ring-white">
                <?= $inisial ?>
            </div>
        </div>

        <!-- Info -->
        <div class="px-5 pt-3 pb-5">
            <h4 class="text-base font-bold text-slate-900 truncate"><?= sanitize($u['nama']) ?></h4>
            <p class="text-xs text-slate-500 truncate mt-0.5"><?= sanitize($u['email']) ?></p>

            <div class="mt-3 flex items-center gap-2 flex-wrap">
                <span class="badge-modern inline-flex items-center gap-1 rounded-full text-[11px] font-semibold ring-1 <?= $roleBadge ?>">
                    <i class="bi bi-shield-check text-[10px]"></i>
                    <?= $roleLabel ?>
                </span>
            </div>

            <!-- Online status -->
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500 flex items-center gap-1.5">
                    <i class="bi bi-clock"></i>
                    <?= formatLastSeen($status['last_seen'], $status['online']) ?>
                </span>
            </div>

            <!-- Actions -->
            <div class="mt-3 flex gap-2">
                <a href="<?= BASE_URL ?>/pages/pengguna/edit.php?id=<?= $u['id'] ?>"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-slate-50 hover:bg-amber-50 text-slate-600 hover:text-amber-700 rounded-lg text-xs font-semibold transition-colors border border-slate-200 hover:border-amber-200">
                    <i class="bi bi-pencil text-[11px]"></i> Edit
                </a>
                <?php if ($u['id'] != $user['id']): ?>
                <button onclick="confirmDeleteUser(<?= $u['id'] ?>, '<?= addslashes($u['nama']) ?>')"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-slate-50 hover:bg-red-50 text-slate-600 hover:text-red-700 rounded-lg text-xs font-semibold transition-colors border border-slate-200 hover:border-red-200">
                    <i class="bi bi-trash text-[11px]"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal Hapus -->
<div id="modalHapus" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden" onclick="closeModal()">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm mx-4" onclick="event.stopPropagation()">
        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-exclamation-triangle text-red-600 text-xl"></i>
        </div>
        <h3 class="text-center font-semibold text-slate-800 mb-2">Hapus Pengguna?</h3>
        <p class="text-center text-sm text-slate-500 mb-6" id="modalDesc">Tindakan ini tidak dapat dibatalkan.</p>
        <div class="flex gap-3">
            <button onclick="closeModal()" class="flex-1 btn-secondary justify-center">Batal</button>
            <form method="POST" action="<?= BASE_URL ?>/api/pengguna/delete.php" class="flex-1">
                <input type="hidden" name="id" id="deleteId">
                <button type="submit" class="w-full btn-danger justify-center">
                    <i class="bi bi-trash"></i> Hapus
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDeleteUser(id, nama) {
    document.getElementById('deleteId').value = id;
    document.getElementById('modalDesc').textContent = `Pengguna "${nama}" akan dihapus permanen.`;
    document.getElementById('modalHapus').classList.remove('hidden');
}
function closeModal() {
    document.getElementById('modalHapus').classList.add('hidden');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>