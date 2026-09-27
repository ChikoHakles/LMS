# Backlog implementasi Ruang 1.0

Sumber: desain `../design/`, kebutuhan VILT, RBAC admin/tutor/siswa, token warna global, MySQL, dan XAMPP.

File utama:

- `wbs.json` — definisi pekerjaan, estimasi, kriteria penerimaan, dan jejak kebutuhan.
- `_ALL/tasks.csv` — 52 task berurutan berdasarkan dependensi internal; status awal `not-done`.
- `_ALL/wbs.md` — versi baca untuk review.
- `_ALL/wbs_jira.csv` — impor JIRA.
- `_ALL/traceability.md` — jejak kebutuhan ke task.

`wbs-execute` dipakai sebagai aturan eksekusi: verifikasi per task, daftar kegagalan saat gagal, maksimum tiga percobaan, dan status `blocked` bila masih gagal. Lingkungan ini tidak menyediakan Workflow tool yang dibutuhkan skrip paralel skill, sehingga backlog belum dijalankan otomatis. Untuk eksekusi, mulai dari fitur **Fondasi VILT dan navigasi Ruang** kemudian **Autentikasi dan RBAC**, sebelum modul konten/ritme/dasbor. Jalankan fitur berikutnya setelah fondasi dan otorisasi terintegrasi; dependensi lintas fitur belum direpresentasikan oleh generator CSV.

Fondasi yang sudah disiapkan di luar 52 task: instalasi global Composer/Laravel CLI, scaffold Vue starter kit, konfigurasi awal MySQL/XAMPP, dan token palet v2. Task FOUNDATION tetap memuat audit/pengembangan lanjut agar hasilnya dapat ditinjau sebagai kode aplikasi.
