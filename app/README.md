# Ruang 1.0

Fondasi aplikasi Laravel 12, PHP 8.2, Vue 3, Inertia, dan Vite. Aplikasi terhubung ke MySQL/MariaDB. Petunjuk berikut menyiapkan lingkungan lokal XAMPP tanpa mengubah variabel lingkungan Windows.

## Menjalankan dengan XAMPP

### Prasyarat

- XAMPP dengan PHP 8.2 dan MariaDB 10.4, serta Apache dan MySQL/MariaDB aktif dari XAMPP Control Panel.
- Node.js dan npm untuk membuat aset frontend.
- Composer dan PHP CLI. Pada mesin ini launcher PHP XAMPP tersedia melalui PATH; di mesin lain, gunakan PHP CLI milik XAMPP.

Pastikan versi PHP CLI yang dipakai adalah 8.2 (`php -v`). Versi MariaDB dapat diperiksa dari XAMPP Shell dengan `mysql --version` atau di phpMyAdmin.

### Konfigurasi aplikasi

1. Dari XAMPP Control Panel, jalankan Apache dan MySQL. Jika MySQL gagal menyala, periksa log XAMPP dan izin akses direktori data sebelum melanjutkan.
2. Buat database aplikasi di phpMyAdmin atau XAMPP Shell:

   ```sql
   CREATE DATABASE IF NOT EXISTS ruang_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

   Jangan gunakan atau hapus database lain yang sudah ada.
3. Dari folder `app`, salin `.env.example` menjadi `.env` bila belum ada. Atur `DB_DATABASE=ruang_lms`, `DB_USERNAME`, dan `DB_PASSWORD` sesuai akun MariaDB lokal. Simpan kredensial hanya di `.env`; jangan commit file tersebut atau menaruh kata sandi pada konfigurasi Apache. File `.env` diabaikan Git.
4. Buat kunci aplikasi dan jalankan migrasi:

   ```powershell
   php artisan key:generate
   php artisan migrate
   ```
   Before running `php artisan db:seed`, set `RUANG_LOCAL_ADMIN_EMAIL` and `RUANG_LOCAL_ADMIN_PASSWORD` in `.env` to create the local administrator. Keep the password in local environment configuration; it is intentionally not stored in the repository. The seeder is restricted to local and testing environments.
5. Buat aset production:

   ```powershell
   npm install
   npm run build
   ```

### Apache VirtualHost

Contoh konfigurasi ada di [`docs/xampp-vhost.conf.example`](docs/xampp-vhost.conf.example). Salin atau gabungkan ke konfigurasi VirtualHost Apache XAMPP, lalu sesuaikan `ServerName` dan path checkout. `DocumentRoot` dan blok `<Directory>` harus menunjuk ke `app/public`, bukan ke folder proyek atau `app`.

Aktifkan `mod_rewrite` di `apache/conf/httpd.conf` (baris `LoadModule rewrite_module modules/mod_rewrite.so` tidak boleh dikomentari), dan pastikan konfigurasi `<Directory>` mengizinkan `AllowOverride All`. Restart Apache setelah mengubah konfigurasi. Tambahkan entri `127.0.0.1 ruang.test` ke berkas `hosts` Windows bila memakai contoh `ServerName ruang.test`.

Contoh tersebut mengandalkan `app/public/.htaccess` untuk meneruskan permintaan ke Laravel. Jangan menaruh `.env`, `vendor`, atau berkas aplikasi lain di bawah DocumentRoot.

Alternatif pengembangan tanpa VirtualHost adalah menjalankan `php artisan serve` dari folder `app` setelah konfigurasi database selesai.

## Struktur awal

- `resources/css/app.css`: token warna global dan pemetaan semantik Ruang.
- `resources/js/components` dan `resources/js/pages`: shell serta halaman Vue.
- `.env.example`: contoh konfigurasi lokal tanpa kata sandi.
- `tests`: pengujian Laravel.

## Pemeriksaan fondasi

```powershell
npm run build
php artisan test --compact
```

Migrasi memerlukan layanan MariaDB XAMPP yang berjalan dan akun lokal yang memiliki izin membuat/mengubah tabel pada `ruang_lms`.
