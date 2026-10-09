<?php
// includes/functions.php
function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function statusBadge(string $status): string {
    // [kelas badge, kelas titik status, label]
    $map = [
        'survei'      => ['bg-slate-50 text-slate-600 ring-1 ring-slate-200',       'bg-slate-400',       'Survei'],
        'perencanaan' => ['bg-sky-50 text-sky-700 ring-1 ring-sky-200',             'bg-sky-500',         'Perencanaan'],
        'instalasi'   => ['bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200',    'bg-indigo-500',      'Instalasi'],
        'selesai'     => ['bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200', 'bg-emerald-500',     'Selesai'],
        'ditunda'     => ['bg-amber-50 text-amber-700 ring-1 ring-amber-200',       'bg-amber-500',       'Ditunda'],
        'terjadwal'   => ['bg-slate-50 text-slate-600 ring-1 ring-slate-200',       'bg-slate-400',       'Terjadwal'],
        'berjalan'    => ['bg-blue-50 text-blue-700 ring-1 ring-blue-200',          'bg-blue-500',        'Berjalan'],
        'belum_mulai' => ['bg-slate-50 text-slate-600 ring-1 ring-slate-200',       'bg-slate-400',       'Belum Mulai'],
        'dalam_proses'=> ['bg-blue-50 text-blue-700 ring-1 ring-blue-200',          'bg-blue-500',        'Dalam Proses'],
        'terkendala'  => ['bg-red-50 text-red-700 ring-1 ring-red-200',             'bg-red-500',         'Terkendala'],
    ];
    [$cls, $dot, $label] = $map[$status] ?? ['bg-gray-50 text-gray-600 ring-1 ring-gray-200', 'bg-gray-400', ucfirst($status)];
    return "<span class=\"badge-modern inline-flex items-center gap-1.5 rounded-full text-xs font-semibold $cls\">"
         . "<span class=\"w-1.5 h-1.5 rounded-full $dot\"></span>$label</span>";
}

function stokStatus(int $stok, int $min): array {
    if ($stok <= 0)           return ['bg-gray-50 text-gray-500 ring-1 ring-gray-200',       'Habis',  'habis'];
    if ($stok <= $min)        return ['bg-red-50 text-red-700 ring-1 ring-red-200',          'Kritis', 'kritis'];
    if ($stok <= $min * 1.5)  return ['bg-amber-50 text-amber-700 ring-1 ring-amber-200',    'Rendah', 'rendah'];
    return ['bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200', 'Aman', 'aman'];
}

function formatTanggal(string $date): string {
    return date('d M Y', strtotime($date));
}

function formatTanggalShort(string $date): string {
    return date('d/m/Y', strtotime($date));
}

function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60)     return 'Baru saja';
    if ($diff < 3600)   return floor($diff/60) . ' menit lalu';
    if ($diff < 86400)  return floor($diff/3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff/86400) . ' hari lalu';
    return formatTanggal($datetime);
}

function kirimNotifikasi(int $userId, string $judul, string $pesan): void {
    $stmt = db()->prepare("INSERT INTO notifikasi (user_id, judul, pesan) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $judul, $pesan]);
}

function kirimNotifikasiKeRole(string $role, string $judul, string $pesan): void {
    $stmt = db()->prepare("SELECT id FROM users WHERE role = ? AND status = 'aktif'");
    $stmt->execute([$role]);
    $users = $stmt->fetchAll();
    foreach ($users as $u) {
        kirimNotifikasi($u['id'], $judul, $pesan);
    }
}

function countNotifBelumDibaca(int $userId): int {
    $stmt = db()->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND status_baca = 'belum_dibaca'");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function csrfField(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . sanitize($_SESSION['csrf_token']) . '">';
}

function verifyCsrfToken(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}