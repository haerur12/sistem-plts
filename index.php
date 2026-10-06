<?php
// index.php — redirect otomatis
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/pages/dashboard/index.php');
} else {
    header('Location: ' . BASE_URL . '/pages/auth/login.php');
}
exit;