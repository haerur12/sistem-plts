<?php
// includes/activity.php
// Helper untuk mencatat activity log

function logActivity(string $aksi, string $tipe, string $deskripsi, string $modul = '', array $metadata = []): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user']['id'])) return;

    try {
        $stmt = db()->prepare("
            INSERT INTO activity_log (user_id, aksi, tipe, deskripsi, modul, ip_address, user_agent, metadata)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user']['id'],
            $aksi,
            $tipe,
            $deskripsi,
            $modul,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            !empty($metadata) ? json_encode($metadata) : null,
        ]);
    } catch (Exception $e) {
        // Silent fail agar tidak mengganggu proses utama
    }
}

function updateLastSeen(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user']['id'])) return;

    try {
        $stmt = db()->prepare("UPDATE users SET last_seen = NOW(), is_online = 1 WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
    } catch (Exception $e) {}
}

function setOffline(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user']['id'])) return;

    try {
        $stmt = db()->prepare("UPDATE users SET is_online = 0 WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
    } catch (Exception $e) {}
}

function isUserOnline(int $userId): array {
    $stmt = db()->prepare("SELECT is_online, last_seen FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $u = $stmt->fetch();
    if (!$u) return ['online' => false, 'last_seen' => null];

    // Anggap online jika last_seen < 5 menit lalu
    $online = $u['is_online'] && $u['last_seen'] && (time() - strtotime($u['last_seen'])) < 300;
    return ['online' => $online, 'last_seen' => $u['last_seen']];
}

function formatLastSeen(?string $lastSeen, bool $online): string {
    if ($online) return '<span class="inline-flex items-center gap-1 text-emerald-600 font-medium"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 pulse-dot"></span>Online</span>';
    if (!$lastSeen) return '<span class="text-slate-400">Belum pernah</span>';

    $diff = time() - strtotime($lastSeen);
    if ($diff < 60) return '<span class="text-slate-500">Baru saja</span>';
    if ($diff < 3600) return '<span class="text-slate-500">' . floor($diff/60) . ' menit lalu</span>';
    if ($diff < 86400) return '<span class="text-slate-500">' . floor($diff/3600) . ' jam lalu</span>';
    return '<span class="text-slate-500">' . formatTanggal($lastSeen) . '</span>';
}

function getActivityIcon(string $tipe): array {
    $map = [
        'create'  => ['bi-plus-circle-fill',    'bg-emerald-100 text-emerald-600'],
        'update'  => ['bi-pencil-fill',         'bg-sky-100 text-sky-600'],
        'delete'  => ['bi-trash-fill',          'bg-red-100 text-red-600'],
        'login'   => ['bi-box-arrow-in-right',  'bg-indigo-100 text-indigo-600'],
        'logout'  => ['bi-box-arrow-right',     'bg-slate-100 text-slate-600'],
        'export'  => ['bi-download',            'bg-amber-100 text-amber-600'],
        'bulk'    => ['bi-stack',               'bg-violet-100 text-violet-600'],
        'view'    => ['bi-eye-fill',            'bg-cyan-100 text-cyan-600'],
        'other'   => ['bi-circle-fill',         'bg-slate-100 text-slate-500'],
    ];
    return $map[$tipe] ?? $map['other'];
}