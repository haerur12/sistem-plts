<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
$user        = currentUser();
$notifCount  = isset($user['id']) ? countNotifBelumDibaca($user['id']) : 0;
$flash       = getFlash();
?>
<!DOCTYPE html>
<html lang="id" id="htmlRoot">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="base-url" content="<?= BASE_URL ?>">
<title><?= $pageTitle ?? 'Sistem Monitoring PLTS' ?> — PT Energy Lima Pilar</title>

<!-- Preload font -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Anti-flash dark mode -->
<script>
(function() {
    const saved = localStorage.getItem('plts_theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (saved === 'dark' || (!saved && prefersDark)) {
        document.documentElement.classList.add('dark');
    }
})();
</script>

<!-- TailwindCSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<!-- Custom CSS -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=5">

<script>
tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            colors: {
                primary: {
                    50:  '#ecfeff',
                    100: '#cffafe',
                    200: '#a5f3fc',
                    300: '#67e8f9',
                    400: '#22d3ee',
                    500: '#06b6d4',
                    600: '#0891b2',
                    700: '#0e7490',
                    800: '#155e75',
                    900: '#0C4A8C',
                },
                accent: {
                    DEFAULT: '#2DD4BF',
                    light: '#5eead4',
                    dark: '#14b8a6',
                },
            },
            boxShadow: {
                'card': '0 1px 3px rgba(12, 74, 140, .06), 0 1px 2px rgba(12, 74, 140, .04)',
                'card-hover': '0 10px 15px -3px rgba(15,23,42,.08), 0 4px 6px -4px rgba(15,23,42,.04)',
            },
            borderRadius: {
                'xl': '16px',
                '2xl': '24px',
            }
        }
    }
}
</script>

<style>
/* Smooth page transition */
main {
    animation: fadeUp 0.4s cubic-bezier(0.22, 1, 0.36, 1);
}

/* Sidebar mobile */
@media (max-width: 1023px) {
    #sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        z-index: 200;
        transform: translateX(-100%);
        transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
    }
    #sidebar.open { transform: translateX(0); }
    #sidebarOverlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(4px);
        z-index: 199;
    }
    #sidebarOverlay.show { display: block; }
}

/* Search dropdown */
#searchDropdown {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    background: white;
    border: 1px solid var(--slate-200);
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    z-index: 300;
    max-height: 380px;
    overflow-y: auto;
}

html.dark #searchDropdown {
    background: var(--slate-800);
    border-color: var(--slate-700);
}
</style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased" <?= $flash ? 'data-flash-type="'.htmlspecialchars($flash['type']).'" data-flash-msg="'.htmlspecialchars($flash['message']).'"' : '' ?>>
<div class="flex h-screen overflow-hidden">