// assets/js/daily-report.js
const semuaJadwal = window.semuaJadwalProyek || [];
const namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'];
const namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

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

    jadwalSelect.innerHTML = '<option value="">-- Fleksibel / Sesuai Lapangan --</option>';

    if (!sel.value) {
        infoBox.classList.add('hidden');
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

    updatePreview();
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

function tambahBarisActivity() {
    const container = document.getElementById('containerActivity');
    const idx = container.querySelectorAll('.activity-row').length;
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 activity-row group';
    div.innerHTML = `
        <span class="text-sm font-bold text-slate-400 w-5 text-right nomor-activity">${idx + 1}.</span>
        <input type="text" name="activity_item[${idx}]" class="form-input flex-1 activity-text" placeholder="Poin pekerjaan..." oninput="updatePreview()">
        <label class="flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors">
            <input type="checkbox" name="activity_done[${idx}]" value="1" class="w-4 h-4 text-emerald-600 rounded activity-check" checked onchange="updatePreview(); this.parentElement.classList.toggle('bg-emerald-50', this.checked); this.parentElement.classList.toggle('border-emerald-200', this.checked);">
            <span class="text-xs font-bold text-slate-600">Selesai</span>
        </label>
        <button type="button" class="p-2 bg-red-50 text-red-500 hover:bg-red-100 rounded-lg transition-colors opacity-0 group-hover:opacity-100" onclick="hapusBarisActivity(this)"><i class="bi bi-trash"></i></button>
    `;
    container.appendChild(div);
    urutkanNomorActivity();
    updatePreview();
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

function tambahBarisNextPlan() {
    const container = document.getElementById('containerNextPlan');
    const idx = container.querySelectorAll('.nextplan-row').length;
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 nextplan-row group';
    div.innerHTML = `
        <span class="text-sm font-bold text-slate-400 w-5 text-right nomor-nextplan">${idx + 1}.</span>
        <input type="text" name="next_plan_item[]" class="form-input flex-1 nextplan-text" placeholder="Rencana selanjutnya..." oninput="updatePreview()">
        <button type="button" class="p-2 bg-red-50 text-red-500 hover:bg-red-100 rounded-lg transition-colors opacity-0 group-hover:opacity-100" onclick="hapusBarisNextPlan(this)"><i class="bi bi-trash"></i></button>
    `;
    container.appendChild(div);
    urutkanNomorNextPlan();
    updatePreview();
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
    document.getElementById('badgeRealisasiHarian').innerText = `${totalDone}/${totalItem} Selesai (${persenHarian}%)`;

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
}

function copyKeWhatsApp() {
    const teks = document.getElementById('waOutput').innerText;
    navigator.clipboard.writeText(teks).then(() => {
        const alertBox = document.getElementById('copyAlert');
        alertBox.classList.remove('hidden');
        setTimeout(() => alertBox.classList.add('hidden'), 3000);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    hitungManpower();
    updatePreview();
});