// assets/js/app.js

// Auto hide flash message setelah 4 detik
document.addEventListener('DOMContentLoaded', function () {
    const flash = document.getElementById('flashMsg');
    if (flash) {
        setTimeout(() => {
            flash.style.transition = 'opacity 0.5s ease';
            flash.style.opacity    = '0';
            setTimeout(() => flash.remove(), 500);
        }, 4000);
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
    sidebar.classList.toggle('-translate-x-full');
}