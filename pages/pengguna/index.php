<?php
$pageTitle   = 'Kelola Pengguna';
$currentPage = 'pengguna';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$db     = db();
$search = $_GET['search'] ?? '';
$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = '(nama LIKE ? OR email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereStr = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM users WHERE $whereStr");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("SELECT * FROM users WHERE $whereStr ORDER BY role, nama ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$pengguna = $stmt->fetchAll();

$roleConfig = [
    'admin'            => ['label'=>'Admin',            'bg'=>'bg-red-100',    'text'=>'text-red-700',    'dot'=>'bg-red-500'],
    'manajer_proyek'   => ['label'=>'Manajer Proyek',   'bg'=>'bg-blue-100',   'text'=>'text-blue-700',   'dot'=>'bg-blue-500'],
    'teknisi_lapangan' => ['label'=>'Teknisi Lapangan', 'bg'=>'bg-green-100',  'text'=>'text-green-700',  'dot'=>'bg-green-500'],
    'gudang'           => ['label'=>'Gudang',           'bg'=>'bg-purple-100', 'text'=>'text-purple-700', 'dot'=>'bg-purple-500'],
];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">Kelola Pengguna Sistem</h2>
        <p class="text-sm text-slate-400 mt-0.5"><?= $totalRows ?> total pengguna</p>
    </div>
    <a href="<?= BASE_URL ?>/pages/pengguna/create.php" class="btn-primary">
        <i class="bi bi-person-plus"></i> Tambah Pengguna
    </a>
</div>

<!-- Ringkasan per role -->
<?php
$roleCounts = $db->query("SELECT role, COUNT(*) as total FROM users GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <?php foreach ($roleConfig as $roleKey => $rc): ?>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-2 h-8 rounded-full <?= $rc['dot'] ?>"></div>
        <div>
            <div class="text-xl font-bold text-slate-800"><?= $roleCounts[$roleKey] ?? 0 ?></div>
            <div class="text-xs text-slate-400"><?= $rc['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Search -->
<div class="card p-4 mb-4">
    <form method="GET" class="flex gap-3">
        <div class="flex-1 relative">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" name="search" value="<?= sanitize($search) ?>"
                placeholder="Cari nama atau email..."
                class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <button type="submit" class="btn-primary"><i class="bi bi-funnel"></i> Cari</button>
        <?php if ($search): ?>
        <a href="<?= BASE_URL ?>/pages/pengguna/index.php" class="btn-secondary"><i class="bi bi-x-lg"></i> Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="table-header">No</th>
                    <th class="table-header">Pengguna</th>
                    <th class="table-header">Email</th>
                    <th class="table-header">Role</th>
                    <th class="table-header">Status</th>
                    <th class="table-header">Bergabung</th>
                    <th class="table-header">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php if (empty($pengguna)): ?>
                <tr><td colspan="7" class="px-4 py-12 text-center">
                    <i class="bi bi-people text-4xl text-slate-200 block mb-2"></i>
                    <p class="text-sm text-slate-400">Belum ada pengguna</p>
                </td></tr>
                <?php else: ?>
                <?php foreach ($pengguna as $i => $u):
                    $rc = $roleConfig[$u['role']] ?? ['label'=>$u['role'],'bg'=>'bg-slate-100','text'=>'text-slate-600','dot'=>'bg-slate-400'];
                ?>
                <tr class="table-row">
                    <td class="table-cell text-slate-400"><?= $offset + $i + 1 ?></td>
                    <td class="table-cell">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full <?= $rc['dot'] ?> flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
                                <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="font-medium text-slate-800 text-sm"><?= sanitize($u['nama']) ?></div>
                                <?php if ($u['id'] === (int)currentUser()['id']): ?>
                                <span class="text-xs text-primary-600">(Akun Anda)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="table-cell text-sm text-slate-500"><?= sanitize($u['email']) ?></td>
                    <td class="table-cell">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $rc['bg'] ?> <?= $rc['text'] ?>">
                            <?= $rc['label'] ?>
                        </span>
                    </td>
                    <td class="table-cell">
                        <?php if ($u['status'] === 'aktif'): ?>
                        <span class="inline-flex items-center gap-1.5 text-xs text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Aktif
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 text-xs text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span> Nonaktif
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="table-cell text-xs text-slate-400">
                        <?= date('d M Y', strtotime($u['created_at'])) ?>
                    </td>
                    <td class="table-cell">
                        <div class="flex items-center gap-1">
                            <a href="<?= BASE_URL ?>/pages/pengguna/edit.php?id=<?= $u['id'] ?>"
                                class="p-1.5 rounded-lg hover:bg-amber-50 text-slate-400 hover:text-amber-600 transition-colors" title="Edit">
                                <i class="bi bi-pencil text-sm"></i>
                            </a>
                            <?php if ($u['id'] !== (int)currentUser()['id']): ?>
                            <form method="POST" action="<?= BASE_URL ?>/api/pengguna/delete.php" class="inline"
                                onsubmit="return confirm('<?= $u['status']==='aktif' ? 'Nonaktifkan' : 'Aktifkan' ?> pengguna <?= addslashes($u['nama']) ?>?')">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="status_saat_ini" value="<?= $u['status'] ?>">
                                <button class="p-1.5 rounded-lg transition-colors
                                    <?= $u['status']==='aktif' ? 'hover:bg-red-50 text-slate-400 hover:text-red-600' : 'hover:bg-green-50 text-slate-400 hover:text-green-600' ?>"
                                    title="<?= $u['status']==='aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                    <i class="bi <?= $u['status']==='aktif' ? 'bi-person-x' : 'bi-person-check' ?> text-sm"></i>
                                </button>
                            </form>
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
    <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between">
        <p class="text-xs text-slate-400">Menampilkan <?= $offset+1 ?>–<?= min($offset+$perPage,$totalRows) ?> dari <?= $totalRows ?></p>
        <div class="flex gap-1">
            <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
            <a href="?page=<?= $pg ?>&search=<?= urlencode($search) ?>"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-xs transition-colors
                <?= $pg === $page ? 'bg-primary-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                <?= $pg ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>