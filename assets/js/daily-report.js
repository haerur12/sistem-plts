// assets/js/daily-report.js

const semuaJadwal = window.semuaJadwalProyek || [];
const namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'];
const namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const DRAFT_KEY = 'plts_daily_report_draft_v1';

let lastPlanCache = null;
let selectedFilesDataTransfer = new DataTransfer();

function formatTanggalIndo(dateStr) {
    if (!dateStr) return '-';
    const parts = dateStr.split('-');
    const d = new Date(parts[0], parts[1] - 1, parts[2]);
    return `${namaHari[d.getDay()]}, ${d.getDate()} ${namaBulan[d.getMonth()]} ${d.getFullYear()}`;
}

function onProyekChange() {
    const sel = document.getElementById('proyek_id');
    const opt = sel.options[sel.selectedIndex];
    const infoBox = document.getElementById('infoTimelineBox');
    const jadwalSelect = document.getElementById('jadwal_id');
    const btnLoadLast = document.getElementById('btnLoadLastPlan');

    jadwalSelect.innerHTML = '<option value="">-- Fleksibel / Sesuai Lapangan --</option>';

    if (!sel.value) {
        infoBox.classList.add('hidden');
        if (btnLoadLast) btnLoadLast.classList.add('hidden');
        updatePreview();
        return;
    }

    infoBox.classList.remove('hidden');
    const proyId = sel.value;
    const tMulai = new Date(opt.getAttribute('data-mulai'));
    const tSelesai = new Date(opt.getAttribute('data-selesai'));
    const tLaporan = new Date(document.getElementById('tanggal_laporan').value);
    const progresSaatIni = parseInt(opt.getAttribute('data-progres')) || 0;

    document.getElementById('progres_proyek').value = progresSaatIni;
    document.getElementById('labelSliderProgres').innerText = progresSaatIni + '%';
    document.getElementById('aktualProgresTeks').innerText = progresSaatIni + '%';

    const totalDurasi = (tSelesai - tMulai) / (1000 * 60 * 60 * 24);
    const hariBerjalan = (tLaporan - tMulai) / (1000 * 60 * 60 * 24);
    let targetPersen = 0;
    if (totalDurasi > 0) {
        targetPersen = Math.round((hariBerjalan / totalDurasi) * 100);
        targetPersen = Math.max(0, Math.min(100, targetPersen));
    }

    document.getElementById('teksRentangTanggal').innerText =
        `${opt.getAttribute('data-mulai')} s/d ${opt.getAttribute('data-selesai')} (${Math.max(0, Math.round(totalDurasi))} Hari)`;
    document.getElementById('targetTimelinePersen').innerText = targetPersen + '%';
    document.getElementById('progres_proyek').setAttribute('data-target', targetPersen);

    evaluasiKecepatanProyek(progresSaatIni, targetPersen, tLaporan > tSelesai);

    semuaJadwal.filter(j => j.proyek_id == proyId).forEach(j => {
        const o = document.createElement('option');
        o.value = j.id;
        o.text = `${j.tanggal_mulai} s/d ${j.tanggal_selesai} — ${j.keterangan || 'Tahap Pekerjaan'} (${j.status})`;
        jadwalSelect.appendChild(o);
    });

    // Cek apakah proyek ini punya Next Plan dari laporan sebelumnya
    cekLaporanTerakhirProyek(proyId);
    updatePreview();
}

function cekLaporanTerakhirProyek(proyekId) {
    const btnLoadLast = document.getElementById('btnLoadLastPlan');
    if (!btnLoadLast) return;

    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
    fetch(`${baseUrl}/api/laporan/last-plan.php?proyek_id=${encodeURIComponent(proyekId)}`)
        .then(r => r.json())
        .then(data => {
            if (data.found && data.items && data.items.length > 0) {
                lastPlanCache = data;
                btnLoadLast.classList.remove('hidden');
                btnLoadLast.classList.add('inline-flex');
                btnLoadLast.title = `Muat ${data.items.length} poin dari laporan tanggal ${data.tanggal_laporan}`;
            } else {
                lastPlanCache = null;
                btnLoadLast.classList.add('hidden');
                btnLoadLast.classList.remove('inline-flex');
            }
        })
        .catch(() => {
            btnLoadLast.classList.add('hidden');
        });
}

