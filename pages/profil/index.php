<?php
// pages/profil/index.php — PENGATURAN PROFIL, FOTO AVATAR & KEAMANAN AKUN
$pageTitle   = 'Profil Saya';
$currentPage = 'profil';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/activity.php';
requireLogin();

$db   = db();
$user = currentUser();
$uid  = (int)$user['id'];

// Pastikan kolom avatar sudah ada di tabel users (Auto-migrate aman jika belum ada)
try {
    $db->query("SELECT avatar FROM users LIMIT 1");
} catch (Exception $e) {
    try {
        $db->exec("ALTER TABLE `users` ADD COLUMN `avatar` VARCHAR(255) DEFAULT NULL AFTER `role`");
    } catch (Exception $ex) {}
}

// Ambil data terbaru user dari database
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$profil = $stmt->fetch();

// Daftar Preset Avatar Profesional (DiceBear Adventurer / Bottts / Notionists)
$presetAvatars = [
    'https://api.dicebear.com/9.x/adventurer/svg?seed=Felix&backgroundColor=b6e3f4',
    'https://api.dicebear.com/9.x/adventurer/svg?seed=Aneka&backgroundColor=c0aede',
    'https://api.dicebear.com/9.x/adventurer/svg?seed=Alexander&backgroundColor=d1d4f9',
    'https://api.dicebear.com/9.x/adventurer/svg?seed=Nala&backgroundColor=ffd5dc',
    'https://api.dicebear.com/9.x/bottts/svg?seed=SolarEngineer1&backgroundColor=b6e3f4',
    'https://api.dicebear.com/9.x/bottts/svg?seed=SolarBot2&backgroundColor=c0aede',
    'https://api.dicebear.com/9.x/micah/svg?seed=Ritzsal&backgroundColor=d1d4f9',
    'https://api.dicebear.com/9.x/micah/svg?seed=EnergyPro&backgroundColor=ffdfbf',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('error', 'Token keamanan tidak valid.');
        redirect(BASE_URL . '/pages/profil/index.php');
    }

    $nama        = trim($_POST['nama'] ?? '');
    $email       = strtolower(trim($_POST['email'] ?? ''));
    $pwLama      = $_POST['password_lama'] ?? '';
    $pwBaru      = $_POST['password_baru'] ?? '';
    $pwKonfirm   = $_POST['password_konfirmasi'] ?? '';
    $avatarMode  = $_POST['avatar_mode'] ?? 'keep'; // keep | upload | preset | remove
    $presetUrl   = trim($_POST['preset_avatar_url'] ?? '');

    if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Nama lengkap dan format email wajib valid.');
        redirect(BASE_URL . '/pages/profil/index.php');
    }

    // Cek apakah email sudah dipakai akun lain
    $cekEmail = $db->prepare("SELECT COUNT(*) FROM users WHERE LOWER(email) = ? AND id != ?");
    $cekEmail->execute([$email, $uid]);
    if ($cekEmail->fetchColumn() > 0) {
        setFlash('error', "Alamat email $email sudah digunakan oleh pengguna lain.");
        redirect(BASE_URL . '/pages/profil/index.php');
    }

    $avatarPathFinal = $profil['avatar'] ?? null;
    $avatarDir       = __DIR__ . '/../../uploads/avatars/';
    if (!is_dir($avatarDir)) {
        mkdir($avatarDir, 0777, true);
    }

    // Helper hapus file avatar lokal lama jika ada
    $hapusFileAvatarLama = function (?string $oldAvatar) use ($avatarDir) {
        if (!empty($oldAvatar) && str_starts_with($oldAvatar, 'avatars/')) {
            $fullOldPath = __DIR__ . '/../../uploads/' . $oldAvatar;
            if (is_file($fullOldPath)) {
                @unlink($fullOldPath);
            }
        }
    };

    // 1. Proses jika memilih Upload Foto Sendiri
    if (!empty($_FILES['foto_profil']['name']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['foto_profil']['tmp_name'];
        $finfo   = finfo_open(FILEINFO_MIME_TYPE);
        $mime    = finfo_file($finfo, $tmpName);
        finfo_close($finfo);

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];

        if (!isset($allowedMimes[$mime])) {
            setFlash('error', 'Format file foto harus berupa gambar (JPG, PNG, atau WebP).');
            redirect(BASE_URL . '/pages/profil/index.php');
        }

        if ($_FILES['foto_profil']['size'] > 4 * 1024 * 1024) {
            setFlash('error', 'Ukuran foto profil maksimal 4 MB.');
            redirect(BASE_URL . '/pages/profil/index.php');
        }

        $ext      = $allowedMimes[$mime];
        $newFile  = 'avatar_' . $uid . '_' . time() . '.' . $ext;
        $destPath = $avatarDir . $newFile;

        if (move_uploaded_file($tmpName, $destPath)) {
            $hapusFileAvatarLama($avatarPathFinal);
            $avatarPathFinal = 'avatars/' . $newFile;
        }
    }
    // 2. Proses jika memilih salah satu Avatar Preset
    elseif ($avatarMode === 'preset' && in_array($presetUrl, $presetAvatars, true)) {
        $hapusFileAvatarLama($avatarPathFinal);
        $avatarPathFinal = $presetUrl;
    }
    // 3. Proses jika memilih Hapus Foto (Kembali ke Inisial)
    elseif ($avatarMode === 'remove') {
        $hapusFileAvatarLama($avatarPathFinal);
        $avatarPathFinal = null;
    }

    // 4. Validasi jika pengguna ingin mengganti Password
    if ($pwBaru !== '') {
        if (strlen($pwBaru) < 6) {
            setFlash('error', 'Password baru minimal harus 6 karakter.');
            redirect(BASE_URL . '/pages/profil/index.php');
        }
        if ($pwBaru !== $pwKonfirm) {
            setFlash('error', 'Konfirmasi password baru tidak cocok.');
            redirect(BASE_URL . '/pages/profil/index.php');
        }
        // Jika akun punya password dan bukan login Google murni, cek password lama
        if ($pwLama !== '' && !password_verify($pwLama, $profil['password'])) {
            setFlash('error', 'Password saat ini yang Anda masukkan salah.');
            redirect(BASE_URL . '/pages/profil/index.php');
        }

        $stmtUpd = $db->prepare("UPDATE users SET nama = ?, email = ?, password = ?, avatar = ? WHERE id = ?");
        $stmtUpd->execute([$nama, $email, password_hash($pwBaru, PASSWORD_BCRYPT), $avatarPathFinal, $uid]);
    } else {
        $stmtUpd = $db->prepare("UPDATE users SET nama = ?, email = ?, avatar = ? WHERE id = ?");
        $stmtUpd->execute([$nama, $email, $avatarPathFinal, $uid]);
    }

    // Perbarui session secara langsung agar foto di Sidebar & Topbar langsung berubah!
    $_SESSION['user']['nama']   = $nama;
    $_SESSION['user']['email']  = $email;
    $_SESSION['user']['avatar'] = $avatarPathFinal;

    logActivity('update_profil', 'update', 'Memperbarui profil & foto avatar akun', 'profil');
    setFlash('success', 'Profil dan foto avatar Anda berhasil diperbarui!');
    redirect(BASE_URL . '/pages/profil/index.php');
}

