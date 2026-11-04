<?php
// includes/auth.php — DYNAMIC RBAC & OPERATOR ACCESS CONTROL

function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user'])) {
        header('Location: ' . BASE_URL . '/pages/auth/login.php');
        exit;
    }
}

function currentUser(): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    static $synced = false;
    if (!$synced && !empty($_SESSION['user']['id']) && function_exists('db')) {
        $synced = true;
        try {
            $stmt = db()->prepare("SELECT nama, email, role, avatar FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user']['id']]);
            if ($fresh = $stmt->fetch()) {
                $_SESSION['user']['nama']  = $fresh['nama'];
                $_SESSION['user']['email'] = $fresh['email'];
                if (!empty($fresh['role'])) {
                    $_SESSION['user']['role'] = $fresh['role'];
                }
                $_SESSION['user']['avatar'] = $fresh['avatar'] ?? null;
            }
        } catch (Exception $e) {}
    }
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['user'] ?? [];
}

function isRole(string $role): bool {
    return (currentUser()['role'] ?? '') === $role;
}

function hasRole(array $roles): bool {
    $userRole = currentUser()['role'] ?? '';
    // Operator selalu memiliki akses penuh ke seluruh sistem
    if ($userRole === 'operator') return true;
    return in_array($userRole, $roles, true);
}

function isOperator(): bool       { return isRole('operator'); }
function isAdmin(): bool          { return hasRole(['operator', 'admin']); }
function isManajer(): bool        { return isRole('manajer_proyek'); }
function isTeknisi(): bool        { return isRole('teknisi_lapangan'); }
function isGudang(): bool         { return isRole('gudang'); }
function isAdminOrManajer(): bool { return hasRole(['operator', 'admin', 'manajer_proyek']); }
function isAdminOrGudang(): bool  { return hasRole(['operator', 'admin', 'gudang']); }

/**
 * Memeriksa apakah pengguna saat ini diizinkan mengakses modul & aksi tertentu
 * berdasarkan pengaturan Operator di tabel role_permissions
 */
function canAccess(string $modul, string $action = 'view'): bool {
    $user = currentUser();
    if (empty($user['role'])) return false;
    $role = $user['role'];

    // Operator memegang kendali penuh atas seluruh fitur
    if ($role === 'operator') return true;

    // Modul umum yang selalu terbuka untuk pengguna yang login
    if (in_array($modul, ['notifikasi', 'profil'], true)) return true;

    static $permCache = null;
    if ($permCache === null) {
        $permCache = [];
        try {
            $stmt = db()->query("SELECT role, modul, can_view, can_create, can_edit, can_delete FROM role_permissions");
            foreach ($stmt->fetchAll() as $row) {
                $permCache[$row['role']][$row['modul']] = [
                    'view'   => (bool)$row['can_view'],
                    'create' => (bool)$row['can_create'],
                    'edit'   => (bool)$row['can_edit'],
                    'delete' => (bool)$row['can_delete'],
                ];
            }
        } catch (Exception $e) {
            // Fallback jika tabel role_permissions belum dibuat
            return true;
        }
    }

    if (!isset($permCache[$role][$modul])) {
        return false;
    }

    return $permCache[$role][$modul][$action] ?? false;
}

/**
 * Validasi akses halaman (Mendukung pengecekan Role + Matriks Permission Operator)
 */
function requireRole(array $roles, ?string $modul = null, string $action = 'view'): void {
    requireLogin();
    $userRole = $_SESSION['user']['role'] ?? '';

    // Operator memiliki akses penuh ke seluruh halaman
    if ($userRole === 'operator') {
        return;
    }

    // Deteksi modul otomatis dari variabel global $currentPage jika tidak dikirim
    $targetModul = $modul ?? ($GLOBALS['currentPage'] ?? null);

    // Jika tabel role_permissions aktif dan modul terdeteksi, gunakan aturan dari Operator
    if ($targetModul && !in_array($targetModul, ['notifikasi', 'profil'], true)) {
        try {
            $stmt = db()->prepare("SELECT COUNT(*) FROM role_permissions WHERE role = ?");
            $stmt->execute([$userRole]);
            if ((int)$stmt->fetchColumn() > 0) {
                if (!canAccess($targetModul, $action)) {
                    http_response_code(403);
                    include __DIR__ . '/../pages/errors/403.php';
                    exit;
                }
                return;
            }
        } catch (Exception $e) {}
    }

    // Fallback ke pengecekan array role standar
    if (!in_array($userRole, $roles, true)) {
        http_response_code(403);
        include __DIR__ . '/../pages/errors/403.php';
        exit;
    }
}