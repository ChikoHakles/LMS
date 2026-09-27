# Work Breakdown Structure — RUANG

> **52 tasks** across **8 features**, **~420.0h** total (~52.5 dev-days).

## Fondasi VILT dan navigasi Ruang  ·  ~29.0h

Lanjutkan starter kit resmi menjadi kerangka Ruang yang responsif dan siap dijalankan di XAMPP.

**Traceability:** design/README.md: peta halaman dan konsep dashboard v2

### [FE] Bangun shell aplikasi berbasis komponen dan navigasi per peran

**Type:** frontend  ·  **Estimate:** 12h (1.5d)  ·  **Traceability:** design/README.md: peta halaman; dashboard-concepts/render.html: v2

Ganti tampilan bawaan pada resources/js/components/AppSidebar.vue, AppHeader.vue, AppLogo.vue, dan layouts/app/*.vue dengan shell Ruang v2. Pecah sidebar, header, kartu, badge, dan progress menjadi komponen reusable. Siapkan susunan menu admin, tutor, siswa tanpa mengandalkan kontrol menu sebagai keamanan; tautan diarahkan ke route bernama. Responsive sampai lebar ponsel.

### [FE] Audit token warna global dan komponen dasar

**Type:** frontend  ·  **Estimate:** 6h  ·  **Traceability:** Permintaan teknis: setiap warna variabel CSS global  ·  **Depends on:** RUANG-2

Audit resources/css/app.css, tailwind.config.js, dan komponen Vue yang digunakan oleh shell. Pertahankan palet Ruang v2 sebagai variabel CSS global; ganti warna literal baru atau bawaan yang terlihat pada layar Ruang dengan token semantik. Pastikan state fokus, hover, error, sukses, dan disabled menggunakan token.

### [BE] Dokumentasikan dan verifikasi alur XAMPP MySQL

**Type:** backend  ·  **Estimate:** 5h  ·  **Traceability:** Permintaan teknis: MySQL dan XAMPP

Tambahkan petunjuk ruang-1.0/app/README.md dan contoh konfigurasi Apache VirtualHost dengan DocumentRoot mengarah ke public/, AllowOverride All, serta mod_rewrite. Jelaskan pembuatan database ruang_lms melalui XAMPP, penyesuaian DB_USERNAME/DB_PASSWORD lokal, npm build, dan php artisan migrate. Jangan menaruh kata sandi di repo atau mengubah variabel lingkungan sistem.

### [BE] Code Review: Fondasi VILT dan navigasi Ruang

**Type:** code-review  ·  **Estimate:** 2.5h  ·  **Traceability:** design/README.md: peta halaman dan konsep dashboard v2  ·  **Depends on:** RUANG-4

Review all BE work delivered for **Fondasi VILT dan navigasi Ruang** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Dokumentasikan dan verifikasi alur XAMPP MySQL**  ·  _Permintaan teknis: MySQL dan XAMPP_
  - Petunjuk dapat diikuti dengan XAMPP PHP 8.2 dan MariaDB 10.4
  - Apache hanya mengekspos public/
  - Kredensial lokal tetap di .env yang diabaikan Git

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.

### [FE] Code Review: Fondasi VILT dan navigasi Ruang

**Type:** code-review  ·  **Estimate:** 3.5h  ·  **Traceability:** design/README.md: peta halaman dan konsep dashboard v2  ·  **Depends on:** RUANG-2, RUANG-3

Review all FE work delivered for **Fondasi VILT dan navigasi Ruang** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Bangun shell aplikasi berbasis komponen dan navigasi per peran**  ·  _design/README.md: peta halaman; dashboard-concepts/render.html: v2_
  - Menu tutor memuat Tasklist harian dan Buat Materi dengan anak Artikel, Video, Kuis
  - Menu siswa memuat Beranda, Ritme hari ini, dan Materi
  - Navigasi ponsel dapat dibuka dan ditutup dengan keyboard

**Audit token warna global dan komponen dasar**  ·  _Permintaan teknis: setiap warna variabel CSS global_
  - Tidak ada warna literal baru di halaman/komponen Ruang
  - Tampilan desktop dan ponsel konsisten dengan palet v2

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.


## Autentikasi dan RBAC admin tutor siswa  ·  ~55.0h

Gunakan autentikasi starter kit dan otorisasi sisi server untuk tiga peran.

**Traceability:** Permintaan teknis: RBAC admin, tutor, siswa

### [BE] Tambahkan peran pengguna dan pembatasan route

**Type:** backend  ·  **Estimate:** 12h (1.5d)  ·  **Traceability:** Permintaan teknis: RBAC admin, tutor, siswa

Tambah migrasi role pada users atau tabel role terpisah, enum/konstanta peran pada app/Models/User.php, middleware role pada app/Http/Middleware, alias di bootstrap/app.php, dan route grup di routes/web.php. Default registrasi publik harus siswa; admin/tutor hanya dibuat admin. Semua route dan aksi tulis memeriksa peran server-side.

### [BE] Unit Test: Tambahkan peran pengguna dan pembatasan route

**Type:** unit-test  ·  **Estimate:** 6h  ·  **Traceability:** Permintaan teknis: RBAC admin, tutor, siswa  ·  **Depends on:** RUANG-8

Write unit tests for the work delivered in **Tambahkan peran pengguna dan pembatasan route**.

This is a separate task because aplikasi Laravel 12 has a coverage gate; the code change is not done until its tests pass that gate.

**Cover at minimum:**
- Siswa tidak dapat mengakses URL tutor/admin secara langsung
- Tutor tidak dapat mengelola akun admin
- Registrasi publik tidak dapat memilih peran istimewa
- The golden path plus at least one failure case per branch the parent task introduced.

**Definition of done:** `php artisan test` is green and the new/changed code meets alur berhasil, validasi gagal, dan otorisasi lintas peran diuji. Test assertions reflect the parent task's acceptance criteria, not just line execution.

### [BE] Kelola akun tutor dan siswa oleh admin

**Type:** backend  ·  **Estimate:** 12h (1.5d)  ·  **Traceability:** Permintaan teknis: RBAC admin, tutor, siswa  ·  **Depends on:** RUANG-8

Buat controller dan FormRequest untuk daftar, tambah, ubah status, dan reset akses akun pada app/Http/Controllers/Admin serta route admin. Batasi perubahan peran berbahaya dan gunakan validasi email unik. Siapkan seeder akun admin lokal dengan kredensial dari konfigurasi lokal, bukan literal di repository.

### [BE] Unit Test: Kelola akun tutor dan siswa oleh admin

**Type:** unit-test  ·  **Estimate:** 6h  ·  **Traceability:** Permintaan teknis: RBAC admin, tutor, siswa  ·  **Depends on:** RUANG-10

Write unit tests for the work delivered in **Kelola akun tutor dan siswa oleh admin**.

This is a separate task because aplikasi Laravel 12 has a coverage gate; the code change is not done until its tests pass that gate.

**Cover at minimum:**
- Admin dapat membuat tutor dan siswa
- Tutor dan siswa tidak dapat memanggil aksi admin
- Tidak ada password seed di source
- The golden path plus at least one failure case per branch the parent task introduced.

**Definition of done:** `php artisan test` is green and the new/changed code meets alur berhasil, validasi gagal, dan otorisasi lintas peran diuji. Test assertions reflect the parent task's acceptance criteria, not just line execution.

### [FE] Bangun halaman admin untuk akun dan kelas

**Type:** frontend  ·  **Estimate:** 12h (1.5d)  ·  **Traceability:** Permintaan teknis: RBAC admin, tutor, siswa

Buat resources/js/pages/admin/Users.vue dan komponen form/tabel di resources/js/components/admin. Tampilkan daftar akun, peran, status, dan aksi pembuatan tutor/siswa. Gunakan validasi dan error server Inertia. Sidebar admin hanya menampilkan menu yang relevan.

### [BE] Code Review: Autentikasi dan RBAC admin tutor siswa

**Type:** code-review  ·  **Estimate:** 4.5h  ·  **Traceability:** Permintaan teknis: RBAC admin, tutor, siswa  ·  **Depends on:** RUANG-8, RUANG-9, RUANG-10, RUANG-11

Review all BE work delivered for **Autentikasi dan RBAC admin tutor siswa** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Tambahkan peran pengguna dan pembatasan route**  ·  _Permintaan teknis: RBAC admin, tutor, siswa_
  - Siswa tidak dapat mengakses URL tutor/admin secara langsung
  - Tutor tidak dapat mengelola akun admin
  - Registrasi publik tidak dapat memilih peran istimewa

**Kelola akun tutor dan siswa oleh admin**  ·  _Permintaan teknis: RBAC admin, tutor, siswa_
  - Admin dapat membuat tutor dan siswa
  - Tutor dan siswa tidak dapat memanggil aksi admin
  - Tidak ada password seed di source

Also covers **2 unit-test task(s)** for the above — verify the tests assert the acceptance criteria, not just execute lines.

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.

### [FE] Code Review: Autentikasi dan RBAC admin tutor siswa

**Type:** code-review  ·  **Estimate:** 2.5h  ·  **Traceability:** Permintaan teknis: RBAC admin, tutor, siswa  ·  **Depends on:** RUANG-12

Review all FE work delivered for **Autentikasi dan RBAC admin tutor siswa** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Bangun halaman admin untuk akun dan kelas**  ·  _Permintaan teknis: RBAC admin, tutor, siswa_
  - Admin dapat mengelola akun melalui antarmuka
  - Validasi gagal terlihat pada field
  - Halaman responsif

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.


## Pustaka materi dan artikel  ·  ~65.0h

Tutor mengelola artikel berbasis blok dan memilihnya untuk ritme murid.

**Traceability:** design/README.md: Buat Materi Artikel dan Pustaka materi

### [BE] Model materi, status publikasi, dan hak akses tutor

**Type:** backend  ·  **Estimate:** 12h (1.5d)  ·  **Traceability:** design/README.md: Pustaka materi

Buat migrasi materials dengan tipe artikel/video/kuis, title, summary, owner_id, status, published_at, timestamps; model, policy, FormRequest, dan controller pustaka. Tutor hanya mengubah materi miliknya; siswa hanya membaca materi terbit yang ditugaskan. Sediakan pencarian/filter tipe yang dipaginasi.

### [BE] Unit Test: Model materi, status publikasi, dan hak akses tutor

**Type:** unit-test  ·  **Estimate:** 6h  ·  **Traceability:** design/README.md: Pustaka materi  ·  **Depends on:** RUANG-16

Write unit tests for the work delivered in **Model materi, status publikasi, dan hak akses tutor**.

This is a separate task because aplikasi Laravel 12 has a coverage gate; the code change is not done until its tests pass that gate.

**Cover at minimum:**
- Materi draft tidak bisa dibaca siswa
- Tutor lain tidak bisa mengubah materi
- Pustaka bisa difilter per tipe
- The golden path plus at least one failure case per branch the parent task introduced.

**Definition of done:** `php artisan test` is green and the new/changed code meets alur berhasil, validasi gagal, dan otorisasi lintas peran diuji. Test assertions reflect the parent task's acceptance criteria, not just line execution.

### [BE] Simpan dan validasi blok artikel

**Type:** backend  ·  **Estimate:** 10h (1.2d)  ·  **Traceability:** design/README.md: Buat Materi Artikel  ·  **Depends on:** RUANG-16

Tambahkan penyimpanan isi artikel berupa JSON blok terstruktur pada materials atau tabel article_contents. Validasi jenis dan ukuran blok; sanitasi output teks/tautan saat render, serta larang HTML tak tepercaya. Implementasikan create/update/publish controller dan request tutor.

### [BE] Unit Test: Simpan dan validasi blok artikel

**Type:** unit-test  ·  **Estimate:** 5h  ·  **Traceability:** design/README.md: Buat Materi Artikel  ·  **Depends on:** RUANG-18

Write unit tests for the work delivered in **Simpan dan validasi blok artikel**.

This is a separate task because aplikasi Laravel 12 has a coverage gate; the code change is not done until its tests pass that gate.

**Cover at minimum:**
- Blok valid dapat disimpan dan dibaca ulang
- HTML/script tak tepercaya tidak dieksekusi
- Hanya tutor pemilik dapat menerbitkan
- The golden path plus at least one failure case per branch the parent task introduced.

**Definition of done:** `php artisan test` is green and the new/changed code meets alur berhasil, validasi gagal, dan otorisasi lintas peran diuji. Test assertions reflect the parent task's acceptance criteria, not just line execution.

### [FE] Bangun editor artikel berbasis blok

**Type:** frontend  ·  **Estimate:** 16h (2d)  ·  **Traceability:** design/README.md: Buat Materi Artikel dan Baca artikel

Buat resources/js/pages/tutor/materials/ArticleEditor.vue dengan integrasi editor blok, form judul/ringkasan, simpan draft, pratinjau, dan publish. Buat komponen renderer artikel siswa di resources/js/components/materials. Susunan konten mengikuti design/index.html#/buat-artikel dan #/baca/a1.

### [FE] Bangun halaman pustaka materi dan kartu konten

**Type:** frontend  ·  **Estimate:** 8h (1d)  ·  **Traceability:** design/README.md: Pustaka materi

Buat resources/js/pages/tutor/materials/Index.vue serta komponen kartu, filter, dan empty state. Tampilkan tipe, judul, ringkasan, status, dan aksi sesuai peran. Gunakan komponen global dan token warna Ruang.

### [BE] Code Review: Pustaka materi dan artikel

**Type:** code-review  ·  **Estimate:** 4.5h  ·  **Traceability:** design/README.md: Buat Materi Artikel dan Pustaka materi  ·  **Depends on:** RUANG-16, RUANG-17, RUANG-18, RUANG-19

Review all BE work delivered for **Pustaka materi dan artikel** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Model materi, status publikasi, dan hak akses tutor**  ·  _design/README.md: Pustaka materi_
  - Materi draft tidak bisa dibaca siswa
  - Tutor lain tidak bisa mengubah materi
  - Pustaka bisa difilter per tipe

**Simpan dan validasi blok artikel**  ·  _design/README.md: Buat Materi Artikel_
  - Blok valid dapat disimpan dan dibaca ulang
  - HTML/script tak tepercaya tidak dieksekusi
  - Hanya tutor pemilik dapat menerbitkan

Also covers **2 unit-test task(s)** for the above — verify the tests assert the acceptance criteria, not just execute lines.

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.

### [FE] Code Review: Pustaka materi dan artikel

**Type:** code-review  ·  **Estimate:** 3.5h  ·  **Traceability:** design/README.md: Buat Materi Artikel dan Pustaka materi  ·  **Depends on:** RUANG-20, RUANG-21

Review all FE work delivered for **Pustaka materi dan artikel** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Bangun editor artikel berbasis blok**  ·  _design/README.md: Buat Materi Artikel dan Baca artikel_
  - Tutor dapat menambah/mengubah blok dan menyimpan draft
  - Pratinjau sesuai renderer siswa
  - Siswa membaca artikel yang ditugaskan

**Bangun halaman pustaka materi dan kartu konten**  ·  _design/README.md: Pustaka materi_
  - Filter artikel/video/kuis bekerja
  - Status draft/terbit terlihat
  - Navigasi ke editor atau pratinjau tepat

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.


## Video YouTube tersemat  ·  ~29.5h

Tutor menautkan video YouTube dan murid menonton pada halaman belajar.

**Traceability:** design/README.md: Buat Materi Video dan Tonton video

### [BE] Validasi dan simpan identitas video YouTube

**Type:** backend  ·  **Estimate:** 8h (1d)  ·  **Traceability:** design/README.md: Buat Materi Video

Tambah field youtube_video_id/metadata pada materials dan request/controller tutor. Terima URL youtube.com/watch, youtu.be, dan Shorts dari host yang diizinkan; simpan ID valid, bukan HTML iframe mentah. Policy kepemilikan mengikuti materi.

### [BE] Unit Test: Validasi dan simpan identitas video YouTube

**Type:** unit-test  ·  **Estimate:** 4h  ·  **Traceability:** design/README.md: Buat Materi Video  ·  **Depends on:** RUANG-25

Write unit tests for the work delivered in **Validasi dan simpan identitas video YouTube**.

This is a separate task because aplikasi Laravel 12 has a coverage gate; the code change is not done until its tests pass that gate.

**Cover at minimum:**
- URL domain asing ditolak
- Embed dibangun dari video ID valid
- Tutor dapat mengubah tautan videonya
- The golden path plus at least one failure case per branch the parent task introduced.

**Definition of done:** `php artisan test` is green and the new/changed code meets alur berhasil, validasi gagal, dan otorisasi lintas peran diuji. Test assertions reflect the parent task's acceptance criteria, not just line execution.

### [FE] Bangun form tutor dan halaman video siswa

**Type:** frontend  ·  **Estimate:** 12h (1.5d)  ·  **Traceability:** design/README.md: Buat Materi Video dan Tonton video

Buat resources/js/pages/tutor/materials/VideoEditor.vue dan resources/js/pages/student/Video.vue. Form memberi pratinjau embed YouTube, judul, ringkasan, validasi dan status. Halaman siswa menampilkan player, konteks, dan aksi selesai; gunakan URL youtube-nocookie.com dari ID tervalidasi.

### [BE] Code Review: Video YouTube tersemat

**Type:** code-review  ·  **Estimate:** 3h  ·  **Traceability:** design/README.md: Buat Materi Video dan Tonton video  ·  **Depends on:** RUANG-25, RUANG-26

Review all BE work delivered for **Video YouTube tersemat** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Validasi dan simpan identitas video YouTube**  ·  _design/README.md: Buat Materi Video_
  - URL domain asing ditolak
  - Embed dibangun dari video ID valid
  - Tutor dapat mengubah tautan videonya

Also covers **1 unit-test task(s)** for the above — verify the tests assert the acceptance criteria, not just execute lines.

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.

### [FE] Code Review: Video YouTube tersemat

**Type:** code-review  ·  **Estimate:** 2.5h  ·  **Traceability:** design/README.md: Buat Materi Video dan Tonton video  ·  **Depends on:** RUANG-27

Review all FE work delivered for **Video YouTube tersemat** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Bangun form tutor dan halaman video siswa**  ·  _design/README.md: Buat Materi Video dan Tonton video_
  - Pratinjau bekerja untuk URL valid
  - URL salah mendapat pesan field
  - Video terbit dapat diputar dari tasklist siswa

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.


## Kuis pilihan ganda dan esai  ·  ~82.0h

Tutor menyusun soal, siswa mengerjakan, pilihan dinilai otomatis dan esai ditinjau tutor.

**Traceability:** design/README.md: Buat Materi Kuis, Kerjakan kuis, Penilaian esai

### [BE] Model soal pilihan dan esai beserta validasi

**Type:** backend  ·  **Estimate:** 14h (1.8d)  ·  **Traceability:** design/README.md: Buat Materi Kuis

Buat migrasi quizzes/questions/options atau struktur setara, model relasi, FormRequest, dan controller tutor. Pilihan memerlukan sekurangnya dua opsi dan tepat satu kunci; esai tidak memiliki kunci otomatis. Simpan poin dan urutan soal; jangan kirim kunci ke props siswa sebelum submit.

### [BE] Unit Test: Model soal pilihan dan esai beserta validasi

**Type:** unit-test  ·  **Estimate:** 7h  ·  **Traceability:** design/README.md: Buat Materi Kuis  ·  **Depends on:** RUANG-31

Write unit tests for the work delivered in **Model soal pilihan dan esai beserta validasi**.

This is a separate task because aplikasi Laravel 12 has a coverage gate; the code change is not done until its tests pass that gate.

**Cover at minimum:**
- Soal pilihan dan esai tersimpan berurutan
- Kunci tidak bocor ke halaman siswa
- Soal tidak valid ditolak
- The golden path plus at least one failure case per branch the parent task introduced.

**Definition of done:** `php artisan test` is green and the new/changed code meets alur berhasil, validasi gagal, dan otorisasi lintas peran diuji. Test assertions reflect the parent task's acceptance criteria, not just line execution.

### [BE] Pengumpulan kuis dan penilaian esai

**Type:** backend  ·  **Estimate:** 14h (1.8d)  ·  **Traceability:** design/README.md: Kerjakan kuis dan Penilaian esai  ·  **Depends on:** RUANG-31

Buat attempt, answer, score, dan status review pada migrasi/model/controller siswa serta tutor. Nilai pilihan ganda server-side memakai kunci tersimpan; esai masuk antrean tutor untuk diberi poin dan komentar. Cegah submit ganda tanpa kebijakan ulang yang jelas.

### [BE] Unit Test: Pengumpulan kuis dan penilaian esai

**Type:** unit-test  ·  **Estimate:** 7h  ·  **Traceability:** design/README.md: Kerjakan kuis dan Penilaian esai  ·  **Depends on:** RUANG-33

Write unit tests for the work delivered in **Pengumpulan kuis dan penilaian esai**.

This is a separate task because aplikasi Laravel 12 has a coverage gate; the code change is not done until its tests pass that gate.

**Cover at minimum:**
- Skor pilihan dihitung di server
- Esai menunggu tutor lalu skor final diperbarui
- Siswa tidak dapat mengirim jawaban sebagai siswa lain
- The golden path plus at least one failure case per branch the parent task introduced.

**Definition of done:** `php artisan test` is green and the new/changed code meets alur berhasil, validasi gagal, dan otorisasi lintas peran diuji. Test assertions reflect the parent task's acceptance criteria, not just line execution.

### [FE] Bangun penyusun kuis tutor

**Type:** frontend  ·  **Estimate:** 16h (2d)  ·  **Traceability:** design/README.md: Buat Materi Kuis

Buat resources/js/pages/tutor/materials/QuizEditor.vue dengan komponen QuestionEditor dan ChoiceEditor. Tutor dapat menambah, menyusun ulang, menghapus, dan mengubah jenis soal; isi opsi/kunci/poin untuk pilihan atau prompt esai. Tampilkan error validasi per soal.

### [FE] Bangun pengerjaan kuis siswa dan antrean esai tutor

**Type:** frontend  ·  **Estimate:** 16h (2d)  ·  **Traceability:** design/README.md: Kerjakan kuis dan Penilaian esai

Buat resources/js/pages/student/Quiz.vue serta resources/js/pages/tutor/EssayReview.vue. Siswa mengisi semua soal dan melihat konfirmasi pengiriman; tutor melihat esai, memberi skor/komentar, dan menandai selesai. Pisahkan komponen jawaban pilihan dan esai.

### [BE] Code Review: Kuis pilihan ganda dan esai

**Type:** code-review  ·  **Estimate:** 4.5h  ·  **Traceability:** design/README.md: Buat Materi Kuis, Kerjakan kuis, Penilaian esai  ·  **Depends on:** RUANG-31, RUANG-32, RUANG-33, RUANG-34

Review all BE work delivered for **Kuis pilihan ganda dan esai** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Model soal pilihan dan esai beserta validasi**  ·  _design/README.md: Buat Materi Kuis_
  - Soal pilihan dan esai tersimpan berurutan
  - Kunci tidak bocor ke halaman siswa
  - Soal tidak valid ditolak

**Pengumpulan kuis dan penilaian esai**  ·  _design/README.md: Kerjakan kuis dan Penilaian esai_
  - Skor pilihan dihitung di server
  - Esai menunggu tutor lalu skor final diperbarui
  - Siswa tidak dapat mengirim jawaban sebagai siswa lain

Also covers **2 unit-test task(s)** for the above — verify the tests assert the acceptance criteria, not just execute lines.

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.

### [FE] Code Review: Kuis pilihan ganda dan esai

**Type:** code-review  ·  **Estimate:** 3.5h  ·  **Traceability:** design/README.md: Buat Materi Kuis, Kerjakan kuis, Penilaian esai  ·  **Depends on:** RUANG-35, RUANG-36

Review all FE work delivered for **Kuis pilihan ganda dan esai** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Bangun penyusun kuis tutor**  ·  _design/README.md: Buat Materi Kuis_
  - Pilihan dan esai bisa dicampur dalam satu kuis
  - Kunci dipilih jelas
  - Draft kuis tersimpan dan dapat diedit

**Bangun pengerjaan kuis siswa dan antrean esai tutor**  ·  _design/README.md: Kerjakan kuis dan Penilaian esai_
  - Siswa dapat mengirim kuis sekali
  - Esai menunggu penilaian ditampilkan tutor
  - Hasil tidak menampilkan kunci sebelum submit

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.


## Tasklist tutor dan ritme harian siswa  ·  ~71.0h

Lima sholat tetap; tutor memilih artikel, video, kuis, dan target menit untuk tiap tanggal/kelas.

**Traceability:** design/README.md: Tasklist harian, Ritme hari ini

### [BE] Model rencana harian kelas dan penugasan materi

**Type:** backend  ·  **Estimate:** 14h (1.8d)  ·  **Traceability:** design/README.md: Tasklist harian

Buat daily_plans dan daily_plan_materials dengan class_id/date unik, slot artikel/video/kuis, urutan, target_minutes, tutor_id. Controller tutor memvalidasi jenis materi tiap slot dan kepemilikan kelas. Snapshot penugasan per tanggal agar perubahan rencana berikutnya tidak menghapus riwayat.

### [BE] Unit Test: Model rencana harian kelas dan penugasan materi

**Type:** unit-test  ·  **Estimate:** 7h  ·  **Traceability:** design/README.md: Tasklist harian  ·  **Depends on:** RUANG-40

Write unit tests for the work delivered in **Model rencana harian kelas dan penugasan materi**.

This is a separate task because aplikasi Laravel 12 has a coverage gate; the code change is not done until its tests pass that gate.

**Cover at minimum:**
- Satu rencana per kelas dan tanggal
- Slot artikel/video/kuis menerima tipe yang tepat
- Tutor kelas lain tidak dapat mengubah rencana
- The golden path plus at least one failure case per branch the parent task introduced.

**Definition of done:** `php artisan test` is green and the new/changed code meets alur berhasil, validasi gagal, dan otorisasi lintas peran diuji. Test assertions reflect the parent task's acceptance criteria, not just line execution.

### [BE] Simpan checklist sholat dan penyelesaian materi per siswa per tanggal

**Type:** backend  ·  **Estimate:** 12h (1.5d)  ·  **Traceability:** design/README.md: Ritme hari ini  ·  **Depends on:** RUANG-40

Buat prayer_logs dengan lima nama waktu yang tetap dan material_completions per user/plan/material. Endpoints siswa idempoten untuk menandai sholat serta selesai artikel/video; kuis selesai dari pengiriman attempt. Hitung ringkasan 5+3 tanpa menerima jumlah checklist dari klien.

### [BE] Unit Test: Simpan checklist sholat dan penyelesaian materi per siswa per tanggal

**Type:** unit-test  ·  **Estimate:** 6h  ·  **Traceability:** design/README.md: Ritme hari ini  ·  **Depends on:** RUANG-42

Write unit tests for the work delivered in **Simpan checklist sholat dan penyelesaian materi per siswa per tanggal**.

This is a separate task because aplikasi Laravel 12 has a coverage gate; the code change is not done until its tests pass that gate.

**Cover at minimum:**
- Checklist hari berbeda terpisah
- Tidak ada sholat keenam atau penghapusan slot wajib
- Kuis tidak bisa ditandai selesai tanpa submit
- The golden path plus at least one failure case per branch the parent task introduced.

**Definition of done:** `php artisan test` is green and the new/changed code meets alur berhasil, validasi gagal, dan otorisasi lintas peran diuji. Test assertions reflect the parent task's acceptance criteria, not just line execution.

### [FE] Bangun pengatur tasklist tutor

**Type:** frontend  ·  **Estimate:** 12h (1.5d)  ·  **Traceability:** design/README.md: Tasklist harian

Buat resources/js/pages/tutor/DailyPlan.vue dengan pemilih kelas/tanggal, slot artikel/video/kuis dari pustaka, target menit, pratinjau, dan simpan. Tampilkan lima sholat sebagai struktur tetap. Ikuti design/index.html#/tasklist.

### [FE] Bangun halaman Ritme hari ini siswa

**Type:** frontend  ·  **Estimate:** 12h (1.5d)  ·  **Traceability:** design/README.md: Ritme hari ini

Buat resources/js/pages/student/DailyRhythm.vue dan komponen PrayerTracker, MaterialChecklist, StudyTime. Tampilkan status 5 sholat, artikel/video/kuis, target dan durasi aktif; tiap kartu materi menuju halaman belajar. Ikuti design/index.html#/ritme.

### [BE] Code Review: Tasklist tutor dan ritme harian siswa

**Type:** code-review  ·  **Estimate:** 4.5h  ·  **Traceability:** design/README.md: Tasklist harian, Ritme hari ini  ·  **Depends on:** RUANG-40, RUANG-41, RUANG-42, RUANG-43

Review all BE work delivered for **Tasklist tutor dan ritme harian siswa** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Model rencana harian kelas dan penugasan materi**  ·  _design/README.md: Tasklist harian_
  - Satu rencana per kelas dan tanggal
  - Slot artikel/video/kuis menerima tipe yang tepat
  - Tutor kelas lain tidak dapat mengubah rencana

**Simpan checklist sholat dan penyelesaian materi per siswa per tanggal**  ·  _design/README.md: Ritme hari ini_
  - Checklist hari berbeda terpisah
  - Tidak ada sholat keenam atau penghapusan slot wajib
  - Kuis tidak bisa ditandai selesai tanpa submit

Also covers **2 unit-test task(s)** for the above — verify the tests assert the acceptance criteria, not just execute lines.

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.

### [FE] Code Review: Tasklist tutor dan ritme harian siswa

**Type:** code-review  ·  **Estimate:** 3.5h  ·  **Traceability:** design/README.md: Tasklist harian, Ritme hari ini  ·  **Depends on:** RUANG-44, RUANG-45

Review all FE work delivered for **Tasklist tutor dan ritme harian siswa** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Bangun pengatur tasklist tutor**  ·  _design/README.md: Tasklist harian_
  - Tutor dapat memilih konten tiap tipe dan target
  - Lima sholat tampak tanpa kontrol hapus
  - Error konflik/validasi dapat dipahami

**Bangun halaman Ritme hari ini siswa**  ·  _design/README.md: Ritme hari ini_
  - Checklist menunjukkan 5+3 langkah
  - Status tersimpan setelah reload
  - Tautan materi membuka halaman yang benar

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.


## Pelacakan waktu belajar aktif  ·  ~39.0h

Durasi siswa terakumulasi hanya di artikel, video, dan kuis saat sesi aktif.

**Traceability:** design/README.md: aturan rancangan jam belajar

### [BE] Catat interval belajar aktif dengan batas server

**Type:** backend  ·  **Estimate:** 14h (1.8d)  ·  **Traceability:** design/README.md: Jam belajar aktif

Buat study_sessions/heartbeats terhubung user, material, daily_plan, started_at, last_seen_at, seconds. Endpoint terautentikasi memverifikasi penugasan, membatasi delta heartbeat, mencegah overlap dan duplikasi, lalu mengagregasi menit per hari dan materi. Jangan mempercayai total detik dari browser.

### [BE] Unit Test: Catat interval belajar aktif dengan batas server

**Type:** unit-test  ·  **Estimate:** 7h  ·  **Traceability:** design/README.md: Jam belajar aktif  ·  **Depends on:** RUANG-49

Write unit tests for the work delivered in **Catat interval belajar aktif dengan batas server**.

This is a separate task because aplikasi Laravel 12 has a coverage gate; the code change is not done until its tests pass that gate.

**Cover at minimum:**
- Siswa tidak dapat menaikkan waktu dengan payload sewenang-wenang
- Tab ganda tidak menggandakan hitungan
- Agregasi memakai tanggal Asia/Jakarta
- The golden path plus at least one failure case per branch the parent task introduced.

**Definition of done:** `php artisan test` is green and the new/changed code meets alur berhasil, validasi gagal, dan otorisasi lintas peran diuji. Test assertions reflect the parent task's acceptance criteria, not just line execution.

### [FE] Buat composable pelacak waktu di halaman belajar

**Type:** frontend  ·  **Estimate:** 12h (1.5d)  ·  **Traceability:** design/README.md: Jam belajar aktif

Buat resources/js/composables/useStudyTimer.ts dan komponen StudyTimer.vue. Aktif hanya pada artikel/video/kuis siswa; pause saat document.hidden atau tanpa interaksi 60 detik; kirim heartbeat berkala dan saat meninggalkan halaman. Tampilkan waktu dari server, bukan akumulasi lokal sebagai sumber kebenaran.

### [BE] Code Review: Pelacakan waktu belajar aktif

**Type:** code-review  ·  **Estimate:** 3.5h  ·  **Traceability:** design/README.md: aturan rancangan jam belajar  ·  **Depends on:** RUANG-49, RUANG-50

Review all BE work delivered for **Pelacakan waktu belajar aktif** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Catat interval belajar aktif dengan batas server**  ·  _design/README.md: Jam belajar aktif_
  - Siswa tidak dapat menaikkan waktu dengan payload sewenang-wenang
  - Tab ganda tidak menggandakan hitungan
  - Agregasi memakai tanggal Asia/Jakarta

Also covers **1 unit-test task(s)** for the above — verify the tests assert the acceptance criteria, not just execute lines.

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.

### [FE] Code Review: Pelacakan waktu belajar aktif

**Type:** code-review  ·  **Estimate:** 2.5h  ·  **Traceability:** design/README.md: aturan rancangan jam belajar  ·  **Depends on:** RUANG-51

Review all FE work delivered for **Pelacakan waktu belajar aktif** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Buat composable pelacak waktu di halaman belajar**  ·  _design/README.md: Jam belajar aktif_
  - Timer berhenti saat tab disembunyikan
  - Timer tutor pada mode pratinjau tidak menambah durasi siswa
  - Reload mempertahankan total dari server

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.


## Dashboard v2 dan progres kelas  ·  ~49.5h

Bawa visual v2 ke halaman Inertia dengan ritme menggantikan sesi terdekat.

**Traceability:** design/README.md: Beranda dan Progres murid

### [BE] Sediakan ringkasan dashboard sesuai peran

**Type:** backend  ·  **Estimate:** 10h (1.2d)  ·  **Traceability:** design/README.md: Beranda dan Progres murid

Buat controller/query untuk ringkasan siswa (rencana hari ini, 5+3, menit, materi) dan tutor (kelas, pengumpulan, esai, progres siswa). Scoped query berdasarkan role dan kelas; data siswa lain tidak dikirim ke props siswa.

### [BE] Unit Test: Sediakan ringkasan dashboard sesuai peran

**Type:** unit-test  ·  **Estimate:** 5h  ·  **Traceability:** design/README.md: Beranda dan Progres murid  ·  **Depends on:** RUANG-55

Write unit tests for the work delivered in **Sediakan ringkasan dashboard sesuai peran**.

This is a separate task because aplikasi Laravel 12 has a coverage gate; the code change is not done until its tests pass that gate.

**Cover at minimum:**
- Ringkasan siswa hanya miliknya
- Ringkasan tutor hanya kelas yang diajar
- Tanggal mengikuti Asia/Jakarta
- The golden path plus at least one failure case per branch the parent task introduced.

**Definition of done:** `php artisan test` is green and the new/changed code meets alur berhasil, validasi gagal, dan otorisasi lintas peran diuji. Test assertions reflect the parent task's acceptance criteria, not just line execution.

### [FE] Bangun dashboard siswa v2 yang diperkuat

**Type:** frontend  ·  **Estimate:** 14h (1.8d)  ·  **Traceability:** design/README.md: Beranda murid

Ganti resources/js/pages/Dashboard.vue dengan dashboard siswa berbasis komponen: agenda, tenggat, Ritme hari ini, materi baru, catatan, kalender, prioritas, dan waktu. Kartu Ritme menggantikan Sesi terdekat dan menuju halaman DailyRhythm. Gunakan token warna global serta desain di design/index.html#/beranda.

### [FE] Bangun dashboard tutor dan progres murid

**Type:** frontend  ·  **Estimate:** 14h (1.8d)  ·  **Traceability:** design/README.md: Beranda tutor dan Progres murid

Buat resources/js/pages/tutor/Dashboard.vue dan StudentProgress.vue. Kartu agenda, tasklist, materi, pengumpulan, esai, dan progres menaut ke modul terkait. Tabel progres memuat sholat 5, materi 3, dan durasi aktif tiap siswa.

### [BE] Code Review: Dashboard v2 dan progres kelas

**Type:** code-review  ·  **Estimate:** 3h  ·  **Traceability:** design/README.md: Beranda dan Progres murid  ·  **Depends on:** RUANG-55, RUANG-56

Review all BE work delivered for **Dashboard v2 dan progres kelas** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Sediakan ringkasan dashboard sesuai peran**  ·  _design/README.md: Beranda dan Progres murid_
  - Ringkasan siswa hanya miliknya
  - Ringkasan tutor hanya kelas yang diajar
  - Tanggal mengikuti Asia/Jakarta

Also covers **1 unit-test task(s)** for the above — verify the tests assert the acceptance criteria, not just execute lines.

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.

### [FE] Code Review: Dashboard v2 dan progres kelas

**Type:** code-review  ·  **Estimate:** 3.5h  ·  **Traceability:** design/README.md: Beranda dan Progres murid  ·  **Depends on:** RUANG-57, RUANG-58

Review all FE work delivered for **Dashboard v2 dan progres kelas** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Bangun dashboard siswa v2 yang diperkuat**  ·  _design/README.md: Beranda murid_
  - Tidak ada kartu Sesi terdekat
  - Kartu Ritme menampilkan sholat, materi, dan menit
  - Layout responsif

**Bangun dashboard tutor dan progres murid**  ·  _design/README.md: Beranda tutor dan Progres murid_
  - Aksi dashboard membuka halaman yang sesuai
  - Progres per siswa hanya kelas tutor
  - Status kosong dan loading jelas

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.