// Statistik kontribusi pengguna ini
$totalAktivitas = (int)$db->query("SELECT COUNT(*) FROM activity_log WHERE user_id = $uid")->fetchColumn();
$totalLaporan   = (int)$db->query("SELECT COUNT(*) FROM laporan_lapangan WHERE user_id = $uid")->fetchColumn();

$currentAvatarUrl = function_exists('resolveAvatarUrl')
    ? resolveAvatarUrl($profil['avatar'] ?? null)
    : (!empty($profil['avatar']) ? (str_starts_with($profil['avatar'], 'http') ? $profil['avatar'] : BASE_URL . '/uploads/' . $profil['avatar']) : null);

$roleBadges = [
    'operator'         => ['label' => 'Operator Sistem',  'cls' => 'from-amber-500 to-orange-600'],
    'admin'            => ['label' => 'Administrator',    'cls' => 'from-rose-500 to-red-600'],
    'manajer_proyek'   => ['label' => 'Manajer Proyek',   'cls' => 'from-sky-500 to-blue-600'],
    'teknisi_lapangan' => ['label' => 'Teknisi Lapangan', 'cls' => 'from-emerald-500 to-green-600'],
    'gudang'           => ['label' => 'Staf Gudang',      'cls' => 'from-violet-500 to-purple-600'],
];
$myRoleMeta = $roleBadges[$profil['role']] ?? ['label' => $profil['role'], 'cls' => 'from-primary-600 to-indigo-700'];
$inisial    = strtoupper(substr($profil['nama'] ?? 'U', 0, 1));

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-5xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Profil & Avatar Saya</span>
    </nav>

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3.5">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-br <?= $myRoleMeta['cls'] ?> text-white flex items-center justify-center shadow-lg">
                <i class="bi bi-person-bounding-box text-lg"></i>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Pengaturan Profil & Avatar</h2>
                <p class="text-xs text-slate-500 mt-0.5">Ubah foto profil sendiri, pilih karakter avatar, dan kelola keamanan kata sandi Anda</p>
            </div>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" id="formProfil">
        <?= csrfField() ?>
        <input type="hidden" name="avatar_mode" id="avatarModeInput" value="keep">
        <input type="hidden" name="preset_avatar_url" id="presetAvatarInput" value="">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- ==========================================================
                 KOLOM KIRI (4 KOLOM): KARTU PREVIEW PROFIL & AVATAR
            =========================================================== -->
            <div class="lg:col-span-4 space-y-5">
                <div class="card overflow-hidden">
                    <!-- Banner Gradient Atas -->
                    <div class="h-24 bg-gradient-to-r <?= $myRoleMeta['cls'] ?> relative">
                        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 50%, white 1px, transparent 1px); background-size: 16px 16px;"></div>
                    </div>

                    <div class="px-6 pb-6 text-center relative">
                        <!-- Lingkaran Avatar Besar dengan Tombol Kamera -->
                        <div class="relative w-28 h-28 mx-auto -mt-14 mb-3 group">
                            <!-- Tampilan Gambar Avatar (Hanya tampil jika pengguna sudah memilih foto/avatar) -->
                            <img id="avatarLivePreviewImg"
                                 src="<?= $currentAvatarUrl ? sanitize($currentAvatarUrl) : '' ?>"
                                 alt="Foto Profil"
                                 style="<?= $currentAvatarUrl ? '' : 'display:none;' ?>"
                                 class="w-28 h-28 rounded-3xl object-cover ring-4 ring-white shadow-xl bg-white">

                            <!-- Default: Tampilan Inisial Huruf Awal (Muncul otomatis jika belum memilih avatar) -->
                            <div id="avatarLivePreviewInitial"
                                 style="<?= $currentAvatarUrl ? 'display:none;' : '' ?>"
                                 class="w-28 h-28 rounded-3xl bg-gradient-to-br <?= $myRoleMeta['cls'] ?> text-white font-extrabold text-4xl flex items-center justify-center ring-4 ring-white shadow-xl">
                                <?= $inisial ?>
                            </div>

                            <!-- Overlay Klik Ganti Foto -->
                            <button type="button" onclick="document.getElementById('inputFotoProfil').click()"
                                    class="absolute bottom-0 right-0 w-9 h-9 rounded-xl bg-slate-900 hover:bg-primary-600 text-white flex items-center justify-center shadow-lg ring-2 ring-white transition-all"
                                    title="Unggah Foto Sendiri">
                                <i class="bi bi-camera-fill text-sm"></i>
                            </button>
                        </div>

                        <h3 class="text-base font-extrabold text-slate-900"><?= sanitize($profil['nama']) ?></h3>
                        <p class="text-xs text-slate-400 mt-0.5"><?= sanitize($profil['email']) ?></p>

                        <div class="mt-2.5 flex items-center justify-center gap-2">
                            <span class="badge-modern bg-slate-100 text-slate-700">
                                <?= $myRoleMeta['label'] ?>
                            </span>
                            <span class="badge-modern bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Online
                            </span>
                        </div>

                        <!-- Tombol Cepat Upload & Hapus Foto -->
                        <div class="grid grid-cols-2 gap-2 mt-5 pt-4 border-t border-slate-100">
                            <button type="button" onclick="document.getElementById('inputFotoProfil').click()"
                                    class="btn-secondary btn-sm justify-center text-xs">
                                <i class="bi bi-upload text-primary-600"></i> Upload Foto
                            </button>
                            <button type="button" onclick="resetKeInisial()"
                                    class="btn-secondary btn-sm justify-center text-xs text-red-600 hover:bg-red-50">
                                <i class="bi bi-trash3"></i> Pakai Inisial
                            </button>
                        </div>
                        <input type="file" name="foto_profil" id="inputFotoProfil" class="hidden" accept="image/jpeg,image/png,image/webp" onchange="handleAvatarFileSelect(this)">
                        <p id="statusCropText" class="text-[11px] font-semibold text-emerald-600 mt-2 hidden"></p>
                    </div>
                </div>

                <!-- Statistik Singkat Pengguna -->
                <div class="card p-5">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Ringkasan Aktivitas Akun</h4>
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-xl font-extrabold text-slate-900"><?= number_format($totalLaporan) ?></div>
                            <div class="text-[11px] text-slate-400 mt-0.5">Laporan Dibuat</div>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-xl font-extrabold text-slate-900"><?= number_format($totalAktivitas) ?></div>
                            <div class="text-[11px] text-slate-400 mt-0.5">Total Aktivitas</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==========================================================
                 KOLOM KANAN (8 KOLOM): PILIHAN AVATAR PRESET & FORM DATA DIRI
            =========================================================== -->
            <div class="lg:col-span-8 space-y-6">

                <!-- 1. Galeri Pilihan Avatar Preset -->
                <div class="card p-6">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <i class="bi bi-stars"></i>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Pilih Karakter Avatar Instan</h3>
                                <p class="text-xs text-slate-400">Klik salah satu avatar di bawah ini jika Anda tidak ingin mengunggah foto pribadi</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 sm:grid-cols-8 gap-3">
                        <?php foreach ($presetAvatars as $idx => $pUrl):
                            $isCurrentPreset = (($profil['avatar'] ?? '') === $pUrl);
                        ?>
                        <button type="button" onclick="pilihAvatarPreset('<?= $pUrl ?>', this)"
                                class="preset-avatar-btn relative p-1.5 rounded-2xl border-2 transition-all hover:scale-105 <?= $isCurrentPreset ? 'border-primary-600 bg-primary-50 ring-2 ring-primary-500/20' : 'border-slate-200 hover:border-primary-300 bg-slate-50' ?>"
                                title="Pilih Avatar #<?= $idx + 1 ?>">
                            <img src="<?= sanitize($pUrl) ?>" alt="Preset <?= $idx + 1 ?>" class="w-full aspect-square rounded-xl object-cover">
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. Informasi Identitas Diri -->
                <div class="card p-6">
                    <div class="flex items-center gap-2.5 mb-4 pb-3 border-b border-slate-100">
                        <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i class="bi bi-person-lines-fill"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Informasi Identitas Akun</h3>
                            <p class="text-xs text-slate-400">Perbarui nama lengkap dan alamat email aktif Anda</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="nama" class="form-input" value="<?= sanitize($profil['nama']) ?>" required>
                        </div>

                        <div>
                            <label class="form-label">Alamat Email (Login & Google) <span class="text-red-500">*</span></label>
                            <input type="email" name="email" class="form-input" value="<?= sanitize($profil['email']) ?>" required>
                        </div>
                    </div>
                </div>

                <!-- 3. Ubah Password (Opsional) -->
                <div class="card p-6">
                    <div class="flex items-center gap-2.5 mb-4 pb-3 border-b border-slate-100">
                        <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <i class="bi bi-shield-lock-fill"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Ubah Kata Sandi (Opsional)</h3>
                            <p class="text-xs text-slate-400">Kosongkan bagian ini jika Anda tidak ingin mengubah password saat ini</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">Password Saat Ini</label>
                            <input type="password" name="password_lama" class="form-input" placeholder="••••••••">
                        </div>
                        <div>
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password_baru" class="form-input" placeholder="Min. 6 karakter">
                        </div>
                        <div>
                            <label class="form-label">Ulangi Password Baru</label>
                            <input type="password" name="password_konfirmasi" class="form-input" placeholder="Ketik ulang...">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-slate-100">
                        <a href="<?= BASE_URL ?>/pages/dashboard/index.php" class="btn-secondary px-5">Batal</a>
                        <button type="submit" id="btnSimpanProfil" class="btn-primary px-6 shadow-lg shadow-primary-600/25">
                            <i class="bi bi-check2-circle"></i> Simpan Perubahan Profil
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
function tampilkanPreviewGambar(url) {
    const imgEl  = document.getElementById('avatarLivePreviewImg');
    const initEl = document.getElementById('avatarLivePreviewInitial');
    imgEl.src = url;
    imgEl.style.display = 'block';
    initEl.style.display = 'none';
}

