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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#e8eaf6', 100:'#c5cae9', 500:'#3f51b5', 600:'#3949ab', 700:'#303f9f', 800:'#283593', 900:'#1a237e' }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-link { @apply flex items-center gap-3 px-4 py-2.5 rounded-lg text-slate-300 hover:bg-white/10 hover:text-white transition-all duration-200 text-sm; }
        .sidebar-link.active { @apply bg-white/15 text-white font-medium; }
        .card { @apply bg-white rounded-2xl shadow-sm border border-slate-100; }
        .btn-primary { @apply inline-flex items-center gap-2 px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white text-sm font-medium rounded-lg transition-colors; }
        .btn-secondary { @apply inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors; }
        .btn-danger { @apply inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors; }
        .btn-sm { @apply px-3 py-1.5 text-xs; }
        .form-input { @apply w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all; }
        .form-label { @apply block text-sm font-medium text-slate-700 mb-1.5; }
        .form-select { @apply w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white; }
        .table-header { @apply px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider; }
        .table-cell { @apply px-4 py-3 text-sm text-slate-700; }
        .table-row { @apply border-b border-slate-50 hover:bg-slate-50/50 transition-colors; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">
<div class="flex h-screen overflow-hidden">