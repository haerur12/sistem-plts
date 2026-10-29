// assets/js/app.js — VERSI FINAL LENGKAP

const BASE_URL = document.querySelector('meta[name="base-url"]')?.content || '';

// ============================================================
// 1. DARK MODE
// ============================================================
const DarkMode = {
    toggle() {
        const html = document.documentElement;
        const isDark = html.classList.toggle('dark');
        localStorage.setItem('plts_theme', isDark ? 'dark' : 'light');
        this.updateIcon(isDark);
        // Update Chart.js warna jika ada chart
        if (window.activeCharts) {
            window.activeCharts.forEach(chart => chart.update());
        }
    },

    init() {
        const saved = localStorage.getItem('plts_theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const isDark = saved === 'dark' || (!saved && prefersDark);
        document.documentElement.classList.toggle('dark', isDark);
        this.updateIcon(isDark);
    },

    updateIcon(isDark) {
        const icon = document.getElementById('darkIcon');
        if (!icon) return;
        icon.className = isDark ? 'bi bi-sun text-base' : 'bi bi-moon text-base';
    }
};

// ============================================================
// 2. TOAST NOTIFICATION
// ============================================================
const Toast = {
    container: null,

    init() {
        this.container = document.getElementById('toastContainer');
        // Cek flash dari server
        const el = document.getElementById('flashData');
        if (el) {
            const type = el.dataset.flashType;
            const msg = el.dataset.flashMsg;
            if (type && msg) {
                // Sedikit delay agar animasi terlihat
                setTimeout(() => this.show(msg, type), 100);
            }
        }
    },

    show(message, type = 'success', duration = 4000) {
        if (!this.container) return;
        const icons = {
            success: 'bi-check-circle-fill',
            error: 'bi-exclamation-triangle-fill',
            warning: 'bi-exclamation-circle-fill',
            info: 'bi-info-circle-fill',
        };
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-amber-500',
            info: 'bg-blue-500',
        };

        const id = 'toast_' + Date.now();
        const icon = icons[type] || icons.info;
        const bg = colors[type] || colors.info;
        const label = { success: 'Berhasil', error: 'Gagal', warning: 'Peringatan', info: 'Info' }[type] || '';

        const toast = document.createElement('div');
        toast.id = id;
        toast.className = `
            pointer-events-auto flex items-start gap-3 min-w-72 max-w-sm
            bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700
            rounded-2xl shadow-2xl p-4
            translate-x-full opacity-0 transition-all duration-300 ease-out
        `.trim();

        toast.innerHTML = `
            <div class="w-9 h-9 ${bg} rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="bi ${icon} text-white"></i>
            </div>
            <div class="flex-1 min-w-0 pt-0.5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">${label}</p>
                <p class="text-sm text-slate-800 dark:text-slate-200 mt-0.5 leading-snug">${message}</p>
                <div class="mt-2.5 h-0.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div class="h-full ${bg} opacity-60 rounded-full"
                        style="animation: toastProgress ${duration}ms linear forwards;"></div>
                </div>
            </div>
            <button onclick="Toast.dismiss('${id}')"
                class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-lg
                text-slate-300 hover:text-slate-500 hover:bg-slate-100
                dark:hover:bg-slate-700 transition-colors mt-0.5">
                <i class="bi bi-x text-base"></i>
            </button>`;

        this.container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
            });
        });

        if (duration > 0) {
            setTimeout(() => this.dismiss(id), duration);
        }
        return id;
    },

    dismiss(id) {
        const toast = document.getElementById(id);
        if (!toast) return;
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }
};

