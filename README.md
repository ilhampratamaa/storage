# StorageQR

Aplikasi manajemen gudang berbasis QR Code dengan PHP native, MySQL/MariaDB, dan Bootstrap.

## Instalasi

1. Salin folder proyek ini ke `htdocs` (XAMPP) atau folder web server Anda.
2. Ubah `BASE_URL` dan kredensial MySQL pada `config.php` bila diperlukan. Contoh: `define('BASE_URL', '/storage');`.
3. Buka `http://localhost/storage/install.php` sekali untuk membuat database serta tabel.
4. Buka `login.php`, lalu masuk dengan `admin` / `admin123`. Segera ubah/kelola akun ini untuk penggunaan nyata.

## Fitur

- Login session dan role admin/user.
- Master kategori dan lokasi bertingkat (gedung, ruang, rak).
- CRUD barang, pencarian dan filter, QR unik berdasarkan URL detail barang.
- Transaksi masuk/keluar dengan validasi stok dan riwayat.
- Pemindaian kamera memakai `html5-qrcode` dari CDN.

## Catatan penggunaan QR

QR berisi URL detail. Agar dapat dipindai dari smartphone lain, aplikasi harus diakses menggunakan alamat LAN komputer, misalnya `http://192.168.1.10/storage`, bukan `localhost`. Atur `BASE_URL` ke alamat tersebut.
