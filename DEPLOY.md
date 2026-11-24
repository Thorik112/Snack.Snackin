# Panduan Hosting Snack.Snackin

Kebutuhan: **PHP 8.2 atau lebih baru**, **MySQL/MariaDB**, dan **HTTPS**. Itu tersedia di hampir semua hosting cPanel (shared hosting) di Indonesia.

## Pilihan hosting yang paling mudah
Cara termudah untuk pemula adalah **shared hosting cPanel + domain murah**. Ada phpMyAdmin, File Manager, dan SSL gratis (AutoSSL/Let's Encrypt), tanpa perlu mengurus server. Penyedia lokal yang umum dipakai antara lain Niagahoster, Rumahweb, DomaiNesia, IDwebhost, dan Hostinger. Harga paket dasar biasanya belasan sampai puluhan ribu rupiah per bulan, tetapi harganya berubah-ubah: **cek langsung di situsnya** sebelum membeli.
Saat memilih paket, pastikan: (1) bisa memilih PHP 8.2/8.3, (2) ada MySQL dan phpMyAdmin, (3) ada SSL gratis, (4) ada ruang disk minimal 1 GB. Terminal/SSH itu bagus tetapi **tidak wajib** (lihat Cara A).
Domain: pakai yang murah seperti `.my.id` atau `.com`, atau subdomain gratis bila ditawarkan paketnya.
Untuk **presentasi**, cadangan paling aman adalah tetap menjalankannya di laptop (Laragon: `php artisan serve`) kalau internet kampus bermasalah.

## Cara A — Tanpa SSH (paling umum)
Semua langkah berat dikerjakan di laptop Anda, hosting hanya menerima hasilnya.

**1. Siapkan di laptop (Laragon + Composer)**
1. Buat proyek Laravel 12 atau 13 baru (Laravel 13 butuh PHP 8.3+), salin isi zip ini ke dalamnya (lihat README.md), lalu `composer install --no-dev --optimize-autoloader`.
2. Buat database lokal `snack_snackin`, isi `.env` (`OWNER_EMAIL` dan `OWNER_PASSWORD` yang akan dipakai Pemilik), lalu:
   ```
   php artisan key:generate
   php artisan migrate --seed
   ```
   **Jangan** jalankan `DemoSeeder` (itu pesanan contoh).
3. Buka phpMyAdmin lokal → pilih database `snack_snackin` → **Export** (format SQL) → simpan `snack_snackin.sql`.

**2. Siapkan di cPanel**
1. **MySQL Databases**: buat database, buat user + kata sandi, lalu tambahkan user ke database dengan *All Privileges*. Catat nama database, user, dan sandi (biasanya berawalan nama akun cPanel).
2. **phpMyAdmin** → pilih database tadi → **Import** → pilih `snack_snackin.sql`.
3. **Select PHP Version**: pilih 8.2 atau 8.3, aktifkan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `xml`, `zip` bila belum aktif.
4. **SSL/TLS Status** (atau AutoSSL): aktifkan SSL untuk domain Anda.

**3. Unggah**
1. Zip seluruh proyek **tanpa** folder `node_modules` dan `.git`. Folder `vendor` ikut.
2. **File Manager** → unggah zip ke folder **di atas** `public_html`, misalnya `/home/akunanda/snackin/`, lalu *Extract*.
3. Isi folder `public` proyek harus menjadi isi folder web situs Anda:
   - Jika bisa mengubah *Document Root* (menu **Domains**): arahkan ke `/home/akunanda/snackin/public`. Selesai.
   - Jika tidak bisa: pindahkan **isi** `snackin/public/*` ke `public_html/`, lalu edit `public_html/index.php` dua baris ini:
     ```php
     require __DIR__.'/../snackin/vendor/autoload.php';
     $app = require_once __DIR__.'/../snackin/bootstrap/app.php';
     ```
     (juga baris `maintenance.php` bila ada, ubah jalurnya dengan cara yang sama).
4. Edit `.env` di folder `snackin` (File Manager → Edit):
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domainanda.my.id
   APP_TIMEZONE=Asia/Jakarta
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_DATABASE=nama_database_cpanel
   DB_USERNAME=user_database_cpanel
   DB_PASSWORD=sandi_database
   SESSION_LIFETIME=720
   SESSION_SECURE_COOKIE=true
   ```
   (`SESSION_SECURE_COOKIE=true` hanya setelah HTTPS aktif. Bila memakai Cloudflare Tunnel atau CDN, tambahkan juga `SNK_TRUSTED_PROXIES=*`.)
5. Izin folder `storage` dan `bootstrap/cache`: 775 (klik kanan → *Change Permissions*), termasuk isinya.
6. Buka `https://domainanda.my.id` dan `/admin/login`.

> Jangan jalankan `php artisan route:cache`: rute aplikasi ini memuat kode pendukung (pembatas permintaan) yang tidak ikut ter-cache.
> `storage:link` tidak diperlukan: foto menu dan QRIS disimpan langsung di `public/storage`.

## Cara B — Ada SSH/Terminal
Unggah proyek, lalu di terminal: `composer install --no-dev --optimize-autoloader`, isi `.env`, `php artisan key:generate`, `php artisan migrate --seed --force`. Lanjut langkah 3.3–3.6 di atas.

## Daftar uji setelah online (kerjakan berurutan)
1. Buka situs dari **ponsel** (data seluler, bukan Wi-Fi yang sama): menu tampil, foto muncul.
2. Pesan satu **Tunai Ambil** → layar "Pesanan berhasil", kode muncul.
3. Login `/admin/login` → pesanan tadi muncul di Pesanan Masuk (maks. ±5 detik) → proses sampai Selesai.
4. Unggah **QRIS** di Pengaturan, pesan **QRIS Antar** dari ponsel: pilih lokasi di peta, cari alamat (butuh internet), bayar, lalu tandai lunas dari admin.
5. Ubah isi pesanan QRIS yang sudah lunas → lihat selisih → tandai beres.
6. Cetak struk (Chrome/Edge → printer atau "Simpan PDF").
7. Buka dari perangkat lain: menu **Transaksi** → cari dengan kode + 4 angka akhir WhatsApp.
8. Coba salah sandi 6 kali → harus muncul "terlalu banyak percobaan".
9. Buka alamat acak (`/abc`) → halaman 404 yang ramah.

## Setelah presentasi: menyerahkan ke mitra
1. **Pengaturan → Kosongkan data pesanan** (ketik `HAPUS`). Semua pesanan/laporan hilang dan nomor mulai SNK-001. (Bila punya terminal: `php artisan snackin:kosongkan`.)
2. **Kelola Menu**: ubah/hapus menu contoh, isi harga dan foto asli.
3. **Pengaturan**: isi nomor WhatsApp asli, alamat dapur + titik peta, jam buka, ongkir, QRIS asli, estimasi waktu.
4. **Kelola Akun**: buat akun Pemilik mitra, masuk dengan akun itu, lalu **nonaktifkan atau hapus akun Anda** dan ganti sandi.
5. Serahkan akses hosting/domain (atau pindahkan kepemilikannya) beserta cara membuat cadangan.
6. Lupa sandi dan tidak ada akun lain: `php artisan snackin:sandi email@contoh.com`. Tanpa terminal, buka phpMyAdmin → tabel `users` → ubah kolom `password` dengan hash bcrypt yang dibuat di laptop (`php artisan tinker` → `Hash::make('sandibaru')`).

## Cadangan
Seminggu sekali: phpMyAdmin → **Export** database, dan unduh folder `public/storage` (foto menu, QRIS). Simpan di Google Drive. Banyak hosting juga punya cadangan otomatis, tetapi jangan hanya bergantung padanya.
