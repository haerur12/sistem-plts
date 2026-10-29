<?php
// pages/laporan/create.php
$pageTitle   = 'Input Laporan Lapangan';
$currentPage = 'laporan';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin','manajer_proyek','teknisi_lapangan']);

$db = db();

$proyek_list = $db->query("
    SELECT p.*, pel.nama_pelanggan 
    FROM proyek p 
    JOIN pelanggan pel ON p.pelanggan_id = pel.id 
    ORDER BY p.created_at DESC
")->fetchAll();

$jadwal_list = $db->query("SELECT * FROM jadwal ORDER BY tanggal_mulai ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Header Halaman Sesuai Tema Web Anda -->
    <div class="flex items-center gap-3 mb-5">
        <a href="<?= BASE_URL ?>/pages/laporan/index.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-500"></i>
        </a>
        <div>
            <h2 class="text-lg font-bold text-slate-900 tracking-tight">Input Laporan Harian Lapangan</h2>
            <p class="text-sm text-slate-400">Daily report teknisi & pemantauan progres proyek PLTS</p>
        </div>
    </div>

    <form action="<?= BASE_URL ?>/api/laporan/store.php" method="POST" enctype="multipart/form-data" id="formLaporan">
        <?= csrfField() ?>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            <!-- KOLOM KIRI: FORM INPUT -->
            <div class="xl:col-span-7 space-y-6">
                
                <!-- 1. Informasi Proyek & Cuaca -->
                <div class="card p-6">
                    <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-3">
                        <span class="w-6 h-6 rounded bg-primary-100 text-primary-600 flex items-center justify-center text-xs font-bold">1</span>
                        <h3 class="text-sm font-bold text-slate-800">Proyek, Lokasi & Cuaca</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">Pilih Proyek PLTS <span class="text-red-500">*</span></label>
                            <select name="proyek_id" id="proyek_id" class="form-select" required onchange="onProyekChange()">
                                <option value="">-- Pilih Proyek --</option>
                                <?php foreach ($proyek_list as $p): ?>
                                    <option value="<?= $p['id'] ?>"
                                        data-nama="<?= sanitize($p['nama_proyek']) ?>"
                                        data-klien="<?= sanitize($p['nama_pelanggan']) ?>"
                                        data-mulai="<?= $p['tanggal_mulai'] ?>"
                                        data-selesai="<?= $p['tanggal_selesai'] ?>"
                                        data-progres="<?= $p['progres'] ?>">
                                        <?= sanitize($p['nama_proyek']) ?> — <?= sanitize($p['nama_pelanggan']) ?> (<?= $p['progres'] ?>%)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Info Timeline Proyek -->
                        <div id="infoTimelineBox" class="hidden bg-sky-50 border border-sky-200 rounded-xl p-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-bold text-sky-800 uppercase tracking-wider">Acuan Timeline Kontrak</span>
                                <span class="badge-modern bg-white border border-slate-200 text-slate-700" id="badgeStatusTimeline">-</span>
                            </div>
                            <p class="text-xs text-slate-500 mb-2" id="teksRentangTanggal">-</p>
                            <div class="flex justify-between text-sm">
                                <span>Target Waktu: <strong class="text-sky-700" id="targetTimelinePersen">0%</strong></span>
                                <span>Progres Aktual: <strong class="text-emerald-600" id="aktualProgresTeks">0%</strong></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Acuan Jadwal <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                <select name="jadwal_id" id="jadwal_id" class="form-select">
                                    <option value="">-- Fleksibel / Sesuai Lapangan --</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Status Tahapan Jadwal</label>
                                <select name="status_jadwal" class="form-select">
                                    <option value="berjalan">Berjalan</option>
                                    <option value="selesai">Selesai</option>
                                    <option value="ditunda">Ditunda</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Judul / Jenis Pekerjaan <span class="text-red-500">*</span></label>
                                <input type="text" name="judul_laporan" id="judul_laporan" class="form-input" 
                                       value="Maintenance Solar PV Rooftop" required oninput="updatePreview()">
                            </div>
                            <div>
                                <label class="form-label">Area / Gedung <span class="text-red-500">*</span></label>
                                <input type="text" name="area_kerja" id="area_kerja" class="form-input" 
                                       value="Building 4" placeholder="Contoh: Building 4" required oninput="updatePreview()">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="form-label">Tanggal Laporan <span class="text-red-500">*</span></label>
                                <input type="date" name="tanggal_laporan" id="tanggal_laporan" class="form-input" 
                                       value="<?= date('Y-m-d') ?>" required onchange="onProyekChange(); updatePreview();">
                            </div>
                            <div>
                                <label class="form-label">Jam (Cuaca)</label>
                                <input type="text" name="jam_cuaca" id="jam_cuaca" class="form-input" 
                                       value="08.00 - 17.00" oninput="updatePreview()">
                            </div>
                            <div>
                                <label class="form-label">Kondisi Cuaca</label>
                                <select name="kondisi_cuaca" id="kondisi_cuaca" class="form-select" onchange="updatePreview()">
                                    <option value="Cerah">Cerah</option>
                                    <option value="Cerah Berawan">Cerah Berawan</option>
                                    <option value="Mendung">Mendung</option>
                                    <option value="Hujan Ringan">Hujan Ringan</option>
                                    <option value="Hujan Deras">Hujan Deras</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Manpower -->
                <div class="card p-6">
                    <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded bg-amber-100 text-amber-600 flex items-center justify-center text-xs font-bold">2</span>
                            <h3 class="text-sm font-bold text-slate-800">Personel Lapangan (Manpower)</h3>
                        </div>
                        <span class="badge-modern bg-slate-100 text-slate-700 border border-slate-200">Total: <span id="totalManpower">8</span></span>
                    </div>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <?php
                        $roles_mp = [
                            'mp_pm'         => ['1. Project Manager', 0], 'mp_sm'         => ['2. Site Manager', 1],
                            'mp_pengawas'   => ['3. Pengawas Teknik', 1], 'mp_admin'      => ['4. Admin', 0],
                            'mp_hse'        => ['5. HSE / Safetyman', 1], 'mp_scaffolder' => ['6. Scaffolder', 0],
                            'mp_teknisi'    => ['7. Teknisi', 1],         'mp_helper'     => ['8. Helper', 4],
                        ];
                        foreach ($roles_mp as $name => $info): ?>
                        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100 hover:border-primary-300 transition-colors">
                            <label class="block text-[11px] font-bold text-slate-500 mb-1.5 truncate"><?= $info[0] ?></label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="<?= $name ?>" id="<?= $name ?>" class="form-input py-1.5 text-sm mp-input" 
                                       min="0" value="<?= $info[1] ?>" oninput="hitungManpower()">
                                <span class="text-xs text-slate-400 font-medium">Org</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 3. Activity Progress -->
                <div class="card p-6">
                    <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold">3</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Activity Progress</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Ketik poin pengerjaan, centang jika selesai.</p>
                            </div>
                        </div>
                        <span class="badge-modern bg-emerald-50 text-emerald-700 border border-emerald-200" id="badgeRealisasiHarian">7/8 (88%)</span>
                    </div>

                    <div class="space-y-3" id="containerActivity">
                        <?php
                        $contoh_activity = [
                            ['teks' => 'Pengukuran Volt DC', 'done' => 1],
                            ['teks' => 'Pengukuran Ampere DC', 'done' => 1],
                            ['teks' => 'Pengukuran Volt AC Inverter', 'done' => 1],
                            ['teks' => 'Pengukuran Volt AC Combiner', 'done' => 1],
                            ['teks' => 'Pengukuran Grounding', 'done' => 1],
                            ['teks' => 'Cleaning Inverter', 'done' => 1],
                            ['teks' => 'Cleaning Panel DC Combiner', 'done' => 1],
                            ['teks' => 'Pengukuran Ampere Inverter 3 & 4', 'done' => 0],
                        ];
                        foreach ($contoh_activity as $i => $act):
                        ?>
                        <div class="flex items-center gap-2 activity-row group">
                            <span class="text-sm font-bold text-slate-400 w-5 text-right nomor-activity"><?= $i + 1 ?>.</span>
                            <input type="text" name="activity_item[<?= $i ?>]" class="form-input flex-1 activity-text" 
                                   value="<?= sanitize($act['teks']) ?>" placeholder="Poin pekerjaan..." oninput="updatePreview()">
                            <label class="flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors <?= $act['done'] ? 'bg-emerald-50 border-emerald-200' : '' ?>">
                                <input type="checkbox" name="activity_done[<?= $i ?>]" value="1" class="w-4 h-4 text-emerald-600 rounded activity-check" <?= $act['done'] ? 'checked' : '' ?> onchange="updatePreview(); this.parentElement.classList.toggle('bg-emerald-50', this.checked); this.parentElement.classList.toggle('border-emerald-200', this.checked);">
                                <span class="text-xs font-bold text-slate-600">Selesai</span>
                            </label>
                            <button type="button" class="p-2 bg-red-50 text-red-500 hover:bg-red-100 rounded-lg transition-colors opacity-0 group-hover:opacity-100" onclick="hapusBarisActivity(this)"><i class="bi bi-trash"></i></button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-secondary btn-sm mt-3" onclick="tambahBarisActivity()">
                        <i class="bi bi-plus-lg"></i> Tambah Pekerjaan
                    </button>
                </div>

                <!-- 4. Next Plan -->
                <div class="card p-6">
                    <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-3">
                        <span class="w-6 h-6 rounded bg-purple-100 text-purple-600 flex items-center justify-center text-xs font-bold">4</span>
                        <h3 class="text-sm font-bold text-slate-800">Next Plan (Rencana Selanjutnya)</h3>
                    </div>
                    <div class="space-y-3" id="containerNextPlan">
                        <?php
                        $contoh_next = ['Pengukuran Ampere Inverter 1 & 2', 'Pengerjaan Sample tagging Tray'];
                        foreach ($contoh_next as $j => $np):
                        ?>
                        <div class="flex items-center gap-2 nextplan-row group">
                            <span class="text-sm font-bold text-slate-400 w-5 text-right nomor-nextplan"><?= $j + 1 ?>.</span>
                            <input type="text" name="next_plan_item[]" class="form-input flex-1 nextplan-text" 
                                   value="<?= sanitize($np) ?>" placeholder="Rencana selanjutnya..." oninput="updatePreview()">
                            <button type="button" class="p-2 bg-red-50 text-red-500 hover:bg-red-100 rounded-lg transition-colors opacity-0 group-hover:opacity-100" onclick="hapusBarisNextPlan(this)"><i class="bi bi-trash"></i></button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-secondary btn-sm mt-3" onclick="tambahBarisNextPlan()">
                        <i class="bi bi-plus-lg"></i> Tambah Rencana
                    </button>
                </div>

                <!-- 5. Progres & Foto -->
                <div class="card p-6">
                    <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-3">
                        <span class="w-6 h-6 rounded bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">5</span>
                        <h3 class="text-sm font-bold text-slate-800">Update Progres & Dokumentasi</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
                        <div>
                            <label class="form-label">Status Laporan Hari Ini</label>
                            <select name="status_pengerjaan" class="form-select">
                                <option value="dalam_proses">Dalam Proses</option>
                                <option value="selesai">Selesai (Target Tuntas)</option>
                                <option value="terkendala">Terkendala</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label flex justify-between">
                                <span>Update Total Progres (%)</span>
                                <strong id="labelSliderProgres" class="text-primary-700">65%</strong>
                            </label>
                            <input type="range" name="progres_proyek" id="progres_proyek" class="w-full mt-2" 
                                   min="0" max="100" value="65" oninput="onSliderProgresChange(this.value)">
                            <div class="flex justify-between text-xs text-slate-400 mt-1">
                                <span>0%</span>
                                <span id="statusKecepatanProyek" class="font-bold text-primary-600">-</span>
                                <span>100%</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Upload Foto Dokumentasi Lapangan</label>
                        <input type="file" name="foto_lapangan[]" class="form-input" accept="image/*" multiple>
                        <p class="text-xs text-slate-400 mt-1">Bisa pilih lebih dari 1 foto sekaligus.</p>
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Catatan Tambahan / Kendala <span class="text-slate-400 font-normal">(Opsional)</span></label>
                        <textarea name="deskripsi" class="form-textarea" rows="2" placeholder="Tulis kendala di lapangan..."></textarea>
                    </div>

                    <div class="flex gap-3 pt-4 border-t border-slate-100">
                        <a href="<?= BASE_URL ?>/pages/laporan/index.php" class="btn-secondary flex-1 justify-center">Batal</a>
                        <button type="submit" class="btn-primary flex-1 justify-center shadow-lg shadow-primary-600/30">
                            <i class="bi bi-save"></i> Simpan Laporan
                        </button>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: PREVIEW WHATSAPP -->
            <div class="xl:col-span-5">
                <div class="card p-0 overflow-hidden sticky top-6 border-0 shadow-lg shadow-slate-200/50">
                    <!-- WA Header -->
                    <div class="bg-[#075e54] px-4 py-3 flex items-center justify-between text-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="bi bi-whatsapp text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm leading-tight">Preview WhatsApp</h3>
                                <p class="text-[11px] opacity-80">PT Energy Lima Pilar</p>
                            </div>
                        </div>
                        <button type="button" class="bg-white/10 hover:bg-white/20 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition-colors" onclick="copyKeWhatsApp()">
                            Copy Teks
                        </button>
                    </div>

                    <!-- WA Body -->
                    <div class="p-4" style="background-color: #efeae2; background-image: radial-gradient(#d1cbbf 1px, transparent 1px); background-size: 18px 18px;">
                        <select id="salam_pembuka" class="form-select form-select-sm mb-3 shadow-sm border-0" onchange="updatePreview()">
                            <option value="Selamat malam">Salam: Selamat malam</option>
                            <option value="Selamat sore">Salam: Selamat sore</option>
                            <option value="Selamat pagi">Salam: Selamat pagi</option>
                        </select>

                        <!-- WA Bubble -->
                        <div class="bg-[#d9fdd3] text-[#111b21] p-3 rounded-xl rounded-tr-none shadow-sm relative text-[13.5px] leading-relaxed" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                            <pre id="waOutput" class="whitespace-pre-wrap font-inherit m-0"></pre>
                            <div class="text-right mt-1 opacity-60 text-[10px]">
                                <?= date('H:i') ?> <i class="bi bi-check-all text-info"></i>
                            </div>
                        </div>

                        <div id="copyAlert" class="mt-3 bg-emerald-500 text-white text-xs font-bold p-2 rounded-lg text-center shadow-md hidden animate-pulse">
                            <i class="bi bi-clipboard-check"></i> Teks berhasil disalin!
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    window.semuaJadwalProyek = <?= json_encode($jadwal_list) ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/daily-report.js"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>