function tampilkanPreviewInisial() {
    const imgEl  = document.getElementById('avatarLivePreviewImg');
    const initEl = document.getElementById('avatarLivePreviewInitial');
    imgEl.removeAttribute('src');
    imgEl.style.display = 'none';
    initEl.style.display = 'flex';
}

// 1. Pilih Karakter Avatar Preset
function pilihAvatarPreset(url, btnEl) {
    document.getElementById('avatarModeInput').value = 'preset';
    document.getElementById('presetAvatarInput').value = url;

    // Kosongkan file upload jika sebelumnya sempat pilih file
    document.getElementById('inputFotoProfil').value = '';

    // Tampilkan di Live Preview
    tampilkanPreviewGambar(url);

    // Highlight tombol preset yang dipilih
    document.querySelectorAll('.preset-avatar-btn').forEach(b => {
        b.className = 'preset-avatar-btn relative p-1.5 rounded-2xl border-2 transition-all hover:scale-105 border-slate-200 hover:border-primary-300 bg-slate-50';
    });
    btnEl.className = 'preset-avatar-btn relative p-1.5 rounded-2xl border-2 transition-all hover:scale-105 border-primary-600 bg-primary-50 ring-2 ring-primary-500/20';

    const statusText = document.getElementById('statusCropText');
    statusText.textContent = '✓ Karakter avatar dipilih. Klik Simpan Perubahan.';
    statusText.classList.remove('hidden');
}

