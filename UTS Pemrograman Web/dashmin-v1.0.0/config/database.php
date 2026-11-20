<?php
/**
 * CALIBRAMED SYSTEM - Database & Core Configuration
 * Koneksi PDO MySQL & Session Management
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = '127.0.0.1';
$db_name = 'calibramed_db';
$db_user = 'root';
$db_pass = '';
$db_charset = 'utf8mb4';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die("Koneksi Database CalibraMed Gagal: " . htmlspecialchars($e->getMessage()));
}

/**
 * Cek apakah user sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Dapatkan data user yang sedang login
 */
function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT u.*, h.name AS hospital_name, h.city AS hospital_city 
                           FROM users u 
                           LEFT JOIN hospitals h ON u.hospital_id = h.id 
                           WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Cek peran aktif (Mendukung Simulator Mode bagi Owner)
 */
function getActiveRole() {
    if (isset($_SESSION['preview_role']) && $_SESSION['actual_role'] === 'owner') {
        return $_SESSION['preview_role'];
    }
    return $_SESSION['role'] ?? null;
}

function isOwner() {
    return ($_SESSION['role'] ?? '') === 'owner';
}

function isTeknisi() {
    $r = getActiveRole();
    return $r === 'teknisi' || $r === 'owner';
}

function isKlien() {
    return getActiveRole() === 'klien';
}

/**
 * Sanitasi string input
 */
function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format tanggal Indonesia
 */
function formatIndo($dateStr) {
    if (!$dateStr || $dateStr === '0000-00-00') return '-';
    $months = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    $timestamp = strtotime($dateStr);
    $d = date('d', $timestamp);
    $m = $months[(int)date('m', $timestamp)];
    $y = date('Y', $timestamp);
    return "$d $m $y";
}
