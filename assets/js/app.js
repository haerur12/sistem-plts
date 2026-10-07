// assets/js/app.js

// Auto hide flash message setelah 5 detik dengan animasi halus
document.addEventListener('DOMContentLoaded', function () {
    const flash = document.getElementById('flashMsg');
    if (flash) {
        setTimeout(() => {
            flash.style.transition = 'opacity .5s ease, transform .5s ease';
            flash.style.opacity    = '0';
            flash.style.transform  = 'translateY(-8px)';
            setTimeout(() => flash.remove(), 500);
        }, 5000);
    }
});

// Konfirmasi form delete (global)
document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', function (e) {
        if (!confirm(this.dataset.confirm)) e.preventDefault();
    });
});

// Format angka ribuan
function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

// Toggle sidebar di mobile
function toggleSidebar() {
    const sidebar = document.querySelector('aside');
    if (sidebar) sidebar.classList.toggle('-translate-x-full');
}

// Tutup modal dengan tombol Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('modalHapus');
        if (modal && !modal.classList.contains('hidden') && typeof closeModal === 'function') {
            closeModal();
        }
    }
});
