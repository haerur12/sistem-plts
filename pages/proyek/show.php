<?php
// pages/proyek/show.php — DENGAN TIMELINE + PETA
$pageTitle   = 'Detail Proyek';
$currentPage = 'proyek';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','ID tidak valid.'); redirect(BASE_URL.'/pages/proyek/index.php'); }

$db = db();
$stmt = $db->prepare("
    SELECT p.*, pl.nama_pelanggan, pl.no_telp, pl.email as email_pelanggan
    FROM proyek p
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$proyek = $stmt->fetch();
if (!$proyek) { setFlash('error','Proyek tidak ditemukan.'); redirect(BASE_URL.'/pages/proyek/index.php'); }

$latitude = is_numeric($proyek['latitude'] ?? null) ? (float)$proyek['latitude'] : null;
$longitude = is_numeric($proyek['longitude'] ?? null) ? (float)$proyek['longitude'] : null;
$hasCoordinates = $latitude !== null && $longitude !== null
    && $latitude >= -90 && $latitude <= 90
    && $longitude >= -180 && $longitude <= 180;

// Jadwal
$jadwals = $db->prepare("SELECT * FROM jadwal WHERE proyek_id = ? ORDER BY tanggal_mulai ASC");
$jadwals->execute([$id]);
$jadwals = $jadwals->fetchAll();

// Laporan + foto
$laporans = $db->prepare("
    SELECT l.*, u.nama as nama_teknisi, u.role
    FROM laporan_lapangan l
    JOIN users u ON l.user_id = u.id
    WHERE l.proyek_id = ?
    ORDER BY l.tanggal_laporan ASC, l.created_at ASC
");
$laporans->execute([$id]);
$laporansRaw = $laporans->fetchAll();

// Ambil foto per laporan
foreach ($laporansRaw as &$l) {
    $fotoStmt = $db->prepare("SELECT * FROM foto_lapangan WHERE laporan_id = ? LIMIT 4");
    $fotoStmt->execute([$l['id']]);
    $l['fotos'] = $fotoStmt->fetchAll();
}
unset($l);

// Material
$materials = $db->prepare("
    SELECT pm.*, k.nama_komponen, k.satuan, u.nama as nama_user
    FROM penggunaan_material pm
    JOIN komponen k ON pm.komponen_id = k.id
    JOIN users u ON pm.user_id = u.id
    WHERE pm.proyek_id = ?
    ORDER BY pm.created_at DESC
");
$materials->execute([$id]);
$materials = $materials->fetchAll();

// Role colors
$roleColors = [
    'admin'            => 'from-rose-500 to-red-600',
    'manajer_proyek'   => 'from-sky-500 to-blue-600',
    'teknisi_lapangan' => 'from-emerald-500 to-green-600',
    'gudang'           => 'from-violet-500 to-purple-600',
];

// Timeline events (gabungkan jadwal + laporan)
$timeline = [];
$timeline[] = [
    'type' => 'proyek',
    'tanggal' => $proyek['tanggal_mulai'],
    'judul' => 'Proyek dimulai',
    'data' => $proyek,
];
$timeline[] = [
    'type' => 'proyek',
    'tanggal' => $proyek['tanggal_selesai'],
    'judul' => 'Target penyelesaian',
    'data' => $proyek,
];
foreach ($jadwals as $j) {
    $timeline[] = [
        'type'      => 'jadwal',
        'tanggal'   => $j['tanggal_mulai'],
        'tanggal_end' => $j['tanggal_selesai'],
        'judul'     => $j['keterangan'] ?: 'Jadwal Instalasi',
        'status'    => $j['status'],
        'data'      => $j,
    ];
}
foreach ($laporansRaw as $l) {
    $timeline[] = [
        'type'      => 'laporan',
        'tanggal'   => $l['tanggal_laporan'],
        'judul'     => $l['judul_laporan'],
        'status'    => $l['status_pengerjaan'],
        'data'      => $l,
    ];
}
usort($timeline, fn($a, $b) => strtotime($a['tanggal']) - strtotime($b['tanggal']));

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Header -->
<div class="flex items-start justify-between mb-5 flex-wrap gap-3">
    <div class="flex items-center gap-3">
        <a href="<?= BASE_URL ?>/pages/proyek/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-bold text-slate-900 tracking-tight"><?= sanitize($proyek['nama_proyek']) ?></h2>
            <p class="text-sm text-slate-400"><?= sanitize($proyek['nama_pelanggan']) ?></p>
        </div>
    </div>
    <div class="flex gap-2">
        <?php if (isAdminOrManajer()): ?>
        <a href="<?= BASE_URL ?>/pages/proyek/edit.php?id=<?= $id ?>" class="btn-secondary btn-sm">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Info Proyek -->
    <div class="space-y-4">
        <div class="card p-5">
            <h3 class="text-sm font-bold text-slate-800 tracking-tight mb-4">Informasi Proyek</h3>
            <div class="mb-5">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs text-slate-500">Progres Pengerjaan</span>
                    <span class="text-sm font-bold text-primary-700"><?= $proyek['progres'] ?>%</span>
                </div>
                <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-primary-500 to-primary-600 rounded-full progress-fill" style="width:<?= $proyek['progres'] ?>%"></div>
                </div>
                <div class="mt-2"><?= statusBadge($proyek['status']) ?></div>
            </div>
            <div class="space-y-3 text-sm">
                <div class="flex gap-2">
                    <i class="bi bi-geo-alt text-slate-400 mt-0.5 flex-shrink-0"></i>
                    <span class="text-slate-600"><?= sanitize($proyek['lokasi']) ?></span>
                </div>
                <div class="flex gap-2">
                    <i class="bi bi-calendar-event text-slate-400 flex-shrink-0"></i>
                    <span class="text-slate-600"><?= formatTanggal($proyek['tanggal_mulai']) ?></span>
                </div>
                <div class="flex gap-2">
                    <i class="bi bi-calendar-check text-slate-400 flex-shrink-0"></i>
                    <span class="text-slate-600"><?= formatTanggal($proyek['tanggal_selesai']) ?></span>
                </div>
                <div class="flex gap-2">
                    <i class="bi bi-person text-slate-400 flex-shrink-0"></i>
                    <span class="text-slate-600"><?= sanitize($proyek['nama_pelanggan']) ?></span>
                </div>
                <?php if ($proyek['no_telp']): ?>
                <div class="flex gap-2">
                    <i class="bi bi-telephone text-slate-400 flex-shrink-0"></i>
                    <span class="text-slate-600"><?= sanitize($proyek['no_telp']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($hasCoordinates): ?>
                <div class="flex gap-2">
                    <i class="bi bi-pin-map text-slate-400 mt-0.5 flex-shrink-0"></i>
                    <span class="text-xs text-slate-500 font-mono">
                        <?= number_format($latitude, 6) ?>, <?= number_format($longitude, 6) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($proyek['deskripsi']): ?>
            <div class="mt-4 p-3 bg-slate-50 rounded-lg text-xs text-slate-500">
                <?= sanitize($proyek['deskripsi']) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- PETA LOKASI -->
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2">
                    <i class="bi bi-pin-map-fill text-primary-600"></i>
                    Lokasi Proyek
                </h3>
                <?php if ($hasCoordinates): ?>
                <a href="https://www.google.com/maps?q=<?= $proyek['latitude'] ?>,<?= $proyek['longitude'] ?>" 
                    target="_blank" class="text-xs text-primary-600 hover:text-primary-700 font-medium">
                    <i class="bi bi-box-arrow-up-right"></i> Google Maps
                </a>
                <?php elseif (isAdmin()): ?>
                <a href="<?= BASE_URL ?>/pages/proyek/edit.php?id=<?= $id ?>" class="text-xs text-primary-600 hover:text-primary-700 font-medium">
                    Tambahkan koordinat
                </a>
                <?php endif; ?>
            </div>
            <?php if ($hasCoordinates): ?>
            <div id="map" style="height: 280px;"></div>
            <?php else: ?>
            <div class="px-5 py-8 text-center">
                <i class="bi bi-geo-alt text-3xl text-slate-300 block mb-2"></i>
                <p class="text-sm text-slate-500">Koordinat lokasi belum diisi.</p>
                <p class="text-xs text-slate-400 mt-1">Edit proyek dan masukkan latitude serta longitude untuk menampilkan peta.</p>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!isGudang()): ?>
        <div class="card p-5">
            <h3 class="text-sm font-bold text-slate-800 tracking-tight mb-4">Perbarui Status</h3>
            <form method="POST" action="<?= BASE_URL ?>/api/proyek/update.php">
                <input type="hidden" name="id" value="<?= $id ?>">
                <div class="space-y-3">
                    <div>
                        <label class="form-label text-xs">Status Pengerjaan</label>
                        <select name="status" class="form-select text-sm">
                            <?php foreach (['survei','perencanaan','instalasi','selesai','ditunda'] as $st): ?>
                            <option value="<?= $st ?>" <?= $proyek['status'] === $st ? 'selected' : '' ?>>
                                <?= ucfirst($st) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label text-xs">Progres (%)</label>
                        <input type="number" name="progres" class="form-input text-sm"
                            value="<?= $proyek['progres'] ?>" min="0" max="100">
                    </div>
                    <button type="submit" class="btn-primary w-full justify-center text-xs">
                        <i class="bi bi-arrow-repeat"></i> Perbarui Status
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php if (isAdminOrManajer()): ?>
        <a href="<?= BASE_URL ?>/pages/jadwal/create.php?proyek_id=<?= $id ?>" class="btn-secondary w-full justify-center">
            <i class="bi bi-calendar-plus"></i> Tambah Jadwal
        </a>
        <?php endif; ?>
    </div>

    <!-- Konten Detail -->
    <div class="lg:col-span-2 space-y-4">
        
        <!-- PROGRESS TIMELINE -->
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2">
                    <i class="bi bi-timeline text-primary-600"></i>
                    Progress Timeline
                </h3>
                <span class="text-xs text-slate-400"><?= count($timeline) ?> milestone</span>
            </div>
            
            <?php if (empty($timeline)): ?>
            <div class="px-5 py-10 text-center">
                <i class="bi bi-clock-history text-4xl text-slate-200 block mb-2"></i>
                <p class="text-sm text-slate-400">Belum ada milestone</p>
            </div>
            <?php else: ?>
            <div class="p-5">
                <div class="relative">
                    <!-- Vertical Line -->
                    <div class="absolute left-[19px] top-2 bottom-2 w-0.5 bg-slate-200"></div>
                    
                    <div class="space-y-5">
                        <?php foreach ($timeline as $t):
                            if ($t['type'] === 'laporan'):
                                $l = $t['data'];
                                $roleColor = $roleColors[$l['role']] ?? 'from-slate-500 to-slate-600';
                                $inisial = strtoupper(substr($l['nama_teknisi'], 0, 1));
                                $statusLaporan = $l['status_pengerjaan'] ?? $t['status'] ?? 'belum_mulai';
                                $statusColor = [
                                    'belum_mulai'  => 'bg-slate-400',
                                    'dalam_proses' => 'bg-blue-500',
                                    'selesai'      => 'bg-emerald-500',
                                    'terkendala'   => 'bg-red-500',
                                ][$statusLaporan] ?? 'bg-slate-400';
                            ?>
                            <div class="relative flex gap-4">
                                <!-- Dot -->
                                <div class="relative z-10 flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br <?= $roleColor ?> flex items-center justify-center text-white font-bold text-sm ring-4 ring-white shadow-md">
                                        <?= $inisial ?>
                                    </div>
                                    <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full <?= $statusColor ?> ring-2 ring-white"></div>
                                </div>
                                
                                <!-- Content -->
                                <div class="flex-1 min-w-0 card p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-[10px] font-bold uppercase tracking-wider">
                                                    <i class="bi bi-file-earmark-text"></i> Laporan
                                                </span>
                                                <?= statusBadge($l['status_pengerjaan']) ?>
                                            </div>
                                            <a href="<?= BASE_URL ?>/pages/laporan/show.php?id=<?= $l['id'] ?>" 
                                                class="text-sm font-semibold text-slate-800 hover:text-primary-600 block">
                                                <?= sanitize($l['judul_laporan']) ?>
                                            </a>
                                            <p class="text-xs text-slate-400 mt-1">
                                                <i class="bi bi-person"></i> <?= sanitize($l['nama_teknisi']) ?>
                                                <span class="mx-1">·</span>
                                                <i class="bi bi-calendar3"></i> <?= formatTanggal($l['tanggal_laporan']) ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <p class="text-xs text-slate-600 leading-relaxed mb-3">
                                        <?= mb_strimwidth(sanitize($l['deskripsi']), 0, 180, '...') ?>
                                    </p>
                                    
                                    <?php if (!empty($l['fotos'])): ?>
                                    <div class="flex gap-1.5 flex-wrap">
                                        <?php foreach ($l['fotos'] as $f): 
                                            $fotoUrl = (str_starts_with($f['path_file'], 'assets/') || str_starts_with($f['path_file'], 'uploads/')) 
                                                ? BASE_URL . '/' . ltrim($f['path_file'], '/') 
                                                : BASE_URL . '/uploads/' . ltrim($f['path_file'], '/');
                                        ?>
                                        <a href="<?= $fotoUrl ?>" target="_blank" 
                                            class="group relative">
                                            <img src="<?= $fotoUrl ?>" 
                                                class="w-16 h-16 object-cover rounded-lg border border-slate-200 group-hover:border-primary-400 transition-colors">
                                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 rounded-lg transition-colors flex items-center justify-center">
                                                <i class="bi bi-zoom-in text-white opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                            </div>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php else:
                                $isProjectMilestone = $t['type'] === 'proyek';
                                $j = $t['data'];
                                $statusColor = $isProjectMilestone
                                    ? 'bg-primary-500'
                                    : ([
                                        'terjadwal' => 'bg-slate-400',
                                        'berjalan'  => 'bg-blue-500',
                                        'selesai'   => 'bg-emerald-500',
                                        'ditunda'   => 'bg-amber-500',
                                    ][$j['status']] ?? 'bg-slate-400');
                                $eventTitle = $isProjectMilestone ? $t['judul'] : ($j['keterangan'] ?: 'Jadwal Instalasi');
                            ?>
                            <div class="relative flex gap-4">
                                <div class="relative z-10 flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-white border-2 border-slate-300 flex items-center justify-center shadow-sm">
                                        <i class="bi bi-calendar3 text-slate-600"></i>
                                    </div>
                                    <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full <?= $statusColor ?> ring-2 ring-white"></div>
                                </div>
                                <div class="flex-1 min-w-0 card p-4 bg-slate-50/50">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 bg-slate-200 text-slate-700 rounded text-[10px] font-bold uppercase tracking-wider">
                                            <i class="bi <?= $isProjectMilestone ? 'bi-flag' : 'bi-calendar-event' ?>"></i>
                                            <?= $isProjectMilestone ? 'Proyek' : 'Jadwal' ?>
                                        </span>
                                        <?= statusBadge($isProjectMilestone ? $proyek['status'] : $j['status']) ?>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-800"><?= sanitize($eventTitle) ?></p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        <i class="bi bi-calendar3"></i>
                                        <?php if ($isProjectMilestone): ?>
                                        <?= formatTanggal($t['tanggal']) ?>
                                        <?php else: ?>
                                        <?= formatTanggal($j['tanggal_mulai']) ?> — <?= formatTanggal($j['tanggal_selesai']) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Jadwal Instalasi -->
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800 tracking-tight">
                    <i class="bi bi-calendar3 text-primary-600 mr-1"></i> Jadwal Instalasi
                </h3>
                <span class="text-xs text-slate-400"><?= count($jadwals) ?> jadwal</span>
            </div>
            <?php if (empty($jadwals)): ?>
            <div class="px-5 py-8 text-center">
                <i class="bi bi-calendar-x text-3xl text-slate-200 block mb-2"></i>
                <p class="text-sm text-slate-400">Belum ada jadwal instalasi</p>
            </div>
            <?php else: ?>
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="table-header">Mulai</th>
                        <th class="table-header">Selesai</th>
                        <th class="table-header">Keterangan</th>
                        <th class="table-header">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($jadwals as $j): ?>
                    <tr class="table-row">
                        <td class="table-cell text-xs"><?= formatTanggalShort($j['tanggal_mulai']) ?></td>
                        <td class="table-cell text-xs"><?= formatTanggalShort($j['tanggal_selesai']) ?></td>
                        <td class="table-cell text-xs text-slate-500"><?= sanitize($j['keterangan'] ?? '-') ?></td>
                        <td class="table-cell"><?= statusBadge($j['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Penggunaan Material -->
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800 tracking-tight">
                    <i class="bi bi-boxes text-amber-600 mr-1"></i> Penggunaan Material
                </h3>
                <span class="text-xs text-slate-400"><?= count($materials) ?> catatan</span>
            </div>
            <?php if (empty($materials)): ?>
            <div class="px-5 py-8 text-center">
                <i class="bi bi-inbox text-3xl text-slate-200 block mb-2"></i>
                <p class="text-sm text-slate-400">Belum ada catatan penggunaan material</p>
            </div>
            <?php else: ?>
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="table-header">Komponen</th>
                        <th class="table-header">Jumlah</th>
                        <th class="table-header">Dicatat Oleh</th>
                        <th class="table-header">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($materials as $m): ?>
                    <tr class="table-row">
                        <td class="table-cell text-sm font-medium"><?= sanitize($m['nama_komponen']) ?></td>
                        <td class="table-cell">
                            <span class="text-red-600 font-medium text-sm">-<?= $m['jumlah_digunakan'] ?></span>
                            <span class="text-xs text-slate-400"> <?= sanitize($m['satuan']) ?></span>
                        </td>
                        <td class="table-cell text-xs text-slate-500"><?= sanitize($m['nama_user']) ?></td>
                        <td class="table-cell text-xs text-slate-500"><?= formatTanggalShort($m['tanggal_penggunaan']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($hasCoordinates): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof L === 'undefined') {
        document.getElementById('map').innerHTML =
            '<div class="h-full flex items-center justify-center px-5 text-center text-sm text-red-600">Peta gagal dimuat karena pustaka peta tidak tersedia. Periksa koneksi internet lalu muat ulang halaman.</div>';
        return;
    }
    const map = L.map('map').setView([<?= json_encode($latitude) ?>, <?= json_encode($longitude) ?>], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19
    }).addTo(map);
    
    const customIcon = L.divIcon({
        className: 'custom-marker',
        html: `<div style="background: #4f46e5; width: 32px; height: 32px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 3px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-sun-fill" style="color: white; transform: rotate(45deg); font-size: 14px;"></i>
        </div>`,
        iconSize: [32, 32],
        iconAnchor: [16, 32]
    });
    
    const popup = document.createElement('div');
    const title = document.createElement('strong');
    title.textContent = <?= json_encode($proyek['nama_proyek'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    title.style.color = '#1e293b';
    title.style.fontSize = '13px';
    popup.appendChild(title);
    const location = document.createElement('p');
    location.textContent = <?= json_encode($proyek['lokasi'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    location.style.cssText = 'color:#64748b;font-size:11px;margin:4px 0 0';
    popup.appendChild(location);

    L.marker([<?= json_encode($latitude) ?>, <?= json_encode($longitude) ?>], {icon: customIcon})
        .addTo(map)
        .bindPopup(popup)
        .openPopup();
    
    setTimeout(() => map.invalidateSize(), 100);
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>