function muatNextPlanKemarin() {
    if (!lastPlanCache || !lastPlanCache.items.length) return;

    const container = document.getElementById('containerActivity');
    container.innerHTML = '';

    lastPlanCache.items.forEach((it, idx) => {
        tambahBarisActivity(it.item, it.done === 1, false);
    });

    // Jika area kerja atau manpower kemarin ada, tawarkan pengisian otomatis
    if (lastPlanCache.area_kerja) {
        document.getElementById('area_kerja').value = lastPlanCache.area_kerja;
    }
    if (lastPlanCache.manpower) {
        const mp = lastPlanCache.manpower;
        const mapId = { pm: 'mp_pm', sm: 'mp_sm', pengawas: 'mp_pengawas', admin: 'mp_admin', hse: 'mp_hse', scaffolder: 'mp_scaffolder', teknisi: 'mp_teknisi', helper: 'mp_helper' };
        Object.keys(mapId).forEach(k => {
            if (mp[k] !== undefined && document.getElementById(mapId[k])) {
                document.getElementById(mapId[k]).value = mp[k];
            }
        });
        hitungManpower();
    }

    urutkanNomorActivity();
    updatePreview();
    if (typeof Toast !== 'undefined') {
        Toast.show(`${lastPlanCache.items.length} poin dari laporan (${lastPlanCache.tanggal_laporan}) berhasil dimuat!`, 'info');
    }
}

function onSliderProgresChange(val) {
    document.getElementById('labelSliderProgres').innerText = val + '%';
    const target = parseInt(document.getElementById('progres_proyek').getAttribute('data-target')) || 0;
    const sel = document.getElementById('proyek_id');
    const opt = sel.options[sel.selectedIndex];
    const isLewat = opt && opt.getAttribute('data-selesai')
        ? (new Date(document.getElementById('tanggal_laporan').value) > new Date(opt.getAttribute('data-selesai')))
        : false;
    evaluasiKecepatanProyek(parseInt(val), target, isLewat);
    simpanDraftOtomatis();
}

function evaluasiKecepatanProyek(aktual, targetTimeline, isOverdue) {
    const badge = document.getElementById('badgeStatusTimeline');
    const label = document.getElementById('statusKecepatanProyek');

    if (aktual >= 100) {
        badge.className = 'badge-modern bg-emerald-100 text-emerald-700 border border-emerald-200';
        badge.innerText = 'Selesai 100%';
        label.innerText = 'Tuntas';
    } else if (isOverdue && aktual < 100) {
        badge.className = 'badge-modern bg-red-100 text-red-700 border border-red-200';
        badge.innerText = 'Melewati Estimasi';
        label.innerText = 'Overdue';
    } else if (aktual > targetTimeline + 5) {
        badge.className = 'badge-modern bg-blue-100 text-blue-700 border border-blue-200';
        badge.innerText = 'Lebih Cepat';
        label.innerText = '+' + (aktual - targetTimeline) + '% dari target';
    } else if (aktual < targetTimeline - 10) {
        badge.className = 'badge-modern bg-amber-100 text-amber-700 border border-amber-200';
        badge.innerText = 'Di Bawah Target';
        label.innerText = (aktual - targetTimeline) + '% dari target';
    } else {
        badge.className = 'badge-modern bg-sky-100 text-sky-700 border border-sky-200';
        badge.innerText = 'Sesuai Timeline';
        label.innerText = 'On Track';
    }
}

function hitungManpower() {
    let total = 0;
    document.querySelectorAll('.mp-input').forEach(inp => {
        total += parseInt(inp.value) || 0;
    });
    document.getElementById('totalManpower').innerText = total;
    updatePreview();
}

