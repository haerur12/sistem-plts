<?php
// pages/pengguna/create.php — TAMBAH PENGGUNA & PENDAFTARAN EMAIL GOOGLE
$pageTitle   = 'Tambah Pengguna Baru';
$currentPage = 'pengguna';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$roleOptions = [
    'teknisi_lapangan' => ['label' => 'Teknisi Lapangan', 'icon' => 'bi-tools',            'desc' => 'Input daily report & dokumentasi lapangan'],
    'manajer_proyek'   => ['label' => 'Manajer Proyek',   'icon' => 'bi-briefcase-fill',   'desc' => 'Kelola proyek, jadwal, & evaluasi laporan'],
    'gudang'           => ['label' => 'Staf Gudang',      'icon' => 'bi-box-seam-fill',    'desc' => 'Kelola stok komponen & material proyek'],
    'admin'            => ['label' => 'Administrator',    'icon' => 'bi-shield-lock-fill', 'desc' => 'Akses penuh seluruh fitur & data master'],
    'operator'         => ['label' => 'Operator Sistem',  'icon' => 'bi-shield-shaded',    'desc' => 'Pantau seluruh akses & atur matriks fitur tiap role'],
];

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-3">
        <a href="<?= BASE_URL ?>/pages/dashboard/index.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <a href="<?= BASE_URL ?>/pages/pengguna/index.php">Manajemen Pengguna</a>
        <span class="sep"><i class="bi bi-chevron-right text-[10px]"></i></span>
        <span class="current">Tambah Pengguna</span>
    </nav>

    <!-- Header -->
    <div class="flex items-center gap-3.5 mb-5">
        <a href="<?= BASE_URL ?>/pages/pengguna/index.php"
           class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-colors shadow-2xs">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Daftarkan Staf / Teknisi Baru</h2>
            <p class="text-xs text-slate-500 mt-0.5">Tambahkan alamat email Gmail staf agar mereka dapat login satu klik dengan Akun Google</p>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="<?= BASE_URL ?>/api/pengguna/store.php" class="space-y-5">
            <?= csrfField() ?>

            <!-- Info Box Google OAuth -->
            <div class="p-4 rounded-xl bg-sky-50/70 border border-sky-200 flex items-start gap-3">
                <i class="bi bi-google text-sky-600 text-lg mt-0.5"></i>
                <div class="text-xs text-sky-900 leading-relaxed">
                    <strong>Mendukung Login dengan Akun Google:</strong> Cukup daftarkan alamat email <strong>@gmail.com</strong> (atau email Google Workspace perusahaan) milik staf di bawah ini. Saat mereka menekan tombol <em>"Sign in with Google"</em> di halaman login, sistem akan otomatis mengenali email dan jabatan mereka.
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Nama Lengkap Staf <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" class="form-input" placeholder="Contoh: Ahmad Fauzi" required autofocus>
                </div>

                <div>
                    <label class="form-label">Alamat Email (Gmail / Perusahaan) <span class="text-red-500">*</span></label>
                    <input type="email" name="email" class="form-input" placeholder="contoh.nama@gmail.com" required>
                </div>
            </div>

            <!-- Pilihan Role Jabatan -->
            <div>
                <label class="form-label">Pilih Hak Akses / Role Jabatan <span class="text-red-500">*</span></label>
                <input type="hidden" name="role" id="selectedRole" value="teknisi_lapangan" required>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1.5">
                    <?php foreach ($roleOptions as $rKey => $rMeta): ?>
                    <button type="button" onclick="pilihRole('<?= $rKey ?>', this)"
                        class="role-card-btn p-3.5 rounded-xl border text-left transition-all flex items-start gap-3 <?= $rKey === 'teknisi_lapangan' ? 'border-primary-600 bg-primary-50/50 ring-2 ring-primary-500/20' : 'border-slate-200 bg-white hover:bg-slate-50' ?>">
                        <span class="w-9 h-9 rounded-lg bg-primary-100 text-primary-700 flex items-center justify-center flex-shrink-0">
                            <i class="bi <?= $rMeta['icon'] ?>"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-900"><?= $rMeta['label'] ?></div>
                            <div class="text-[11px] text-slate-400 mt-0.5 leading-snug"><?= $rMeta['desc'] ?></div>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pengaturan Password (Opsional jika pakai Login Google) -->
            <div class="pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <label class="form-label mb-0">Pengaturan Password Login</label>
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-primary-700 cursor-pointer">
                        <input type="checkbox" id="chkAutoPass" name="auto_password" value="1"
                               class="w-4 h-4 rounded text-primary-600" onchange="togglePasswordMode(this.checked)">
                        <span>Hanya Login via Google (Generate Password Acak)</span>
                    </label>
                </div>

                <div id="manualPassBox" class="relative">
                    <input type="password" name="password" id="passInput" class="form-input pr-24"
                           placeholder="Masukkan password awal (Min. 6 karakter)..." required minlength="6">
                    <button type="button" onclick="isiPasswordDefault()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-[11px] font-bold text-slate-600">
                        Isi Default
                    </button>
                </div>
                <p id="passHelpText" class="text-[11px] text-slate-400 mt-1.5">
                    Staf tetap bisa login menggunakan tombol Google maupun menggunakan password ini.
                </p>
            </div>

            <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
                <a href="<?= BASE_URL ?>/pages/pengguna/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="bi bi-check-lg"></i> Daftarkan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function pilihRole(roleKey, btnEl) {
    document.getElementById('selectedRole').value = roleKey;
    document.querySelectorAll('.role-card-btn').forEach(b => {
        b.className = 'role-card-btn p-3.5 rounded-xl border text-left transition-all flex items-start gap-3 border-slate-200 bg-white hover:bg-slate-50';
    });
    btnEl.className = 'role-card-btn p-3.5 rounded-xl border text-left transition-all flex items-start gap-3 border-primary-600 bg-primary-50/50 ring-2 ring-primary-500/20';
}

function togglePasswordMode(isGoogleOnly) {
    const passBox   = document.getElementById('manualPassBox');
    const passInput = document.getElementById('passInput');
    const helpText  = document.getElementById('passHelpText');

    if (isGoogleOnly) {
        passBox.classList.add('hidden');
        passInput.removeAttribute('required');
        passInput.value = '';
        helpText.innerHTML = '<span class="text-emerald-600 font-semibold"><i class="bi bi-shield-check"></i> Sistem akan membuatkan token password acak yang aman. Pengguna cukup klik "Sign in with Google" untuk masuk.</span>';
    } else {
        passBox.classList.remove('hidden');
        passInput.setAttribute('required', 'required');
        helpText.textContent = 'Staf tetap bisa login menggunakan tombol Google maupun menggunakan password ini.';
    }
}

function isiPasswordDefault() {
    const inp = document.getElementById('passInput');
    inp.type  = 'text';
    inp.value = 'elp2026';
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>