<?php
require_once __DIR__.'/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$rows = $pdo->query("SELECT t.jenis,t.jumlah,t.tanggal,t.created_at,b.nama,u.nama AS user_name FROM transaksi t JOIN barang b ON b.id=t.barang_id JOIN users u ON u.id=t.user_id ORDER BY t.created_at DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
$activities = array_map(static function ($row) {
    return [
        'nama' => $row['nama'],
        'jenis' => $row['jenis'],
        'jumlah' => (int)$row['jumlah'],
        'user' => $row['user_name'],
        'tanggal' => $row['tanggal'],
        'waktu' => date('d-m-Y H:i:s', strtotime($row['created_at'])),
    ];
}, $rows);
echo json_encode(['activities' => $activities], JSON_UNESCAPED_UNICODE);
