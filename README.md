# Snack.Snackin — Laravel + MySQL (frontend + backend)

Sistem pemesanan dan kasir UMKM Dapur Snack Snackin. Pengguna memesan tanpa login; hanya Pemilik yang login (sekaligus kasir/admin).

## Pasang ke proyek Laravel 11/12/13
1. `composer create-project laravel/laravel snack-snackin` lalu salin **seluruh isi folder ini** ke root proyek (gabungkan folder yang sama, timpa `routes/web.php`). Jangan lupa `public/vendor/leaflet`.
2. Di `.env`:
   ```
   APP_TIMEZONE=Asia/Jakarta
   DB_CONNECTION=mysql
   DB_DATABASE=snack_snackin
   DB_USERNAME=root
   DB_PASSWORD=
   SESSION_LIFETIME=720
   OWNER_NAME=Pemilik
   OWNER_EMAIL=emailanda@contoh.com
   OWNER_PASSWORD=isi-sandi-kuat-min-8
   ```
   (`config/app.php` memakai `APP_TIMEZONE` pada Laravel 11/12/13; pada versi lama set `'timezone' => 'Asia/Jakarta'`.)
3. Buat database kosong `snack_snackin`, lalu:
   ```
   php artisan migrate --seed
   php artisan serve
   ```
   Seeder membuat akun Pemilik dari `.env` dan daftar menu awal. Akun lain dibuat Pemilik lewat **Kelola Akun** (tidak ada pendaftaran publik).
   `php artisan db:seed --class=DemoSeeder` (opsional) mengisi pesanan contoh untuk mencoba Laporan — jangan dipakai di toko sungguhan.
4. Buka `/admin/login`, masuk, lalu isi **Pengaturan**: nomor WhatsApp, alamat dapur + titik peta, jam buka, QRIS, estimasi waktu.
   Nomor WA (`0812-0000-0000`) dan koordinat dapur masih contoh — **ganti dengan data asli**.

## Perintah berguna
- `php artisan snackin:kosongkan` — hapus semua pesanan (mis. setelah presentasi); menu, akun, pengaturan tetap. Tombol yang sama ada di **Pengaturan → Kosongkan data pesanan**.
- `php artisan snackin:sandi email@contoh.com` — atur ulang kata sandi Pemilik yang lupa (atau buat akunnya).
- Hosting: baca **DEPLOY.md**.

## Struktur
- `app/Domain/*` — logika murni tanpa Laravel (alur status, harga, validasi, jarak, jam buka) + `Services/*`. Diuji dengan `tests/Unit`.
- `app/Infrastructure/EloquentStore.php` — penyimpanan ke MySQL (DB facade). `tests/Support/PdoStore.php` — versi SQLite untuk tes.
- `app/Http/Controllers/{Api,Admin}`, `Middleware/{EnsureOwner,SecurityHeaders}`, `routes/web.php` — lapisan HTTP.
- `database/migrations`, `database/seeders`, `resources/views` (Blade), `public/js/app.js`, `public/css`.
- `tests/server/router.php` — server uji (PHP + SQLite) yang meniru API tanpa Laravel; hanya untuk pengujian.

## Keamanan yang sudah dipasang
Harga dihitung ulang di server dari database; token rahasia per pesanan (disimpan di perangkat pemesan); pencarian dari perangkat lain butuh kode + 4 angka akhir WhatsApp; pembatasan percobaan (pesan, login, pencarian); CSRF; `password` bcrypt; sesi diganti saat login; semua `/admin/*` dijaga `EnsureOwner` (API balas 401); unggahan hanya JPG/PNG ≤ 2 MB; header keamanan.

## Alur status
Belum dibayar → Diterima → Diproses → Siap diambil / Diantar → Selesai, atau Dibatalkan. QRIS mulai dari Belum dibayar (Pemilik memeriksa pembayaran), Tunai mulai dari Diterima. Pengguna hanya bisa membatalkan sebelum Diproses.

## Fitur pengguna
Pemilik bisa mengubah isi pesanan (selisih pembayaran QRIS dicatat dan harus dibereskan sebelum Selesai) dan mengabari pelanggan lewat WhatsApp. Pesanan berhasil (kode, QRIS, estimasi, WhatsApp, cetak struk), Transaksi hanya pesanan milik perangkat + cari dari perangkat lain, bayar QRIS ulang, Pesan lagi, nama/WA diingat, peta pilih lokasi, footer, halaman 404/419/500/503.
Pembaruan otomatis memakai polling (4–6 dtk), bukan websocket.

## Menjalankan tes
- Logika: `vendor/bin/phpunit tests/Unit` (setelah `composer require --dev phpunit/phpunit` bila belum ada).
- Server uji: `SNK_DB=/tmp/snk.sqlite php -S 127.0.0.1:8080 -t public tests/server/router.php` (login `owner@snackin.test` / `rahasia123`).
- Peta dan pencarian alamat memakai OpenStreetMap/Nominatim dan butuh internet.

## Catatan pengujian
Seluruh alur (pesan, status, kasir, menu, akun, pengaturan, unggah, login, pembatasan) diuji dengan Playwright terhadap **Laravel 12.73 dan 13.6 asli + SQLite**, dan terhadap server uji. Belum diuji di MySQL — jalankan `php artisan migrate --seed` pada MySQL Anda dan laporkan bila ada galat.
Pembatas permintaan memakai `RateLimiter::for` di `routes/web.php` (bukan `throttle:N,M`, yang memakai satu hitungan bersama untuk semua route).

## Tambahan v14
- **Batas waktu bayar QRIS** (Pengaturan → "Batas waktu bayar QRIS", default 30 menit, 0 = mati): hanya penanda "lewat batas bayar"; pesanan tidak dibatalkan otomatis.
- Layar sukses punya tombol **Kirim kode ke WhatsApp saya** (memakai nomor yang diisi saat memesan).
- Halaman Menu punya tombol **Lihat semua N menu** di tampilan HP.
- Panduan untuk pemilik toko: `PANDUAN_MITRA.md`.

## Mengganti logo
Letakkan file di `public/img/` dengan nama **`logo.svg`** (atau `logo.png` / `logo.webp` / `logo.jpg`). Logo otomatis tampil di halaman pelanggan, login admin, sidebar admin, dan halaman error, menggantikan tulisan "Snack.Snackin". Ikon tab browser memakai `public/img/favicon.png` (atau `.svg`/`.ico`) bila ada, jika tidak memakai logo, jika tidak ada juga memakai `public/favicon.svg` bawaan. Disarankan logo berlatar transparan, tinggi sekitar 80–100 px, lebar maksimal sekitar 400 px. Hapus file logo untuk kembali ke tulisan.
