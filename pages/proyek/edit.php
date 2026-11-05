<?php
// pages/proyek/edit.php — EDIT PROYEK & PENEMPATAN TIM PERSONEL
$pageTitle   = 'Edit Proyek';
$currentPage = 'proyek';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$db = db();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    setFlash('error', 'ID proyek tidak valid.');
    redirect(BASE_URL . '/pages/proyek/index.php');
}

$stmt = $db->prepare("SELECT * FROM proyek WHERE id = ?");
$stmt->execute([$id]);
$proyek = $stmt->fetch();

if (!$proyek) {
    setFlash('error', 'Data proyek tidak ditemukan.');
    redirect(BASE_URL . '/pages/proyek/index.php');
}

$pelanggans = $db->query("SELECT * FROM pelanggan ORDER BY nama_pelanggan ASC")->fetchAll();
$usersList  = $db->query("
    SELECT id, nama, email, role 
    FROM users 
    WHERE status = 'aktif' 
    ORDER BY FIELD(role, 'manajer_proyek', 'teknisi_lapangan', 'admin', 'gudang'), nama ASC
")->fetchAll();

// Ambil data tim yang sudah ditempatkan di proyek ini
$assignedUsers  = []; // [user_id => peran_proyek]
$manualPersonel = []; // daftar personel non-akun

try {
    $stmtTim = $db->prepare("SELECT * FROM proyek_tim WHERE proyek_id = ? ORDER BY id ASC");
    $stmtTim->execute([$id]);
    foreach ($stmtTim->fetchAll() as $row) {
        if (!empty($row['user_id'])) {
            $assignedUsers[(int)$row['user_id']] = $row['peran_proyek'];
        } else {
            $manualPersonel[] = $row;
        }
    }
} catch (Exception $e) {
    // Abaikan jika tabel proyek_tim belum dibuat
}

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

<div class="max-w-4xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <a href="<?= BASE_URL ?>/pages/proyek/index.php">Proyek</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $proyek['id'] ?>"><?= sanitize($proyek['nama_proyek']) ?></a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Edit Proyek</span>
    </nav>

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-3.5">
            <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $proyek['id'] ?>"
               class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-colors shadow-2xs">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Edit Proyek PLTS</h2>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui detail kontrak, progres, lokasi, dan susunan personel tim proyek</p>
            </div>
        </div>
        <?= statusBadge($proyek['status']) ?>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/api/proyek/update.php" class="space-y-6">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= $proyek['id'] ?>">

        <!-- BAGIAN 1: INFORMASI KONTRAK, STATUS & PROGRES -->
        <div class="card p-6">
            <div class="flex items-center gap-2.5 mb-5 pb-3 border-b border-slate-100">
                <span class="w-7 h-7 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center text-xs font-bold">1</span>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Informasi Kontrak, Status & Lokasi</h3>
                    <p class="text-xs text-slate-400">Data utama proyek dan capaian progres pelaksanaan</p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Pelanggan / Klien <span class="text-red-500">*</span></label>
                        <select name="pelanggan_id" class="form-select" required>
                            <option value="">-- Pilih Pelanggan --</option>
                            <?php foreach ($pelanggans as $pl): ?>
                            <option value="<?= $pl['id'] ?>" <?= (int)$proyek['pelanggan_id'] === (int)$pl['id'] ? 'selected' : '' ?>>
                                <?= sanitize($pl['nama_pelanggan']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Nama Proyek PLTS <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_proyek" class="form-input"
                               value="<?= sanitize($proyek['nama_proyek']) ?>" required>
                    </div>
                </div>

                <div>
                    <label class="form-label">Lokasi Instalasi / Area Proyek <span class="text-red-500">*</span></label>
                    <input type="text" name="lokasi" class="form-input"
                           value="<?= sanitize($proyek['lokasi']) ?>" required>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Tanggal Mulai <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_mulai" class="form-input"
                               value="<?= sanitize($proyek['tanggal_mulai']) ?>" required>
                    </div>
                    <div>
                        <label class="form-label">Estimasi Tanggal Selesai <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_selesai" class="form-input"
                               value="<?= sanitize($proyek['tanggal_selesai']) ?>" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="form-label">Status Proyek <span class="text-red-500">*</span></label>
                        <select name="status" class="form-select" required>
                            <?php
                            $statusOpts = [
                                'survei'      => 'Survei Lokasi',
                                'perencanaan' => 'Perencanaan & Desain',
                                'instalasi'   => 'Instalasi / Pengerjaan',
                                'selesai'     => 'Selesai (100%)',
                                'ditunda'     => 'Ditunda (On Hold)',
                            ];
                            foreach ($statusOpts as $val => $lbl):
                            ?>
                            <option value="<?= $val ?>" <?= $proyek['status'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label flex justify-between">
                            <span>Progres Proyek (%)</span>
                            <strong id="labelEditProgres" class="text-primary-700"><?= (int)$proyek['progres'] ?>%</strong>
                        </label>
                        <input type="range" name="progres" min="0" max="100" value="<?= (int)$proyek['progres'] ?>"
                               class="w-full mt-2" oninput="document.getElementById('labelEditProgres').textContent = this.value + '%'">
                    </div>
                </div>

<!-- INTERACTIVE MAP PICKER (LEAFLET.JS + OPENSTREETMAP) -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <label class="form-label mb-0 flex items-center gap-1.5">
                                <i class="bi bi-geo-alt-fill text-red-500"></i> Titik Koordinat Peta Lokasi Proyek <span class="text-slate-400 font-normal">(Opsional)</span>
                            </label>
                            <p class="text-[11px] text-slate-400">Klik langsung pada peta atau geser pin biru untuk menentukan titik lokasi proyek PLTS</p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" onclick="ambilLokasiGPS()" class="btn-secondary btn-sm text-xs">
                                <i class="bi bi-crosshair text-primary-600"></i> Lokasi Saya
                            </button>
                            <button type="button" onclick="resetKoordinatPeta()" class="btn-secondary btn-sm text-xs text-red-600">
                                <i class="bi bi-x-lg"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- Kanvas Peta Interaktif -->
                    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                    <div id="mapPickerCanvas" class="w-full h-64 rounded-xl border border-slate-200 shadow-2xs z-10"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="text-[11px] font-bold text-slate-500 uppercase">Latitude</label>
                            <input type="text" name="latitude" id="inputLat" class="form-input py-1.5 text-xs mt-1"
                                   value="<?= isset($proyek['latitude']) ? sanitize($proyek['latitude']) : '' ?>"
                                   placeholder="Contoh: -6.356100" oninput="sinkronInputKePeta()">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-slate-500 uppercase">Longitude</label>
                            <input type="text" name="longitude" id="inputLng" class="form-input py-1.5 text-xs mt-1"
                                   value="<?= isset($proyek['longitude']) ? sanitize($proyek['longitude']) : '' ?>"
                                   placeholder="Contoh: 107.283100" oninput="sinkronInputKePeta()">
                        </div>
                    </div>
                </div>

                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                <script>
                let mapPicker, mapMarker;
                document.addEventListener('DOMContentLoaded', function() {
                    const latEl = document.getElementById('inputLat');
                    const lngEl = document.getElementById('inputLng');

                    // Default ke area Jabodetabek/Karawang (-6.30, 107.15) jika belum ada koordinat
                    const initLat = parseFloat(latEl.value) || -6.300000;
                    const initLng = parseFloat(lngEl.value) || 107.150000;
                    const hasInit = (latEl.value.trim() !== '' && lngEl.value.trim() !== '');

                    mapPicker = L.map('mapPickerCanvas').setView([initLat, initLng], hasInit ? 15 : 10);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap'
                    }).addTo(mapPicker);

                    if (hasInit) {
                        pasangPinPeta(initLat, initLng);
                    }

                    mapPicker.on('click', function(e) {
                        const lat = e.latlng.lat.toFixed(6);
                        const lng = e.latlng.lng.toFixed(6);
                        latEl.value = lat;
                        lngEl.value = lng;
                        pasangPinPeta(lat, lng);
                    });
                });

                function pasangPinPeta(lat, lng) {
                    if (mapMarker) {
                        mapMarker.setLatLng([lat, lng]);
                    } else {
                        mapMarker = L.marker([lat, lng], { draggable: true }).addTo(mapPicker);
                        mapMarker.on('dragend', function(e) {
                            const pos = e.target.getLatLng();
                            document.getElementById('inputLat').value = pos.lat.toFixed(6);
                            document.getElementById('inputLng').value = pos.lng.toFixed(6);
                        });
                    }
                }

                function sinkronInputKePeta() {
                    const lat = parseFloat(document.getElementById('inputLat').value);
                    const lng = parseFloat(document.getElementById('inputLng').value);
                    if (!isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                        pasangPinPeta(lat, lng);
                        mapPicker.panTo([lat, lng]);
                    }
                }

                function ambilLokasiGPS() {
                    if (!navigator.geolocation) {
                        alert('Browser Anda tidak mendukung fitur GPS.');
                        return;
                    }
                    navigator.geolocation.getCurrentPosition(function(pos) {
                        const lat = pos.coords.latitude.toFixed(6);
                        const lng = pos.coords.longitude.toFixed(6);
                        document.getElementById('inputLat').value = lat;
                        document.getElementById('inputLng').value = lng;
                        pasangPinPeta(lat, lng);
                        mapPicker.setView([lat, lng], 16);
                    }, function() {
                        alert('Gagal mengambil lokasi GPS. Pastikan izin lokasi browser diaktifkan.');
                    });
                }

                function resetKoordinatPeta() {
                    document.getElementById('inputLat').value = '';
                    document.getElementById('inputLng').value = '';
                    if (mapMarker) {
                        mapPicker.removeLayer(mapMarker);
                        mapMarker = null;
                    }
                }
                </script>

                <div>
                    <label class="form-label">Deskripsi & Ruang Lingkup Pekerjaan</label>
                    <textarea name="deskripsi" rows="3" class="form-textarea"><?= sanitize($proyek['deskripsi'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- BAGIAN 2: EDIT PENEMPATAN TIM & PERSONEL -->
        <div class="card p-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-5 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs font-bold">2</span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Penempatan Tim & Personel Proyek</h3>
                        <p class="text-xs text-slate-400">Komposisi tim ini akan menjadi acuan otomatis jumlah Manpower pada Daily Report</p>
                    </div>
                </div>
                <span class="badge-modern bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200">
                    <i class="bi bi-people-fill"></i> <span id="counterTimTerpilih">0</span> Personel Ditempatkan
                </span>
            </div>

            <!-- 2A. Pilih dari Akun Staf/Teknisi -->
            <div class="mb-5">
                <label class="form-label mb-2">A. Akun Staf / Teknisi Sistem</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <?php foreach ($usersList as $u):
                        $uid = (int)$u['id'];
                        $isChecked = array_key_exists($uid, $assignedUsers);
                        $defaultRoleMap = [
                            'manajer_proyek'   => 'Project Manager',
                            'teknisi_lapangan' => 'Teknisi Lapangan',
                            'admin'            => 'Admin Proyek',
                            'gudang'           => 'Helper',
                        ];
                        $selectedJabatan = $assignedUsers[$uid] ?? ($defaultRoleMap[$u['role']] ?? 'Teknisi Lapangan');
                        $inisial = strtoupper(substr($u['nama'], 0, 1));
                    ?>
                    <div class="team-user-card p-3 rounded-xl border transition-all <?= $isChecked ? 'border-primary-500 bg-primary-50/40 ring-2 ring-primary-500/15' : 'border-slate-200 bg-slate-50/50' ?>"
                         id="userCard_<?= $uid ?>">
                        <div class="flex items-center justify-between gap-3">
                            <label class="flex items-center gap-3 cursor-pointer flex-1 min-w-0">
                                <input type="checkbox" name="tim_user_ids[]" value="<?= $uid ?>"
                                       class="w-4 h-4 rounded text-primary-600 focus:ring-primary-500 team-checkbox"
                                       <?= $isChecked ? 'checked' : '' ?>
                                       onchange="toggleUserCard(<?= $uid ?>, this.checked)">
                                <span class="w-8 h-8 rounded-lg bg-primary-100 text-primary-700 font-bold text-xs flex items-center justify-center flex-shrink-0">
                                    <?= $inisial ?>
                                </span>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-800 truncate"><?= sanitize($u['nama']) ?></div>
                                    <div class="text-[11px] text-slate-400 truncate"><?= ucfirst(str_replace('_', ' ', $u['role'])) ?></div>
                                </div>
                            </label>

                            <select name="tim_user_peran[<?= $uid ?>]" id="userRole_<?= $uid ?>"
                                    class="py-1.5 px-2.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 transition-opacity <?= $isChecked ? '' : 'opacity-50 pointer-events-none' ?>">
                                <?php foreach ($daftarJabatan as $jab): ?>
                                <option value="<?= $jab ?>" <?= $jab === $selectedJabatan ? 'selected' : '' ?>><?= $jab ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 2B. Personel Lapangan Tambahan (Non-Akun) -->
            <div class="pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <label class="form-label mb-0">B. Personel Lapangan Tambahan <span class="text-slate-400 font-normal">(Non-Akun — Helper, HSE, Scaffolder, dll.)</span></label>
                    <button type="button" onclick="tambahPersonelManual()" class="btn-secondary btn-sm text-xs">
                        <i class="bi bi-person-plus"></i> Tambah Personel
                    </button>
                </div>

                <div id="containerPersonelManual" class="space-y-2.5 mt-3">
                    <?php foreach ($manualPersonel as $mp): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center p-3 rounded-xl bg-slate-50 border border-slate-200 manual-personel-row">
                        <div class="sm:col-span-5">
                            <input type="text" name="manual_nama[]" value="<?= sanitize($mp['nama_personel']) ?>"
                                   class="form-input py-2 text-xs" placeholder="Nama lengkap personel..." oninput="hitungTotalTim()">
                        </div>
                        <div class="sm:col-span-4">
                            <select name="manual_peran[]" class="form-select py-2 text-xs">
                                <?php foreach ($daftarJabatan as $jab): ?>
                                <option value="<?= $jab ?>" <?= $mp['peran_proyek'] === $jab ? 'selected' : '' ?>><?= $jab ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <input type="text" name="manual_hp[]" value="<?= sanitize($mp['no_hp'] ?? '') ?>"
                                   class="form-input py-2 text-xs" placeholder="No. HP / WA">
                        </div>
                        <div class="sm:col-span-1 text-right">
                            <button type="button" onclick="this.closest('.manual-personel-row').remove(); hitungTotalTim();"
                                    class="p-2 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition-colors" title="Hapus baris">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi -->
        <div class="flex items-center justify-between gap-3">
            <button type="button"
                    onclick="confirmDelete('formDeleteProyek', 'Yakin ingin menghapus proyek <?= addslashes(sanitize($proyek['nama_proyek'])) ?> beserta seluruh jadwal dan laporannya?')"
                    class="btn-secondary text-red-600 hover:bg-red-50">
                <i class="bi bi-trash"></i> Hapus Proyek
            </button>

            <div class="flex items-center gap-2.5">
                <a href="<?= BASE_URL ?>/pages/proyek/show.php?id=<?= $proyek['id'] ?>" class="btn-secondary px-5">Batal</a>
                <button type="submit" class="btn-primary px-6 shadow-lg shadow-primary-600/25">
                    <i class="bi bi-check2-circle"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>

    <form method="POST" action="<?= BASE_URL ?>/api/proyek/delete.php" id="formDeleteProyek" class="hidden">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= $proyek['id'] ?>">
    </form>
</div>

<script>
const daftarJabatanJS = <?= json_encode($daftarJabatan) ?>;

function toggleUserCard(userId, isChecked) {
    const card = document.getElementById('userCard_' + userId);
    const sel  = document.getElementById('userRole_' + userId);
    if (isChecked) {
        card.classList.remove('border-slate-200', 'bg-slate-50/50');
        card.classList.add('border-primary-500', 'bg-primary-50/40', 'ring-2', 'ring-primary-500/15');
        sel.classList.remove('opacity-50', 'pointer-events-none');
    } else {
        card.classList.add('border-slate-200', 'bg-slate-50/50');
        card.classList.remove('border-primary-500', 'bg-primary-50/40', 'ring-2', 'ring-primary-500/15');
        sel.classList.add('opacity-50', 'pointer-events-none');
    }
    hitungTotalTim();
}

function tambahPersonelManual(nama = '', peran = 'Helper', hp = '') {
    const container = document.getElementById('containerPersonelManual');
    const row = document.createElement('div');
    row.className = 'grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center p-3 rounded-xl bg-slate-50 border border-slate-200 manual-personel-row';

    const optionsHtml = daftarJabatanJS.map(j => `<option value="${j}" ${j === peran ? 'selected' : ''}>${j}</option>`).join('');

    row.innerHTML = `
        <div class="sm:col-span-5">
            <input type="text" name="manual_nama[]" value="${nama}" class="form-input py-2 text-xs" placeholder="Nama lengkap personel..." oninput="hitungTotalTim()">
        </div>
        <div class="sm:col-span-4">
            <select name="manual_peran[]" class="form-select py-2 text-xs">
                ${optionsHtml}
            </select>
        </div>
        <div class="sm:col-span-2">
            <input type="text" name="manual_hp[]" value="${hp}" class="form-input py-2 text-xs" placeholder="No. HP / WA">
        </div>
        <div class="sm:col-span-1 text-right">
            <button type="button" onclick="this.closest('.manual-personel-row').remove(); hitungTotalTim();"
                class="p-2 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition-colors" title="Hapus baris">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
    hitungTotalTim();
}

function hitungTotalTim() {
    const checkedUsers = document.querySelectorAll('.team-checkbox:checked').length;
    let manualCount = 0;
    document.querySelectorAll('input[name="manual_nama[]"]').forEach(inp => {
        if (inp.value.trim() !== '') manualCount++;
    });
    document.getElementById('counterTimTerpilih').textContent = checkedUsers + manualCount;
}

document.addEventListener('DOMContentLoaded', hitungTotalTim);
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>