function tambahBarisActivity(teksDefault = '', isDone = true, triggerUpdate = true) {
    const container = document.getElementById('containerActivity');
    const idx = container.querySelectorAll('.activity-row').length;
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 activity-row group';
    div.innerHTML = `
        <span class="text-sm font-bold text-slate-400 w-5 text-right nomor-activity">${idx + 1}.</span>
        <input type="text" name="activity_item[${idx}]" value="${teksDefault.replace(/"/g, '&quot;')}" class="form-input flex-1 activity-text" placeholder="Poin pekerjaan..." oninput="updatePreview()">
        <label class="flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors ${isDone ? 'bg-emerald-50 border-emerald-200' : ''}">
            <input type="checkbox" name="activity_done[${idx}]" value="1" class="w-4 h-4 text-emerald-600 rounded activity-check" ${isDone ? 'checked' : ''} onchange="updatePreview(); this.parentElement.classList.toggle('bg-emerald-50', this.checked); this.parentElement.classList.toggle('border-emerald-200', this.checked);">
            <span class="text-xs font-bold text-slate-600">Selesai</span>
        </label>
        <button type="button" class="p-2 bg-red-50 text-red-500 hover:bg-red-100 rounded-lg transition-colors opacity-0 group-hover:opacity-100" onclick="hapusBarisActivity(this)"><i class="bi bi-trash"></i></button>
    `;
    container.appendChild(div);
    if (triggerUpdate) {
        urutkanNomorActivity();
        updatePreview();
    }
}

function hapusBarisActivity(btn) {
    const container = document.getElementById('containerActivity');
    if (container.querySelectorAll('.activity-row').length > 1) {
        btn.closest('.activity-row').remove();
        urutkanNomorActivity();
        updatePreview();
    }
}

function urutkanNomorActivity() {
    document.querySelectorAll('#containerActivity .activity-row').forEach((row, i) => {
        row.querySelector('.nomor-activity').innerText = (i + 1) + '.';
        row.querySelector('.activity-text').name = `activity_item[${i}]`;
        row.querySelector('.activity-check').name = `activity_done[${i}]`;
    });
}

function tambahBarisNextPlan(teksDefault = '', triggerUpdate = true) {
    const container = document.getElementById('containerNextPlan');
    const idx = container.querySelectorAll('.nextplan-row').length;
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 nextplan-row group';
    div.innerHTML = `
        <span class="text-sm font-bold text-slate-400 w-5 text-right nomor-nextplan">${idx + 1}.</span>
        <input type="text" name="next_plan_item[]" value="${teksDefault.replace(/"/g, '&quot;')}" class="form-input flex-1 nextplan-text" placeholder="Rencana selanjutnya..." oninput="updatePreview()">
        <button type="button" class="p-2 bg-red-50 text-red-500 hover:bg-red-100 rounded-lg transition-colors opacity-0 group-hover:opacity-100" onclick="hapusBarisNextPlan(this)"><i class="bi bi-trash"></i></button>
    `;
    container.appendChild(div);
    if (triggerUpdate) {
        urutkanNomorNextPlan();
        updatePreview();
    }
}

function hapusBarisNextPlan(btn) {
    const container = document.getElementById('containerNextPlan');
    if (container.querySelectorAll('.nextplan-row').length > 1) {
        btn.closest('.nextplan-row').remove();
        urutkanNomorNextPlan();
        updatePreview();
    }
}

function urutkanNomorNextPlan() {
    document.querySelectorAll('#containerNextPlan .nextplan-row').forEach((row, i) => {
        row.querySelector('.nomor-nextplan').innerText = (i + 1) + '.';
    });
}

function formatMpLine(val, suffix = 'orang') {
    const n = parseInt(val) || 0;
    return n === 0 ? '-' : `${n} ${suffix}`;
}

