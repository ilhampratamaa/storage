<?php
require_once __DIR__ . '/config.php';

// Simpan sesi di proyek agar kompatibel dengan PHP built-in server dan XAMPP.
if (session_status() === PHP_SESSION_NONE) {
    session_save_path(__DIR__ . '/.sessions');
    session_start();
}
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $hasStatus = $pdo->query("SHOW COLUMNS FROM barang LIKE 'status'")->fetch();
    if (!$hasStatus) {
        $pdo->exec("ALTER TABLE barang ADD status ENUM('Tersedia','Sedang Digunakan','Dipinjam','Dalam Perbaikan') NOT NULL DEFAULT 'Tersedia' AFTER kondisi");
    }
    $hasUpdatedAt = $pdo->query("SHOW COLUMNS FROM barang LIKE 'updated_at'")->fetch();
    if (!$hasUpdatedAt) {
        $pdo->exec("ALTER TABLE barang ADD updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS barang_unit (id INT AUTO_INCREMENT PRIMARY KEY,barang_id INT NOT NULL,kode_unit VARCHAR(80) NOT NULL UNIQUE,nama_unit VARCHAR(150) NULL,lokasi_id INT NULL,spesifikasi TEXT NULL,kondisi ENUM('Baik','Rusak Ringan','Rusak Berat') NOT NULL DEFAULT 'Baik',catatan TEXT NULL,status ENUM('tersedia','keluar','rusak') NOT NULL DEFAULT 'tersedia',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,CONSTRAINT fk_unit_barang FOREIGN KEY(barang_id) REFERENCES barang(id) ON DELETE CASCADE,CONSTRAINT fk_unit_lokasi FOREIGN KEY(lokasi_id) REFERENCES lokasi(id) ON DELETE SET NULL) ENGINE=InnoDB");
    foreach (["nama_unit VARCHAR(150) NULL AFTER kode_unit", "lokasi_id INT NULL AFTER nama_unit", "spesifikasi TEXT NULL AFTER lokasi_id", "kondisi ENUM('Baik','Rusak Ringan','Rusak Berat') NOT NULL DEFAULT 'Baik' AFTER spesifikasi", "catatan TEXT NULL AFTER kondisi"] as $column) {
        $columnName = strtok($column, ' ');
        if (!$pdo->query("SHOW COLUMNS FROM barang_unit LIKE '$columnName'")->fetch()) $pdo->exec("ALTER TABLE barang_unit ADD $column");
    }
    $legacyItems = $pdo->query('SELECT id,kode,stok FROM barang WHERE aktif=1')->fetchAll(PDO::FETCH_ASSOC);
    $unitInsert = $pdo->prepare('INSERT INTO barang_unit(barang_id,kode_unit) VALUES(?,?)');
    $unitCount = $pdo->prepare('SELECT COUNT(*) FROM barang_unit WHERE barang_id=?');
    foreach ($legacyItems as $item) {
        $unitCount->execute([$item['id']]);
        $existing = (int)$unitCount->fetchColumn();
        for ($number = $existing + 1; $number <= (int)$item['stok']; $number++) {
            $unitInsert->execute([$item['id'], $item['kode'].'-'.str_pad((string)$number, 3, '0', STR_PAD_LEFT)]);
        }
    }
} catch (PDOException $e) { die('Koneksi database gagal. Jalankan install.php dan cek config.php.'); }
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url($path = '') {
    if (BASE_URL !== '') return BASE_URL . '/' . ltrim($path, '/');
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/storage/index.php')), '/');
    return ($basePath === '.' ? '' : $basePath) . '/' . ltrim($path, '/');
}
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
function sync_barang_units($barangId, $kode, $targetStock) {
    global $pdo;
    $s = $pdo->prepare('SELECT COUNT(*) FROM barang_unit WHERE barang_id=?');
    $s->execute([$barangId]);
    $current = (int)$s->fetchColumn();
    if ($targetStock > $current) {
        $insert = $pdo->prepare('INSERT INTO barang_unit(barang_id,kode_unit) VALUES(?,?)');
        for ($number = $current + 1; $number <= $targetStock; $number++) {
            $insert->execute([$barangId, $kode.'-'.str_pad((string)$number, 3, '0', STR_PAD_LEFT)]);
        }
    } elseif ($targetStock < $current) {
        $remove = $pdo->prepare('SELECT id FROM barang_unit WHERE barang_id=? AND status="tersedia" ORDER BY id DESC LIMIT '.($current - $targetStock));
        $remove->execute([$barangId]);
        $ids = $remove->fetchAll(PDO::FETCH_COLUMN);
        if (count($ids) < $current - $targetStock) throw new Exception('Stok tidak dapat dikurangi karena sebagian unit sudah berstatus keluar atau rusak.');
        $delete = $pdo->prepare('DELETE FROM barang_unit WHERE id=?');
        foreach ($ids as $unitId) $delete->execute([$unitId]);
    }
}
