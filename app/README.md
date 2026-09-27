# Ruang 1.0

Fondasi aplikasi dari **Laravel Vue starter kit resmi**, dengan PHP 8.2/Laravel 12, Vue 3, Inertia, Vite, dan MySQL. Implementasi modul Ruang mengikuti backlog di `../wbs/_ALL/tasks.csv`; tampilan acuan ada di `../design/index.html`.

## Menjalankan dengan XAMPP

1. Jalankan **Apache** dan **MySQL** dari XAMPP Control Panel. XAMPP pada mesin ini berada di `C:\Program Files\xampp`; MySQL perlu hak tulis atas direktori data XAMPP.
2. Buat database baru tanpa mengubah database yang sudah ada:

   ```sql
   CREATE DATABASE IF NOT EXISTS ruang_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Salin `.env.example` ke `.env` jika belum ada. Isi `DB_USERNAME` dan `DB_PASSWORD` sesuai akun MySQL lokal, lalu jalankan `php artisan key:generate` dan `php artisan migrate` dari direktori `app`.
4. Jalankan `npm install` dan `npm run build`. Untuk pengembangan, `npm run dev` menyalakan Vite.
5. Arahkan Apache ke folder **`app/public`**, bukan ke akar proyek. Contoh VirtualHost ada di `docs/xampp-vhost.conf.example`; sesuaikan nama host dan jalurnya, lalu aktifkan `mod_rewrite` dan `AllowOverride All`. Alternatif tanpa VirtualHost: `php artisan serve`.

Perintah `php`, `composer`, dan `laravel` tersedia melalui launcher di `C:\Users\hakle\.local\bin` yang sudah berada di PATH. Launcher memakai PHP XAMPP. Tidak ada nilai variabel lingkungan sistem yang diubah oleh penyiapan ini.

## Struktur awal

- `resources/css/app.css`: seluruh token warna global, termasuk palet Ruang v2 dan pemetaan semantik komponen.
- `resources/js/components` dan `resources/js/pages`: komponen dan halaman Vue dari starter kit.
- `.env.example`: contoh koneksi MySQL, zona waktu Asia/Jakarta, dan nama aplikasi. `.env` lokal tidak masuk Git.
- `tests`: pengujian bawaan starter kit.

## Pemeriksaan fondasi

```text
npm run build
php artisan test --compact
```

Migrasi MySQL belum dijalankan pada mesin ini karena proses MariaDB XAMPP tidak memiliki izin tulis pada `C:\Program Files\xampp\mysql\data` saat dijalankan dari sesi ini. Jalankan MySQL lewat XAMPP Control Panel dengan izin yang sesuai, lalu buat database dan jalankan migrasi.
