# StorageQR

Aplikasi manajemen gudang berbasis QR Code dengan PHP native, MySQL/MariaDB, dan Bootstrap.

## Instalasi

1. Salin folder proyek ini ke `htdocs` (XAMPP) atau folder web server Anda.
2. Ubah kredensial MySQL pada `config.php` bila diperlukan. `BASE_URL` dikosongkan agar alamat mengikuti IP komputer di jaringan yang sedang dipakai.
3. Buka `http://IP-KOMPUTER/storage/install.php` sekali untuk membuat database serta tabel.
4. Buka `login.php`, lalu masuk dengan `admin` / `admin123`. Segera ubah/kelola akun ini untuk penggunaan nyata.

## Fitur

- Login session dan role admin/user.
- Master kategori dan lokasi bertingkat (gedung, ruang, rak).
- CRUD barang, pencarian dan filter, QR unik berdasarkan URL detail barang.
- Transaksi masuk/keluar dengan validasi stok dan riwayat.
- Pemindaian kamera memakai `html5-qrcode` dari CDN.

## Catatan penggunaan QR

QR berisi URL detail. Agar dapat dipindai dari smartphone lain dan kamera live diizinkan browser, aplikasi harus diakses melalui HTTPS menggunakan alamat LAN komputer, bukan `localhost`. Untuk sekadar membuka aplikasi, HTTP melalui IP komputer sudah cukup. Sertifikat HTTPS harus dipercaya pada smartphone.
