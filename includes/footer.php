<?php
// includes/footer.php — VERSI BERSIH & RINGAN
?>
    </main>
</div><!-- end main content -->
</div><!-- end flex wrapper -->

<?php
// Tambahkan di includes/footer.php tepat setelah </div><!-- end flex wrapper -->
$curr = $currentPage ?? '';
?>
<!-- ===== MOBILE BOTTOM NAVIGATION BAR (Hanya tampil di HP/Tablet < 1024px) ===== -->
<nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 z-40 px-2 py-1.5 flex items-center justify-around shadow-[0_-4px_20px_rgba(15,23,42,0.06)] no-print">
    <a href="<?= BASE_URL ?>/pages/dashboard/index.php"
       class="flex flex-col items-center justify-center py-1 px-3 rounded-xl transition-colors <?= $curr === 'dashboard' ? 'text-primary-600 font-bold' : 'text-slate-400 hover:text-slate-600' ?>">
        <i class="bi bi-speedometer2 text-lg leading-none"></i>
        <span class="text-[10px] mt-1">Home</span>
    </a>

    <?php if (!isGudang()): ?>
    <a href="<?= BASE_URL ?>/pages/laporan/index.php"
       class="flex flex-col items-center justify-center py-1 px-3 rounded-xl transition-colors <?= $curr === 'laporan' ? 'text-primary-600 font-bold' : 'text-slate-400 hover:text-slate-600' ?>">
        <i class="bi bi-file-earmark-text text-lg leading-none"></i>
        <span class="text-[10px] mt-1">Laporan</span>
    </a>

    <!-- Tombol Tengah Menonjol: Input Daily Report Cepat -->
    <a href="<?= BASE_URL ?>/pages/laporan/create.php"
       class="flex flex-col items-center justify-center -mt-5">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-600 to-indigo-700 text-white flex items-center justify-center shadow-lg shadow-primary-600/35 ring-4 ring-white dark:ring-slate-900 active:scale-95 transition-transform">
            <i class="bi bi-plus-lg text-xl"></i>
        </div>
        <span class="text-[10px] font-bold text-primary-700 dark:text-primary-400 mt-1">Buat Baru</span>
    </a>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>/pages/komponen/status.php"
       class="flex flex-col items-center justify-center py-1 px-3 rounded-xl transition-colors <?= $curr === 'status-material' ? 'text-primary-600 font-bold' : 'text-slate-400 hover:text-slate-600' ?>">
        <i class="bi bi-boxes text-lg leading-none"></i>
        <span class="text-[10px] mt-1">Stok</span>
    </a>

    <button type="button" onclick="openSidebar()"
       class="flex flex-col items-center justify-center py-1 px-3 rounded-xl text-slate-400 hover:text-slate-600 transition-colors">
        <i class="bi bi-grid-fill text-lg leading-none"></i>
        <span class="text-[10px] mt-1">Menu</span>
    </button>
</nav>

<!-- ===== TOAST CONTAINER ===== -->
<div id="toastContainer"
    class="fixed bottom-5 right-5 z-[999] flex flex-col gap-2.5 pointer-events-none"
    aria-live="polite"></div>

<!-- ===== MODAL KONFIRMASI HAPUS ===== -->
<div id="deleteModal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-[998] flex items-center justify-center p-4 hidden"
    onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm p-6 border border-slate-100" onclick="event.stopPropagation()">
        <div class="w-14 h-14 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-exclamation-triangle text-2xl"></i>
        </div>
        <h3 class="text-center font-bold text-slate-800 dark:text-slate-200 mb-2">Konfirmasi Hapus</h3>
        <p class="text-center text-sm text-slate-500 mb-6" id="deleteModalDesc">
            Apakah Anda yakin ingin menghapus data ini?
        </p>
        <div class="flex gap-3">
            <button type="button" id="deleteModalCancel" class="flex-1 btn-secondary justify-center">
                Batal
            </button>
            <button type="button" id="deleteModalBtn" class="flex-1 btn-danger justify-center">
                <i class="bi bi-trash"></i> Hapus
            </button>
        </div>
    </div>
</div>

<!-- ===== KEYBOARD SHORTCUT HINT ===== -->
<div id="shortcutHint"
    class="fixed bottom-5 left-5 z-50 hidden bg-slate-900 text-white text-xs rounded-xl px-4 py-3 shadow-xl">
    <div class="font-semibold mb-2 text-slate-300">Keyboard Shortcuts</div>
    <div class="space-y-1.5">
        <div class="flex justify-between gap-6"><span>Buka pencarian</span><kbd class="bg-slate-700 px-1.5 py-0.5 rounded">Ctrl+K</kbd></div>
        <div class="flex justify-between gap-6"><span>Dashboard</span><kbd class="bg-slate-700 px-1.5 py-0.5 rounded">Alt+D</kbd></div>
        <div class="flex justify-between gap-6"><span>Notifikasi</span><kbd class="bg-slate-700 px-1.5 py-0.5 rounded">Alt+N</kbd></div>
        <div class="flex justify-between gap-6"><span>Tutup Modal</span><kbd class="bg-slate-700 px-1.5 py-0.5 rounded">Esc</kbd></div>
    </div>
</div>

<button type="button" onclick="document.getElementById('shortcutHint').classList.toggle('hidden')"
    class="fixed bottom-6 right-5 z-40 w-9 h-9 rounded-full bg-white border border-slate-200
    hover:bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold transition-colors shadow-md no-print"
    title="Keyboard Shortcuts">
    ?
</button>

<style>
@keyframes ripple {
    from { transform: scale(0); opacity: 1; }
    to   { transform: scale(2); opacity: 0; }
}
@keyframes toastProgress {
    from { width: 100%; }
    to   { width: 0%; }
}
</style>

<script src="<?= BASE_URL ?>js/app.js"></script>
</body>
</html>