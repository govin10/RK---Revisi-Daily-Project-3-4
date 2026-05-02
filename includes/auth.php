<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /system-pelacak-alumni/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: /system-pelacak-alumni/dashboard.php?error=akses_ditolak');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'   => $_SESSION['user_id'],
        'nama' => $_SESSION['user_nama'],
        'role' => $_SESSION['user_role'],
        'username' => $_SESSION['username'],
    ];
}

function logActivity($aksi, $detail = '') {
    if (!isLoggedIn()) return;
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, aksi, detail, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $aksi, $detail, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {}
}

function sanitize($input) {
    return htmlspecialchars(trim($input ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatTanggal($date) {
    if (!$date || $date === '0000-00-00') return '-';
    $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $d = date_create($date);
    if (!$d) return $date;
    return date_format($d, 'j') . ' ' . $months[(int)date_format($d, 'n')] . ' ' . date_format($d, 'Y');
}

function statusBadge($status) {
    $map = [
        'belum'  => ['class' => 'badge-danger',  'label' => '⏳ Belum Dilacak'],
        'proses' => ['class' => 'badge-warning', 'label' => '🔍 Sedang Dilacak'],
        'selesai'=> ['class' => 'badge-success', 'label' => '✅ Sudah Dilacak'],
    ];
    $s = $map[$status] ?? $map['belum'];
    return '<span class="badge ' . $s['class'] . '">' . $s['label'] . '</span>';
}

function statusPekerjaanBadge($status) {
    $map = [
        'PNS'           => ['class' => 'badge-info',    'label' => '🏛️ PNS'],
        'Swasta'        => ['class' => 'badge-primary', 'label' => '🏢 Swasta'],
        'Wirausaha'     => ['class' => 'badge-warning', 'label' => '💼 Wirausaha'],
        'Tidak Bekerja' => ['class' => 'badge-danger',  'label' => '❌ Tidak Bekerja'],
        'Lainnya'       => ['class' => 'badge-secondary','label' => '🔖 Lainnya'],
    ];
    if (!$status) return '<span class="badge badge-secondary">Belum diisi</span>';
    $s = $map[$status] ?? ['class' => 'badge-secondary', 'label' => $status];
    return '<span class="badge ' . $s['class'] . '">' . $s['label'] . '</span>';
}