function updatePreview() {
    const salam = document.getElementById('salam_pembuka').value.replace(/_/g, '');
    const judul = document.getElementById('judul_laporan').value || 'Maintenance Solar PV Rooftop';

    const sel = document.getElementById('proyek_id');
    const opt = sel.options[sel.selectedIndex];
    const namaKlienProyek = (sel.value && opt)
        ? `${opt.getAttribute('data-klien')}`
        : 'PT Yamaha Motor Parts Manufacturing Indonesia KIIC';

    const tglIndo = formatTanggalIndo(document.getElementById('tanggal_laporan').value);
    const jamCuaca = document.getElementById('jam_cuaca').value;
    const kondisiCuaca = document.getElementById('kondisi_cuaca').value;
    const areaKerja = document.getElementById('area_kerja').value;

    const pm = document.getElementById('mp_pm').value;
    const sm = document.getElementById('mp_sm').value;
    const pengawas = document.getElementById('mp_pengawas').value;
    const admin = document.getElementById('mp_admin').value;
    const hse = document.getElementById('mp_hse').value;
    const scaffolder = document.getElementById('mp_scaffolder').value;
    const teknisi = document.getElementById('mp_teknisi').value;
    const helper = document.getElementById('mp_helper').value;
    const totalMp = document.getElementById('totalManpower').innerText;

    let teksActivity = '';
    let totalItem = 0;
    let totalDone = 0;
    document.querySelectorAll('#containerActivity .activity-row').forEach((row, idx) => {
        const teks = row.querySelector('.activity-text').value.trim();
        const isChecked = row.querySelector('.activity-check').checked;
        if (teks !== '') {
            totalItem++;
            if (isChecked) totalDone++;
            teksActivity += `${idx + 1}. ${teks}${isChecked ? ' ✓' : ''}\n`;
        }
    });

    const persenHarian = totalItem > 0 ? Math.round((totalDone / totalItem) * 100) : 0;
    document.getElementById('badgeRealisasiHarian').innerText = `${totalDone}/${totalItem} (${persenHarian}%)`;

    let teksNextPlan = '';
    document.querySelectorAll('#containerNextPlan .nextplan-row').forEach((row, idx) => {
        const teks = row.querySelector('.nextplan-text').value.trim();
        if (teks !== '') {
            teksNextPlan += `${idx + 1}. ${teks}\n`;
        }
    });

    const waText = `_${salam}_

*Daily Report Project*
_${judul}_
*${namaKlienProyek}*


Date :
${tglIndo}


1. Project Manager : ${formatMpLine(pm)}
2. Site Manager : ${formatMpLine(sm)}
3. Pengawas Teknik : ${formatMpLine(pengawas)}
4. Admin : ${formatMpLine(admin)}
5. Hse / Safetyman : ${formatMpLine(hse)}
6. Scaffolder : ${formatMpLine(scaffolder)}
7. Teknisi : ${formatMpLine(teknisi)}
8. Helper : ${formatMpLine(helper)}
   Total =  ${totalMp} orang    

*Weather Report*
${jamCuaca}  ${kondisiCuaca}

*Activity Progress :*
 ${areaKerja}


${teksActivity.trim()}

*Next Plan* :


${teksNextPlan.trim()}

_terima kasih_`;

    document.getElementById('waOutput').innerText = waText;
    simpanDraftOtomatis();
}

function copyKeWhatsApp() {
    const teks = document.getElementById('waOutput').innerText;
    navigator.clipboard.writeText(teks).then(() => {
        const alertBox = document.getElementById('copyAlert');
        alertBox.classList.remove('hidden');
        setTimeout(() => alertBox.classList.add('hidden'), 3000);
    });
}

// ============================================================
// FITUR AUTO-SAVE DRAFT (LOCALSTORAGE)
// ============================================================
function simpanDraftOtomatis() {
    const activities = [];
    document.querySelectorAll('#containerActivity .activity-row').forEach(row => {
        activities.push({
            teks: row.querySelector('.activity-text').value,
            done: row.querySelector('.activity-check').checked ? 1 : 0
        });
    });

    const nextPlans = [];
    document.querySelectorAll('#containerNextPlan .nextplan-row').forEach(row => {
        nextPlans.push(row.querySelector('.nextplan-text').value);
    });

    const draft = {
        proyek_id: document.getElementById('proyek_id')?.value || '',
        judul_laporan: document.getElementById('judul_laporan')?.value || '',
        area_kerja: document.getElementById('area_kerja')?.value || '',
        tanggal_laporan: document.getElementById('tanggal_laporan')?.value || '',
        jam_cuaca: document.getElementById('jam_cuaca')?.value || '',
        kondisi_cuaca: document.getElementById('kondisi_cuaca')?.value || 'Cerah',
        mp: {
            pm: document.getElementById('mp_pm')?.value || 0,
            sm: document.getElementById('mp_sm')?.value || 0,
            pengawas: document.getElementById('mp_pengawas')?.value || 0,
            admin: document.getElementById('mp_admin')?.value || 0,
            hse: document.getElementById('mp_hse')?.value || 0,
            scaffolder: document.getElementById('mp_scaffolder')?.value || 0,
            teknisi: document.getElementById('mp_teknisi')?.value || 0,
            helper: document.getElementById('mp_helper')?.value || 0,
        },
        activities: activities,
        nextPlans: nextPlans,
        deskripsi: document.getElementById('deskripsi')?.value || '',
        savedAt: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
    };

    localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
    const badge = document.getElementById('draftStatusBadge');
    const text = document.getElementById('draftStatusText');
    if (badge && text) {
        badge.classList.remove('hidden');
        badge.classList.add('inline-flex');
        text.innerText = `Draf disimpan (${draft.savedAt})`;
    }
}

