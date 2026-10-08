<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
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

    <script>
    // Terapkan dark mode SEBELUM render untuk hindari flash
    (function() {
        const saved = localStorage.getItem('plts_theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (saved === 'dark' || (!saved && prefersDark)) {
            document.documentElement.classList.add('dark');
        }
    })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=3">

    <script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    primary: {
                        50 :'#e8eaf6', 100:'#c5cae9', 200:'#9fa8da',
                        500:'#3f51b5', 600:'#3949ab', 700:'#303f9f',
                        800:'#283593', 900:'#1a237e'
                    }
                },
                animation: {
                    'shimmer': 'shimmer 1.5s infinite',
                    'pulse-slow': 'pulse 3s infinite',
                }
            }
        }
    }
    </script>

    <style>
    /* ---- DARK MODE BASE COLORS ---- */
    html.dark body           { background:#0f172a !important; color:#e2e8f0 !important; }
    html.dark .card          { background:#1e293b !important; border-color:#334155 !important; }
    html.dark aside          { background:#0f172a !important; }
    html.dark header.topbar  { background:#1e293b !important; border-color:#334155 !important; }
    html.dark .bg-white      { background:#1e293b !important; }
    html.dark .bg-slate-50   { background:#0f172a !important; }
    html.dark .bg-slate-100  { background:#1e293b !important; }
    html.dark .border-slate-100,
    html.dark .border-slate-200 { border-color:#334155 !important; }
    html.dark .text-slate-800,
    html.dark .text-slate-700 { color:#e2e8f0 !important; }
    html.dark .text-slate-600,
    html.dark .text-slate-500 { color:#94a3b8 !important; }
    html.dark .text-slate-400 { color:#64748b !important; }
    html.dark table          { color:#e2e8f0; }
    html.dark .table-light,
    html.dark thead          { background:#0f172a !important; }
    html.dark .bg-slate-50.rounded-xl { background:#0f172a !important; }
    html.dark input, html.dark select, html.dark textarea {
        background:#0f172a !important;
        border-color:#475569 !important;
        color:#e2e8f0 !important;
    }
    html.dark input::placeholder { color:#64748b !important; }
    html.dark .form-input, html.dark .form-select {
        background:#0f172a !important;
        border-color:#475569 !important;
        color:#e2e8f0 !important;
    }
    html.dark .hover\:bg-slate-50:hover { background:#1e293b !important; }
    html.dark .hover\:bg-slate-100:hover { background:#334155 !important; }
    html.dark .divide-slate-50 > * { border-color:#1e293b !important; }
    html.dark #toastContainer .bg-white { background:#1e293b !important; border-color:#475569 !important; }
    html.dark #searchDropdown { background:#1e293b; border-color:#475569; }

    /* ---- SKELETON ---- */
    .skeleton {
        background: linear-gradient(90deg, #e2e8f0 25%, #f8fafc 50%, #e2e8f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 6px;
    }
    html.dark .skeleton {
        background: linear-gradient(90deg, #1e293b 25%, #334155 50%, #1e293b 75%);
        background-size: 200% 100%;
    }
    @keyframes shimmer {
        0%   { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* ---- BREADCRUMB ---- */
    .breadcrumb { display:flex; align-items:center; gap:6px; padding:8px 0 0; }
    .breadcrumb a { font-size:12px; color:#64748b; text-decoration:none; }
    .breadcrumb a:hover { color:#1a237e; }
    html.dark .breadcrumb a { color:#94a3b8; }
    html.dark .breadcrumb a:hover { color:#93c5fd; }
    .breadcrumb span { font-size:12px; color:#cbd5e1; }
    .breadcrumb .current { font-size:12px; color:#1a237e; font-weight:500; }
    html.dark .breadcrumb .current { color:#93c5fd; }

    /* ---- TOAST ---- */
    @keyframes toastProgress {
        from { width: 100%; }
        to   { width: 0%; }
    }

    /* ---- MICRO ANIMATIONS ---- */
    .card-hover { transition: box-shadow .2s ease, transform .2s ease; }
    .card-hover:hover { box-shadow: 0 8px 30px rgba(0,0,0,.1); transform: translateY(-2px); }

    .btn-primary, .btn-secondary, .btn-danger {
        position: relative; overflow: hidden;
    }
    .btn-primary::after, .btn-secondary::after, .btn-danger::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(255,255,255,0);
        transition: background .2s;
    }
    .btn-primary:hover::after { background: rgba(255,255,255,.1); }
    .btn-danger:hover::after  { background: rgba(255,255,255,.1); }

    /* Pulse untuk stok kritis */
    .stok-kritis-pulse {
        animation: pulse-border 2s infinite;
    }
    @keyframes pulse-border {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0); }
        50%       { box-shadow: 0 0 0 4px rgba(239,68,68,0.2); }
    }

    /* Smooth page transition */
    main { animation: fadeSlideIn .25s ease-out; }
    @keyframes fadeSlideIn {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    html.dark ::-webkit-scrollbar-thumb { background: #475569; }

    /* Sidebar mobile */
    @media (max-width: 1023px) {
        #sidebar {
            position: fixed; top: 0; left: 0;
            height: 100vh; z-index: 200;
            transform: translateX(-100%);
            transition: transform .3s ease;
        }
        #sidebar.open { transform: translateX(0); }
        #sidebarOverlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.5); z-index: 199;
        }
        #sidebarOverlay.show { display: block; }
    }

    /* Search dropdown */
    #searchDropdown {
        position: absolute; top: calc(100% + 8px); left: 0; right: 0;
        background: white; border: 1px solid #e2e8f0;
        border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,.12);
        z-index: 300; max-height: 380px; overflow-y: auto;
    }

    /* Print */
    @media print {
        aside, header, .no-print { display: none !important; }
        main { animation: none; }
    }
    </style>
</head>
<body class="bg-slate-100/70 text-slate-800 antialiased" <?= $flash ? 'data-flash-type="'.htmlspecialchars($flash['type']).'" data-flash-msg="'.htmlspecialchars($flash['message']).'"' : '' ?>>
<div class="flex h-screen overflow-hidden">
