<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/activity.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['user'])) {
    logActivity('logout', 'logout', 'Keluar dari sistem', 'auth');
    setOffline();
}

$_SESSION = [];
session_destroy();
redirect(BASE_URL . '/pages/auth/login.php');