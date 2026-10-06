<?php
// includes/auth.php
function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user'])) {
        header('Location: ' . BASE_URL . '/pages/auth/login.php');
        exit;
    }
}

function requireRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['user']['role'], $roles)) {
        http_response_code(403);
        include __DIR__ . '/../pages/errors/403.php';
        exit;
    }
}

function currentUser(): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['user'] ?? [];
}

function isRole(string $role): bool {
    return (currentUser()['role'] ?? '') === $role;
}

function hasRole(array $roles): bool {
    return in_array(currentUser()['role'] ?? '', $roles);
}

function isAdmin(): bool          { return isRole('admin'); }
function isManajer(): bool        { return isRole('manajer_proyek'); }
function isTeknisi(): bool        { return isRole('teknisi_lapangan'); }
function isGudang(): bool         { return isRole('gudang'); }
function isAdminOrManajer(): bool { return hasRole(['admin','manajer_proyek']); }
function isAdminOrGudang(): bool  { return hasRole(['admin','gudang']); }