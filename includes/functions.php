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
    $map = [
        'survei'      => ['bg-slate-100 text-slate-600',    'Survei'],
        'perencanaan' => ['bg-blue-100 text-blue-600',      'Perencanaan'],
        'instalasi'   => ['bg-indigo-100 text-indigo-700',  'Instalasi'],
        'selesai'     => ['bg-green-100 text-green-700',    'Selesai'],
        'ditunda'     => ['bg-yellow-100 text-yellow-700',  'Ditunda'],
        'terjadwal'   => ['bg-slate-100 text-slate-600',    'Terjadwal'],
        'berjalan'    => ['bg-blue-100 text-blue-700',      'Berjalan'],
        'belum_mulai' => ['bg-slate-100 text-slate-600',    'Belum Mulai'],
        'dalam_proses'=> ['bg-blue-100 text-blue-700',      'Dalam Proses'],
        'terkendala'  => ['bg-red-100 text-red-700',        'Terkendala'],
    ];
    [$cls, $label] = $map[$status] ?? ['bg-gray-100 text-gray-600', ucfirst($status)];
    return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $cls\">$label</span>";
}

function stokStatus(int $stok, int $min): array {
    if ($stok <= 0)           return ['bg-gray-100 text-gray-500',    'Habis',  'habis'];
    if ($stok <= $min)        return ['bg-red-100 text-red-700',      'Kritis', 'kritis'];
    if ($stok <= $min * 1.5)  return ['bg-yellow-100 text-yellow-700','Rendah', 'rendah'];
    return ['bg-green-100 text-green-700', 'Aman', 'aman'];
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