// ============================================================
// 3. SKELETON LOADER
// ============================================================
const Skeleton = {
    // Buat skeleton card
    card(lines = 3) {
        return `
            <div class="card p-5 animate-pulse">
                <div class="skeleton h-4 w-1/3 mb-4 rounded"></div>
                ${Array(lines).fill('<div class="skeleton h-3 w-full mb-2 rounded"></div>').join('')}
                <div class="skeleton h-3 w-2/3 rounded"></div>
            </div>`;
    },

    // Buat skeleton tabel row
    tableRow(cols = 5) {
        return `
            <tr>
                ${Array(cols).fill(`
                    <td class="table-cell">
                        <div class="skeleton h-3 rounded" style="width:${60 + Math.random() * 30}%"></div>
                    </td>`).join('')}
            </tr>`;
    },

    // Tampilkan skeleton di container
    show(containerId, type = 'card', count = 3) {
        const el = document.getElementById(containerId);
        if (!el) return;
        el.innerHTML = type === 'table'
            ? Array(count).fill(this.tableRow()).join('')
            : Array(count).fill(this.card()).join('');
    }
};

// ============================================================
// 4. SEARCH GLOBAL
// ============================================================
const Search = {
    input: null,
    dropdown: null,
    timer: null,

    init() {
        this.input = document.getElementById('globalSearch');
        this.dropdown = document.getElementById('searchDropdown');
        if (!this.input) return;

        this.input.addEventListener('input', () => this.onInput());
        this.input.addEventListener('keydown', e => {
            if (e.key === 'Escape') { this.close(); this.input.blur(); }
            if (e.key === 'ArrowDown') this.focusResult(0);
        });

        document.addEventListener('click', e => {
            if (!e.target.closest('#searchContainer')) this.close();
        });
    },

    onInput() {
        clearTimeout(this.timer);
        const q = this.input.value.trim();
        if (q.length < 2) { this.close(); return; }

        // Loading state
        this.dropdown.innerHTML = `
            <div class="px-4 py-4 flex items-center gap-2 text-sm text-slate-400">
                <div class="w-4 h-4 border-2 border-slate-300 border-t-primary-600 rounded-full animate-spin"></div>
                Mencari...
            </div>`;
        this.dropdown.classList.remove('hidden');

        this.timer = setTimeout(() => {
            fetch(`${BASE_URL}/api/search/global.php?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => this.renderResults(data, q))
                .catch(() => this.close());
        }, 280);
    },

    renderResults(data, q) {
        if (!data.results?.length) {
            this.dropdown.innerHTML = `
                <div class="px-4 py-8 text-center text-sm text-slate-400">
                    <i class="bi bi-search block text-2xl mb-1 opacity-30"></i>
                    Tidak ada hasil untuk <strong>"${q}"</strong>
                </div>`;
            return;
        }

        this.dropdown.innerHTML = data.results.map((r, i) => `
            <a href="${r.url}" class="search-result flex items-center gap-3 px-4 py-3
                hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0"
                data-idx="${i}" tabindex="-1">
                <div class="w-8 h-8 bg-primary-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="bi ${r.icon} text-primary-600 text-sm"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-medium text-slate-800 truncate">${r.title}</div>
                    <div class="text-xs text-slate-400 truncate">
                        <span class="text-primary-600">${r.type}</span> · ${r.subtitle}
                    </div>
                </div>
            </a>`).join('') +
            `<div class="px-4 py-2 border-t border-slate-50 text-xs text-slate-400 flex items-center gap-1">
                <i class="bi bi-arrow-return-left"></i> Enter untuk pilih · Esc untuk tutup
            </div>`;
    },

    close() {
        this.dropdown?.classList.add('hidden');
    },

    focusResult(idx) {
        const results = this.dropdown?.querySelectorAll('.search-result');
        results?.[idx]?.focus();
    }
};

// ============================================================
// 5. USER DROPDOWN
// ============================================================
function toggleUserMenu() {
    const dd = document.getElementById('userDropdown');
    dd?.classList.toggle('hidden');
}
document.addEventListener('click', e => {
    if (!e.target.closest('#userMenu')) {
        document.getElementById('userDropdown')?.classList.add('hidden');
    }
});

// ============================================================
// 6. SIDEBAR MOBILE
// ============================================================
function openSidebar() {
    document.getElementById('sidebar')?.classList.add('open');
    document.getElementById('sidebarOverlay')?.classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('sidebar')?.classList.remove('open');
    document.getElementById('sidebarOverlay')?.classList.remove('show');
    document.body.style.overflow = '';
}

// ============================================================
// 7. POLLING NOTIFIKASI
// ============================================================
function pollNotifikasi() {
    fetch(`${BASE_URL}/api/notifikasi/count.php`)
        .then(r => r.json())
        .then(data => {
            if (!data.authenticated) return;
            const count = parseInt(data.count) || 0;
            const dot = document.getElementById('notifDot');
            if (dot) dot.classList.toggle('hidden', count === 0);
            document.querySelectorAll('#notifBadge, #sidebarNotifBadge, .notif-badge').forEach(badge => {
                badge.classList.toggle('hidden', count === 0);
                badge.textContent = count > 9 ? '9+' : count;
            });
        })
        .catch(() => { });
}

// ============================================================
// 8. KEYBOARD SHORTCUTS
// ============================================================
document.addEventListener('keydown', e => {
    // Ctrl+K atau / → fokus search
    if ((e.ctrlKey && e.key === 'k') || (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName))) {
        e.preventDefault();
        document.getElementById('globalSearch')?.focus();
    }
    // Escape → tutup sidebar & dropdown
    if (e.key === 'Escape') {
        closeSidebar();
        document.getElementById('userDropdown')?.classList.add('hidden');
        Search.close();
    }
    // Alt+D → Dashboard
    if (e.altKey && e.key === 'd') {
        e.preventDefault();
        window.location = BASE_URL + '/pages/dashboard/index.php';
    }
    // Alt+N → Notifikasi
    if (e.altKey && e.key === 'n') {
        e.preventDefault();
        window.location = BASE_URL + '/pages/notifikasi/index.php';
    }
});

// ============================================================
// 9. MICRO ANIMATIONS & UX POLISH
// ============================================================
function initMicroAnimations() {
    // Ripple effect pada tombol
    document.querySelectorAll('.btn-primary, .btn-danger').forEach(btn => {
        btn.addEventListener('click', function (e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.cssText = `
                position:absolute; border-radius:50%;
                width:${size}px; height:${size}px;
                top:${e.clientY - rect.top - size / 2}px;
                left:${e.clientX - rect.left - size / 2}px;
                background:rgba(255,255,255,0.3);
                animation: ripple .5s ease-out forwards;
                pointer-events:none;`;
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 500);
        });
    });

    // Tabel row clickable (kecuali aksi)
    document.querySelectorAll('.table-row').forEach(row => {
        const link = row.querySelector('a[href]:not([href="#"])');
        if (!link) return;
        row.style.cursor = 'pointer';
        row.addEventListener('click', e => {
            if (e.target.closest('a, button, form, input, select')) return;
            window.location = link.href;
        });
    });

    // Lazy load images
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '100px' });

        document.querySelectorAll('img[data-src]').forEach(img => observer.observe(img));
    }

    // Auto-submit filter forms saat select berubah
    document.querySelectorAll('[data-auto-submit]').forEach(el => {
        el.addEventListener('change', () => el.closest('form')?.submit());
    });

    // Tooltip sederhana
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.setAttribute('title', el.dataset.tooltip);
    });
}

// ============================================================
// 10. CONFIRM DELETE MODAL (mengganti confirm() bawaan)
// ============================================================
function confirmDelete(formEl, message = 'Yakin ingin menghapus data ini?') {
    const modal = document.getElementById('deleteModal');
    const desc = document.getElementById('deleteModalDesc');
    const btn = document.getElementById('deleteModalBtn');

    if (!modal) {
        // Fallback ke confirm() jika modal belum ada
        return window.confirm(message);
    }

    desc.textContent = message;
    modal.classList.remove('hidden');

    btn.onclick = () => {
        modal.classList.add('hidden');
        if (typeof formEl === 'string') {
            document.getElementById(formEl)?.submit();
        } else {
            formEl?.submit();
        }
    };

    document.getElementById('deleteModalCancel').onclick = () => {
        modal.classList.add('hidden');
    };
}

// ============================================================
// INIT SEMUA
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    DarkMode.init();
    Toast.init();
    Search.init();
    initMicroAnimations();

    // Polling notifikasi mulai setelah 5 detik
    setTimeout(() => {
        pollNotifikasi();
        setInterval(pollNotifikasi, 30000);
    }, 5000);
});