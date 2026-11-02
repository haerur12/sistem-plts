<?php
// pages/proyek/show.php — DETAIL PROYEK + TIM YANG DITEMPATKAN
$pageTitle   = 'Detail Proyek';
$currentPage = 'proyek';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/activity.php';
requireRole(['admin', 'manajer_proyek', 'teknisi_lapangan']);

$db = db();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    setFlash('error', 'ID proyek tidak valid.');
    redirect(BASE_URL . '/pages/proyek/index.php');
}

// Ambil data proyek + pelanggan
$stmt = $db->prepare("
    SELECT p.*, pl.nama_pelanggan, pl.no_telp, pl.email AS email_pelanggan, pl.alamat AS alamat_pelanggan
    FROM proyek p
    JOIN pelanggan pl ON p.pelanggan_id = pl.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$proyek = $stmt->fetch();

if (!$proyek) {
    setFlash('error', 'Proyek tidak ditemukan.');
    redirect(BASE_URL . '/pages/proyek/index.php');
}

// Ambil daftar Tim yang ditempatkan di proyek ini (dari tabel proyek_tim)
$timProyek = [];
try {
    $stmtTim = $db->prepare("
        SELECT pt.*, u.email, u.role AS role_sistem, u.is_online, u.last_seen,
               (SELECT COUNT(*) FROM laporan_lapangan l WHERE l.proyek_id = pt.proyek_id AND l.user_id = pt.user_id) AS total_laporan
        FROM proyek_tim pt
        LEFT JOIN users u ON pt.user_id = u.id
        WHERE pt.proyek_id = ?
        ORDER BY pt.id ASC
    ");
    $stmtTim->execute([$id]);
    $timProyek = $stmtTim->fetchAll();
} catch (Exception $e) {
    // Jika tabel proyek_tim belum dibuat, biarkan kosong agar tidak error
    $timProyek = [];
}

// Daftar semua user aktif untuk modal tambah tim
$allUsers = $db->query("SELECT id, nama, role FROM users WHERE status = 'aktif' ORDER BY nama ASC")->fetchAll();

// Ambil jadwal instalasi proyek
$stmtJadwal = $db->prepare("SELECT * FROM jadwal WHERE proyek_id = ? ORDER BY tanggal_mulai ASC");
$stmtJadwal->execute([$id]);
$jadwals = $stmtJadwal->fetchAll();

// Ambil laporan lapangan proyek
$stmtLaporan = $db->prepare("
    SELECT l.*, u.nama AS nama_teknisi,
           (SELECT COUNT(*) FROM foto_lapangan f WHERE f.laporan_id = l.id) AS jml_foto
    FROM laporan_lapangan l
    JOIN users u ON l.user_id = u.id
    WHERE l.proyek_id = ?
    ORDER BY l.tanggal_laporan DESC, l.id DESC
");
$stmtLaporan->execute([$id]);
$laporans = $stmtLaporan->fetchAll();

// Ambil penggunaan material proyek
$stmtMaterial = $db->prepare("
    SELECT pm.*, k.nama_komponen, k.kategori, k.satuan, u.nama AS dicatat_oleh
    FROM penggunaan_material pm
    JOIN komponen k ON pm.komponen_id = k.id
    JOIN users u ON pm.user_id = u.id
    WHERE pm.proyek_id = ?
    ORDER BY pm.tanggal_penggunaan DESC
");
$stmtMaterial->execute([$id]);
$materials = $stmtMaterial->fetchAll();

// Hitung sisa hari kontrak
$tMulai   = new DateTime($proyek['tanggal_mulai']);
$tSelesai = new DateTime($proyek['tanggal_selesai']);
$tNow     = new DateTime(date('Y-m-d'));
$totalDurasi = max(1, (int)$tMulai->diff($tSelesai)->days);
$sisaHari    = (int)$tNow->diff($tSelesai)->format('%r%a');

$daftarJabatan = [
    'Project Manager',
    'Site Manager',
    'Pengawas Teknik',
    'HSE / Safetyman',
    'Teknisi Utama',
    'Teknisi Lapangan',
    'Scaffolder',
    'Helper',
    'Admin Proyek'
];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <a href="<?= BASE_URL ?>/pages/proyek/index.php">Proyek</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current"><?= sanitize($proyek['nama_proyek']) ?></span>
    </nav>

    <!-- Header Detail Proyek -->
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div class="flex items-center gap-3.5">
            <a href="<?= BASE_URL ?>/pages/proyek/index.php"
               class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors shadow-2xs">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight"><?= sanitize($proyek['nama_proyek']) ?></h2>
                    <?= statusBadge($proyek['status']) ?>
                </div>
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1 font-semibold text-slate-700">
                        <i class="bi bi-building text-primary-600"></i> <?= sanitize($proyek['nama_pelanggan']) ?>
                    </span>
                    <span class="text-slate-300">·</span>
                    <span class="inline-flex items-center gap-1">
                        <i class="bi bi-geo-alt text-amber-500"></i> <?= sanitize($proyek['lokasi']) ?>
                    </span>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= BASE_URL ?>/pages/laporan/create.php?proyek_id=<?= $proyek['id'] ?>" class="btn-secondary btn-sm">
                <i class="bi bi-clipboard2-plus text-emerald-600"></i> Buat Daily Report
            </a>
            <?php if (isAdminOrManajer()): ?>
            <button type="button" onclick="openModalTambahTim()" class="btn-secondary btn-sm">
                <i class="bi bi-person-plus-fill text-primary-600"></i> Tambah Personel Tim
            </button>
            <?php endif; ?>
            <?php if (isAdmin()): ?>
            <a href="<?= BASE_URL ?>/pages/proyek/edit.php?id=<?= $proyek['id'] ?>" class="btn-primary btn-sm">
                <i class="bi bi-pencil-square"></i> Edit Proyek
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Ringkasan Proyek -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card p-4 stat-card" style="--stat-accent: #4f46e5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Capaian Progres</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int)$proyek['progres'] ?>%</p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="bi bi-speedometer2 text-lg"></i>
                </span>
            </div>
            <div class="h-1.5 progress-track rounded-full overflow-hidden mt-3">
                <div class="h-full progress-fill rounded-full" style="width: <?= (int)$proyek['progres'] ?>%"></div>
            </div>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: #10b981">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tim Ditempatkan</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= count($timProyek) ?> <span class="text-xs font-semibold text-slate-400">Orang</span></p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="bi bi-people-fill text-lg"></i>
                </span>
            </div>
            <p class="text-[11px] text-slate-400 mt-2">Personel aktif di lokasi proyek</p>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: #0ea5e9">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Laporan Masuk</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= count($laporans) ?> <span class="text-xs font-semibold text-slate-400">Laporan</span></p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i class="bi bi-file-earmark-text-fill text-lg"></i>
                </span>
            </div>
            <p class="text-[11px] text-slate-400 mt-2"><?= count($jadwals) ?> tahapan jadwal terdaftar</p>
        </div>

        <div class="card p-4 stat-card" style="--stat-accent: <?= $sisaHari < 0 && $proyek['status'] !== 'selesai' ? '#ef4444' : '#f59e0b' ?>">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Durasi Kontrak</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= $totalDurasi ?> <span class="text-xs font-semibold text-slate-400">Hari</span></p>
                </div>
                <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="bi bi-calendar-range-fill text-lg"></i>
                </span>
            </div>
            <p class="text-[11px] <?= $sisaHari < 0 && $proyek['status'] !== 'selesai' ? 'text-red-600 font-bold' : 'text-slate-400' ?> mt-2">
                <?= formatTanggalShort($proyek['tanggal_mulai']) ?> — <?= formatTanggalShort($proyek['tanggal_selesai']) ?>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <!-- KOLOM KIRI: TIM PROYEK & LAPORAN LAPANGAN -->
        <div class="xl:col-span-8 space-y-6">

            <!-- ==========================================================
                 SEKSI UTAMA: DAFTAR TIM & PERSONEL YANG DITEMPATKAN
            =========================================================== -->
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i class="bi bi-people-fill"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Daftar Personel & Tim yang Ditempatkan</h3>
                            <p class="text-xs text-slate-400">Susunan tim pelaksana dan penanggung jawab di lokasi proyek</p>
                        </div>
                    </div>

                    <?php if (isAdminOrManajer()): ?>
                    <button type="button" onclick="openModalTambahTim()" class="btn-primary btn-sm">
                        <i class="bi bi-person-plus"></i> Tempatkan Personel
                    </button>
                    <?php endif; ?>
                </div>

                <div class="p-6">
                    <?php if (empty($timProyek)): ?>
                    <div class="py-8 text-center bg-slate-50/70 rounded-2xl border border-dashed border-slate-200">
                        <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 text-slate-400 flex items-center justify-center mx-auto mb-2.5 shadow-2xs">
                            <i class="bi bi-person-badge text-xl"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-700">Belum Ada Personel yang Ditempatkan</p>
                        <p class="text-xs text-slate-400 mt-0.5 mb-3">Tambahkan Project Manager, Site Manager, HSE, atau Teknisi untuk proyek ini.</p>
                        <?php if (isAdminOrManajer()): ?>
                        <button type="button" onclick="openModalTambahTim()" class="btn-secondary btn-sm">
                            <i class="bi bi-plus-lg"></i> Tambah Tim Sekarang
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <?php
                        $roleBadgeStyle = [
                            'Project Manager'  => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
                            'Site Manager'     => 'bg-sky-50 text-sky-700 ring-sky-200',
                            'Pengawas Teknik'  => 'bg-blue-50 text-blue-700 ring-blue-200',
                            'HSE / Safetyman'  => 'bg-amber-50 text-amber-700 ring-amber-200',
                            'Teknisi Utama'    => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                            'Teknisi Lapangan' => 'bg-teal-50 text-teal-700 ring-teal-200',
                        ];
                        foreach ($timProyek as $tp):
                            $inisial = strtoupper(substr($tp['nama_personel'], 0, 1));
                            $badgeCls = $roleBadgeStyle[$tp['peran_proyek']] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
                            $isOnline = !empty($tp['user_id']) && $tp['is_online'] && $tp['last_seen'] && (time() - strtotime($tp['last_seen'])) < 300;
                        ?>
                        <div class="p-4 rounded-2xl border border-slate-200/80 bg-white hover:shadow-md transition-all flex items-start justify-between gap-3 group">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="relative flex-shrink-0">
                                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-primary-600 to-indigo-700 text-white font-bold text-sm flex items-center justify-center shadow-xs">
                                        <?= $inisial ?>
                                    </div>
                                    <?php if (!empty($tp['user_id'])): ?>
                                    <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-white <?= $isOnline ? 'bg-emerald-500' : 'bg-slate-300' ?>"
                                          title="<?= $isOnline ? 'Sedang Online' : 'Offline' ?>"></span>
                                    <?php endif; ?>
                                </div>

                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <h4 class="text-sm font-bold text-slate-900 truncate"><?= sanitize($tp['nama_personel']) ?></h4>
                                        <?php if (!empty($tp['user_id'])): ?>
                                        <span class="text-[10px] px-1.5 py-0.2 rounded bg-emerald-50 text-emerald-700 font-bold border border-emerald-200" title="Terhubung dengan Akun Sistem">
                                            Akun Aktif
                                        </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mt-1">
                                        <span class="badge-modern ring-1 <?= $badgeCls ?> text-[11px] py-0.5 px-2.5">
                                            <?= sanitize($tp['peran_proyek']) ?>
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-3 mt-2 text-[11px] text-slate-400">
                                        <?php if (!empty($tp['user_id'])): ?>
                                        <span><i class="bi bi-file-earmark-check text-primary-500"></i> <?= (int)$tp['total_laporan'] ?> laporan dikirim</span>
                                        <?php endif; ?>
                                        <?php if (!empty($tp['no_hp'])): ?>
                                        <span><i class="bi bi-whatsapp text-emerald-500"></i> <?= sanitize($tp['no_hp']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if (isAdminOrManajer()): ?>
                            <form method="POST" action="<?= BASE_URL ?>/api/proyek/tim.php"
                                  onsubmit="event.preventDefault(); confirmDelete(this, 'Keluarkan <?= addslashes(sanitize($tp['nama_personel'])) ?> dari tim proyek ini?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="proyek_id" value="<?= $proyek['id'] ?>">
                                <input type="hidden" name="tim_id" value="<?= $tp['id'] ?>">
                                <button type="submit" class="p-1.5 rounded-lg text-slate-300 hover:text-red-600 hover:bg-red-50 transition-colors" title="Keluarkan dari proyek">
                                    <i class="bi bi-x-lg text-xs"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Riwayat Laporan Harian Lapangan (Daily Report) -->
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                            <i class="bi bi-clipboard2-pulse-fill"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Riwayat Daily Report Proyek</h3>
                            <p class="text-xs text-slate-400">Laporan harian yang dikirimkan oleh tim lapangan</p>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/pages/laporan/create.php?proyek_id=<?= $proyek['id'] ?>" class="btn-secondary btn-sm">
                        <i class="bi bi-plus-lg"></i> Tambah Laporan
                    </a>
                </div>

                <?php if (empty($laporans)): ?>
                <div class="p-8 text-center text-sm text-slate-400">Belum ada laporan lapangan untuk proyek ini.</div>
                <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($laporans as $l): ?>
                    <div class="px-6 py-4 hover:bg-slate-50/80 transition-colors flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <a href="<?= BASE_URL ?>/pages/laporan/show.php?id=<?= $l['id'] ?>" class="text-sm font-bold text-slate-800 hover:text-primary-600 truncate">
                                    <?= sanitize($l['judul_laporan']) ?>
                                </a>
                                <?php if (!empty($l['area_kerja'])): ?>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200">
                                    <?= sanitize($l['area_kerja']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-slate-400 mt-1 flex items-center gap-2 flex-wrap">
                                <span><i class="bi bi-calendar3"></i> <?= formatTanggal($l['tanggal_laporan']) ?></span>
                                <span>·</span>
                                <span><i class="bi bi-person"></i> <?= sanitize($l['nama_teknisi']) ?></span>
                                <?php if ((int)$l['jml_foto'] > 0): ?>
                                <span>·</span>
                                <span class="text-primary-600 font-semibold"><i class="bi bi-images"></i> <?= $l['jml_foto'] ?> foto</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <?= statusBadge($l['status_pengerjaan']) ?>
                            <a href="<?= BASE_URL ?>/pages/laporan/show.php?id=<?= $l['id'] ?>" class="btn-secondary btn-sm">Detail</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Penggunaan Material Proyek -->
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <i class="bi bi-box-seam-fill"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Material & Komponen Terpakai</h3>
                            <p class="text-xs text-slate-400">Daftar inventaris gudang yang dialokasikan ke proyek ini</p>
                        </div>
                    </div>
                </div>

                <?php if (empty($materials)): ?>
                <div class="p-8 text-center text-sm text-slate-400">Belum ada pencatatan keluar material untuk proyek ini.</div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="table-header">Tanggal</th>
                                <th class="table-header">Komponen</th>
                                <th class="table-header">Jumlah</th>
                                <th class="table-header">Dicatat Oleh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($materials as $m): ?>
                            <tr class="table-row">
                                <td class="table-cell text-xs text-slate-500"><?= formatTanggalShort($m['tanggal_penggunaan']) ?></td>
                                <td class="table-cell">
                                    <div class="text-sm font-bold text-slate-800"><?= sanitize($m['nama_komponen']) ?></div>
                                    <div class="text-[11px] text-slate-400"><?= sanitize($m['keterangan'] ?: $m['kategori']) ?></div>
                                </td>
                                <td class="table-cell font-bold text-red-600">-<?= (int)$m['jumlah_digunakan'] ?> <?= sanitize($m['satuan']) ?></td>
                                <td class="table-cell text-xs text-slate-500"><?= sanitize($m['dicatat_oleh']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- KOLOM KANAN: INFO PELANGGAN, UPDATE CEPAT & JADWAL -->
        <div class="xl:col-span-4 space-y-6">

            <!-- Quick Update Status & Progres (Khusus Admin / Manajer) -->
            <?php if (isAdminOrManajer()): ?>
            <div class="card p-5">
                <h3 class="text-sm font-bold text-slate-900 mb-3 pb-2.5 border-b border-slate-100 flex items-center gap-2">
                    <i class="bi bi-sliders text-primary-600"></i> Update Cepat Status & Progres
                </h3>
                <form method="POST" action="<?= BASE_URL ?>/api/proyek/update.php" class="space-y-4">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $proyek['id'] ?>">

                    <div>
                        <label class="form-label">Status Proyek</label>
                        <select name="status" class="form-select">
                            <?php foreach (['survei'=>'Survei','perencanaan'=>'Perencanaan','instalasi'=>'Instalasi','selesai'=>'Selesai','ditunda'=>'Ditunda'] as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $proyek['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label flex justify-between">
                            <span>Progres Keseluruhan</span>
                            <strong id="lblProgQuick" class="text-primary-700"><?= (int)$proyek['progres'] ?>%</strong>
                        </label>
                        <input type="range" name="progres" min="0" max="100" value="<?= (int)$proyek['progres'] ?>"
                               class="w-full" oninput="document.getElementById('lblProgQuick').textContent = this.value + '%'">
                    </div>

                    <button type="submit" class="btn-primary w-full justify-center">
                        <i class="bi bi-check2-circle"></i> Simpan Pembaruan
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Informasi Klien / Pelanggan -->
            <div class="card p-5">
                <h3 class="text-sm font-bold text-slate-900 mb-3 pb-2.5 border-b border-slate-100 flex items-center gap-2">
                    <i class="bi bi-building text-indigo-600"></i> Informasi Pelanggan & Lokasi
                </h3>
                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400 block">Nama Perusahaan / Pelanggan</span>
                        <strong class="text-slate-800 text-sm"><?= sanitize($proyek['nama_pelanggan']) ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Lokasi Proyek</span>
                        <span class="text-slate-700 font-medium"><?= sanitize($proyek['lokasi']) ?></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Kontak Telepon</span>
                        <span class="text-slate-700 font-medium"><?= sanitize($proyek['no_telp'] ?: '-') ?></span>
                    </div>
                    <?php if (!empty($proyek['deskripsi'])): ?>
                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-slate-400 block mb-1">Deskripsi Ruang Lingkup</span>
                        <p class="text-slate-600 leading-relaxed whitespace-pre-line"><?= sanitize($proyek['deskripsi']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tahapan Jadwal Proyek -->
            <div class="card p-5">
                <div class="flex items-center justify-between mb-3 pb-2.5 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i class="bi bi-calendar3 text-emerald-600"></i> Jadwal Instalasi
                    </h3>
                    <?php if (isAdminOrManajer()): ?>
                    <a href="<?= BASE_URL ?>/pages/jadwal/create.php?proyek_id=<?= $proyek['id'] ?>" class="text-xs font-bold text-primary-600 hover:underline">
                        + Tambah
                    </a>
                    <?php endif; ?>
                </div>

                <?php if (empty($jadwals)): ?>
                <p class="text-xs text-slate-400 py-4 text-center">Belum ada jadwal tahapan.</p>
                <?php else: ?>
                <div class="space-y-2.5">
                    <?php foreach ($jadwals as $j): ?>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-xs font-bold text-slate-800 truncate"><?= sanitize($j['keterangan'] ?: 'Tahap Instalasi') ?></span>
                            <?= statusBadge($j['status']) ?>
                        </div>
                        <div class="text-[11px] text-slate-400">
                            <?= formatTanggalShort($j['tanggal_mulai']) ?> — <?= formatTanggalShort($j['tanggal_selesai']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- ==========================================================
     MODAL TAMBAH PERSONEL TIM KE PROYEK
=========================================================== -->
<?php if (isAdminOrManajer()): ?>
<div id="modalTambahTim" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden items-center justify-center p-4"
     onclick="if(event.target===this) closeModalTambahTim()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100" onclick="event.stopPropagation()">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="bi bi-person-plus-fill text-lg"></i>
                </span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Tempatkan Personel ke Proyek</h3>
                    <p class="text-xs text-slate-400"><?= sanitize($proyek['nama_proyek']) ?></p>
                </div>
            </div>
            <button type="button" onclick="closeModalTambahTim()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/api/proyek/tim.php" class="p-6 space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="aksi" value="tambah">
            <input type="hidden" name="proyek_id" value="<?= $proyek['id'] ?>">

            <!-- Pilihan Sumber Personel -->
            <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-xl">
                <button type="button" id="tabTimAkun" onclick="switchTipePersonel('akun')"
                        class="py-1.5 rounded-lg text-xs font-bold bg-white text-slate-900 shadow-2xs transition-all">
                    Dari Akun Sistem
                </button>
                <button type="button" id="tabTimManual" onclick="switchTipePersonel('manual')"
                        class="py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition-all">
                    Input Nama Manual
                </button>
            </div>
            <input type="hidden" name="tipe_personel" id="tipePersonelInput" value="akun">

            <!-- Input A: Pilih dari Akun Users -->
            <div id="boxInputAkun">
                <label class="form-label">Pilih Staf / Teknisi <span class="text-red-500">*</span></label>
                <select name="user_id" class="form-select">
                    <option value="">-- Pilih Nama Pengguna --</option>
                    <?php foreach ($allUsers as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= sanitize($u['nama']) ?> (<?= ucfirst(str_replace('_', ' ', $u['role'])) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Input B: Ketik Nama Manual -->
            <div id="boxInputManual" class="hidden">
                <label class="form-label">Nama Lengkap Personel <span class="text-red-500">*</span></label>
                <input type="text" name="nama_personel" class="form-input" placeholder="Contoh: Budi Santoso">
            </div>

            <div>
                <label class="form-label">Jabatan / Peran di Proyek <span class="text-red-500">*</span></label>
                <select name="peran_proyek" class="form-select" required>
                    <?php foreach ($daftarJabatan as $jab): ?>
                    <option value="<?= $jab ?>"><?= $jab ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="form-label">No. WhatsApp / Telepon <span class="text-slate-400 font-normal">(Opsional)</span></label>
                <input type="text" name="no_hp" class="form-input" placeholder="Contoh: 081234567890">
            </div>

            <div class="flex gap-2.5 pt-2">
                <button type="button" onclick="closeModalTambahTim()" class="btn-secondary flex-1 justify-center">Batal</button>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="bi bi-check-lg"></i> Tempatkan ke Proyek
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModalTambahTim() {
    const m = document.getElementById('modalTambahTim');
    m.classList.remove('hidden');
    m.classList.add('flex');
}
function closeModalTambahTim() {
    const m = document.getElementById('modalTambahTim');
    m.classList.add('hidden');
    m.classList.remove('flex');
}
function switchTipePersonel(tipe) {
    document.getElementById('tipePersonelInput').value = tipe;
    const isAkun = tipe === 'akun';
    document.getElementById('boxInputAkun').classList.toggle('hidden', !isAkun);
    document.getElementById('boxInputManual').classList.toggle('hidden', isAkun);

    document.getElementById('tabTimAkun').className = isAkun
        ? 'py-1.5 rounded-lg text-xs font-bold bg-white text-slate-900 shadow-2xs transition-all'
        : 'py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition-all';
    document.getElementById('tabTimManual').className = !isAkun
        ? 'py-1.5 rounded-lg text-xs font-bold bg-white text-slate-900 shadow-2xs transition-all'
        : 'py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition-all';
}
</script>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>