function terapkanDraftTersimpan() {
    const raw = localStorage.getItem(DRAFT_KEY);
    if (!raw) return;
    try {
        const d = JSON.parse(raw);
        if (d.proyek_id) document.getElementById('proyek_id').value = d.proyek_id;
        if (d.judul_laporan) document.getElementById('judul_laporan').value = d.judul_laporan;
        if (d.area_kerja) document.getElementById('area_kerja').value = d.area_kerja;
        if (d.tanggal_laporan) document.getElementById('tanggal_laporan').value = d.tanggal_laporan;
        if (d.jam_cuaca) document.getElementById('jam_cuaca').value = d.jam_cuaca;
        if (d.kondisi_cuaca) document.getElementById('kondisi_cuaca').value = d.kondisi_cuaca;
        if (d.deskripsi && document.getElementById('deskripsi')) document.getElementById('deskripsi').value = d.deskripsi;

        if (d.mp) {
            const mapId = { pm: 'mp_pm', sm: 'mp_sm', pengawas: 'mp_pengawas', admin: 'mp_admin', hse: 'mp_hse', scaffolder: 'mp_scaffolder', teknisi: 'mp_teknisi', helper: 'mp_helper' };
            Object.keys(mapId).forEach(k => {
                if (d.mp[k] !== undefined && document.getElementById(mapId[k])) {
                    document.getElementById(mapId[k]).value = d.mp[k];
                }
            });
        }

        if (Array.isArray(d.activities) && d.activities.length) {
            const cAct = document.getElementById('containerActivity');
            cAct.innerHTML = '';
            d.activities.forEach(a => tambahBarisActivity(a.teks, a.done === 1, false));
            urutkanNomorActivity();
        }

        if (Array.isArray(d.nextPlans) && d.nextPlans.length) {
            const cNext = document.getElementById('containerNextPlan');
            cNext.innerHTML = '';
            d.nextPlans.forEach(np => tambahBarisNextPlan(np, false));
            urutkanNomorNextPlan();
        }

        document.getElementById('draftRestoreBanner')?.classList.add('hidden');
        onProyekChange();
        hitungManpower();
        if (typeof Toast !== 'undefined') Toast.show('Draf laporan berhasil dipulihkan!', 'success');
    } catch (e) { }
}

function abaikanDraftTersimpan() {
    localStorage.removeItem(DRAFT_KEY);
    document.getElementById('draftRestoreBanner')?.classList.add('hidden');
}

function resetDraftForm() {
    localStorage.removeItem(DRAFT_KEY);
    location.reload();
}

// ============================================================
// FITUR DRAG & DROP + LIVE PREVIEW + KOMPRESI FOTO OTOMATIS
// ============================================================
async function compressImageFile(file, maxWidth = 1400, quality = 0.78) {
    if (!file.type.startsWith('image/') || file.size < 250 * 1024) {
        return file; // Jangan kompres jika sudah di bawah 250 KB
    }
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                let width = img.width;
                let height = img.height;
                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                }
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                canvas.toBlob((blob) => {
                    if (!blob || blob.size >= file.size) {
                        resolve(file);
                    } else {
                        const newFile = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        resolve(newFile);
                    }
                }, 'image/jpeg', quality);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}

