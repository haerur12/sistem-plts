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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Sistem Monitoring PLTS' ?> — PT Energy Lima Pilar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=2">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50:'#eef2ff', 100:'#e0e7ff', 200:'#c7d2fe', 300:'#a5b4fc',
                            400:'#818cf8', 500:'#6366f1', 600:'#4f46e5', 700:'#4338ca',
                            800:'#3730a3', 900:'#1e1b4b'
                        }
                    },
                    boxShadow: {
                        card: '0 1px 3px rgba(15,23,42,.05), 0 8px 24px -12px rgba(15,23,42,.10)',
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        [x-cloak] { display: none !important; }
        .sidebar-link { @apply flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-300/90 hover:bg-white/10 hover:text-white transition-all duration-200 text-sm; }
        .sidebar-link.active { @apply bg-white/15 text-white font-medium; }
        .card { @apply bg-white rounded-2xl shadow-card border border-slate-200/70; }
        .btn-primary { @apply inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors; }
        .btn-secondary { @apply inline-flex items-center gap-2 px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-600 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm transition-colors; }
        .btn-danger { @apply inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors; }
        .btn-sm { @apply px-3 py-1.5 text-xs; }
        .form-input { @apply w-full px-3.5 py-2.5 border border-slate-200 bg-white rounded-xl text-sm placeholder:text-slate-400 focus:outline-none focus:ring-4 focus:ring-primary-500/15 focus:border-primary-500 transition-all; }
        .form-label { @apply block text-sm font-semibold text-slate-700 mb-1.5; }
        .form-select { @apply w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-4 focus:ring-primary-500/15 focus:border-primary-500 bg-white; }
        .table-header { @apply px-5 py-3.5 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider; }
        .table-cell { @apply px-5 py-3.5 text-sm text-slate-700; }
        .table-row { @apply border-b border-slate-100 hover:bg-primary-50/40 transition-colors; }
    </style>
</head>
<body class="bg-slate-100/70 text-slate-800 antialiased">
<div class="flex h-screen overflow-hidden">
