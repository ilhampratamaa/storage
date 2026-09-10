<?php
require_once __DIR__ . '/config.php';
// Simpan sesi di proyek agar kompatibel dengan PHP built-in server dan XAMPP.
if (session_status() === PHP_SESSION_NONE) {
    session_save_path(__DIR__ . '/.sessions');
    session_start();
}
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) { die('Koneksi database gagal. Jalankan install.php dan cek config.php.'); }
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url($path = '') { return BASE_URL . '/' . ltrim($path, '/'); }
function public_url($path = '') {
    if (BASE_URL !== '') return url($path);
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path);
}
function qr_image_url($kode, $size = 300) {
    $target = public_url('detail.php?kode=' . rawurlencode($kode));
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . (int)$size . 'x' . (int)$size . '&format=png&data=' . rawurlencode($target);
}
function logged_in() { return isset($_SESSION['user']); }
function require_login() { if (!logged_in()) { header('Location: '.url('login.php')); exit; } }
function admin_only() { require_login(); if ($_SESSION['user']['role'] !== 'admin') { flash('danger', 'Akses hanya untuk admin.'); header('Location: '.url('index.php')); exit; } }
function flash($type, $message) { $_SESSION['flash'] = [$type, $message]; }
function show_flash() { if (!empty($_SESSION['flash'])) { [$t,$m]=$_SESSION['flash']; unset($_SESSION['flash']); echo '<div class="alert alert-'.e($t).' alert-dismissible fade show">'.e($m).'<button class="btn-close" data-bs-dismiss="alert"></button></div>'; } }
function is_post() { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function csrf() { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verify_csrf() { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) die('Permintaan tidak valid.'); }
function location_name($x) { return trim(implode(' › ', array_filter([$x['gedung'] ?? '', $x['ruang'] ?? '', $x['rak'] ?? '']))); }