async function handleFotoSelect(fileList) {
    if (!fileList || !fileList.length) return;
    const infoEl = document.getElementById('infoKompresiFoto');
    if (infoEl) {
        infoEl.classList.remove('hidden');
        infoEl.innerText = 'Mengompresi foto...';
    }

    let totalBefore = 0;
    let totalAfter = 0;

    for (const file of Array.from(fileList)) {
        if (!file.type.startsWith('image/')) continue;
        totalBefore += file.size;
        const compressed = await compressImageFile(file);
        totalAfter += compressed.size;
        selectedFilesDataTransfer.items.add(compressed);
    }

    document.getElementById('inputFotoLapangan').files = selectedFilesDataTransfer.files;
    renderPreviewFoto();

    if (infoEl && totalBefore > 0) {
        const savedPct = Math.max(0, Math.round((1 - totalAfter / totalBefore) * 100));
        const afterKB = Math.round(totalAfter / 1024);
        infoEl.innerText = `✓ ${selectedFilesDataTransfer.files.length} Foto siap unggah (${afterKB} KB · Hemat ${savedPct}%)`;
    }
}

function hapusFotoPreview(idx) {
    const dtBaru = new DataTransfer();
    Array.from(selectedFilesDataTransfer.files).forEach((f, i) => {
        if (i !== idx) dtBaru.items.add(f);
    });
    selectedFilesDataTransfer = dtBaru;
    document.getElementById('inputFotoLapangan').files = selectedFilesDataTransfer.files;
    renderPreviewFoto();
}

function renderPreviewFoto() {
    const container = document.getElementById('previewFotoContainer');
    const infoEl = document.getElementById('infoKompresiFoto');
    if (!container) return;

    const files = Array.from(selectedFilesDataTransfer.files);
    if (!files.length) {
        container.classList.add('hidden');
        container.innerHTML = '';
        if (infoEl) infoEl.classList.add('hidden');
        return;
    }

    container.classList.remove('hidden');
    container.innerHTML = '';

    files.forEach((file, idx) => {
        const url = URL.createObjectURL(file);
        const sizeKB = Math.round(file.size / 1024);
        const card = document.createElement('div');
        card.className = 'relative group aspect-video rounded-xl overflow-hidden bg-slate-100 border border-slate-200';
        card.innerHTML = `
            <img src="${url}" class="w-full h-full object-cover">
            <div class="absolute bottom-0 inset-x-0 bg-black/60 text-white text-[10px] px-2 py-1 flex justify-between items-center">
                <span class="truncate">${file.name}</span>
                <span class="font-bold ml-1">${sizeKB}KB</span>
            </div>
            <button type="button" onclick="hapusFotoPreview(${idx})"
                class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-red-500 text-white flex items-center justify-center text-xs shadow-md hover:bg-red-600">
                <i class="bi bi-x"></i>
            </button>
        `;
        container.appendChild(card);
    });
}

// Inisialisasi saat DOM siap
document.addEventListener('DOMContentLoaded', () => {
    // Cek apakah ada draf tersimpan
    if (localStorage.getItem(DRAFT_KEY)) {
        document.getElementById('draftRestoreBanner')?.classList.remove('hidden');
    }

    // Drag & drop listener pada dropzone foto
    const dz = document.getElementById('dropzoneFoto');
    if (dz) {
        dz.addEventListener('dragover', (e) => {
            e.preventDefault();
            dz.classList.add('border-primary-500', 'bg-primary-50/50');
        });
        dz.addEventListener('dragleave', () => {
            dz.classList.remove('border-primary-500', 'bg-primary-50/50');
        });
        dz.addEventListener('drop', (e) => {
            e.preventDefault();
            dz.classList.remove('border-primary-500', 'bg-primary-50/50');
            if (e.dataTransfer.files.length) {
                handleFotoSelect(e.dataTransfer.files);
            }
        });
    }

    // Hapus draf otomatis ketika form berhasil di-submit
    document.getElementById('formLaporan')?.addEventListener('submit', () => {
        localStorage.removeItem(DRAFT_KEY);
        const btn = document.getElementById('btnSubmitLaporan');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i> Menyimpan Laporan...';
        }
    });

    hitungManpower();
    updatePreview();
});