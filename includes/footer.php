<?php
// includes/footer.php — versi final
?>
    </main>
</div><!-- end main content -->
</div><!-- end flex wrapper -->

<!-- ===== TOAST CONTAINER ===== -->
<div id="toastContainer"
    class="fixed bottom-5 right-5 z-[999] flex flex-col gap-2.5 pointer-events-none"
    aria-live="polite"></div>

<!-- ===== MODAL KONFIRMASI HAPUS ===== -->
<div id="deleteModal"
    class="fixed inset-0 bg-black/50 z-[998] flex items-center justify-center p-4 hidden"
    onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm p-6" onclick="event.stopPropagation()">
        <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-exclamation-triangle text-red-500 text-2xl"></i>
        </div>
        <h3 class="text-center font-semibold text-slate-800 dark:text-slate-200 mb-2">Konfirmasi Hapus</h3>
        <p class="text-center text-sm text-slate-500 mb-6" id="deleteModalDesc">
            Apakah Anda yakin ingin menghapus data ini?
        </p>
        <div class="flex gap-3">
            <button id="deleteModalCancel"
                class="flex-1 btn-secondary justify-center">
                Batal
            </button>
            <button id="deleteModalBtn"
                class="flex-1 btn-danger justify-center">
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
        <div class="flex justify-between gap-6"><span>Dark mode</span><kbd class="bg-slate-700 px-1.5 py-0.5 rounded">?</kbd></div>
        <div class="flex justify-between gap-6"><span>Tutup</span><kbd class="bg-slate-700 px-1.5 py-0.5 rounded">Esc</kbd></div>
    </div>
</div>

<!-- Tombol ? untuk shortcut hint -->
<button onclick="document.getElementById('shortcutHint').classList.toggle('hidden')"
    class="fixed bottom-20 right-5 z-50 w-9 h-9 rounded-full bg-slate-200 dark:bg-slate-700
    hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300
    flex items-center justify-center text-sm font-semibold transition-colors shadow-md"
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

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>