// 2. Kembali ke Avatar Inisial Huruf
function resetKeInisial() {
    document.getElementById('avatarModeInput').value = 'remove';
    document.getElementById('presetAvatarInput').value = '';
    document.getElementById('inputFotoProfil').value = '';

    tampilkanPreviewInisial();

    document.querySelectorAll('.preset-avatar-btn').forEach(b => {
        b.className = 'preset-avatar-btn relative p-1.5 rounded-2xl border-2 transition-all hover:scale-105 border-slate-200 hover:border-primary-300 bg-slate-50';
    });

    const statusText = document.getElementById('statusCropText');
    statusText.textContent = '✓ Menggunakan avatar inisial. Klik Simpan Perubahan.';
    statusText.classList.remove('hidden');
}

// 3. Upload Foto Sendiri + Otomatis Center Square Crop (400x400px) & Kompresi
function handleAvatarFileSelect(inputEl) {
    const file = inputEl.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {
            // Buat kanvas persegi 400x400 agar foto profil selalu proporsional (Center Crop)
            const size   = 400;
            const canvas = document.createElement('canvas');
            canvas.width  = size;
            canvas.height = size;
            const ctx = canvas.getContext('2d');

            const minSide = Math.min(img.width, img.height);
            const sx = (img.width - minSide) / 2;
            const sy = (img.height - minSide) / 2;

            ctx.drawImage(img, sx, sy, minSide, minSide, 0, 0, size, size);

            canvas.toBlob(function(blob) {
                if (!blob) return;
                const croppedFile = new File([blob], 'avatar_cropped.jpg', {
                    type: 'image/jpeg',
                    lastModified: Date.now()
                });

                const dt = new DataTransfer();
                dt.items.add(croppedFile);
                inputEl.files = dt.files;

                // Update Live Preview
                const previewUrl = URL.createObjectURL(blob);
                tampilkanPreviewGambar(previewUrl);

                document.getElementById('avatarModeInput').value = 'upload';
                document.getElementById('presetAvatarInput').value = '';

                const statusText = document.getElementById('statusCropText');
                statusText.textContent = `✓ Foto dipotong rapi (${Math.round(blob.size / 1024)} KB). Klik Simpan Perubahan.`;
                statusText.classList.remove('hidden');
            }, 'image/jpeg', 0.85);
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>