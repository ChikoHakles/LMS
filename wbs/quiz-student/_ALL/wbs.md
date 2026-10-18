# Work Breakdown Structure — RUANG

> **6 tasks** across **1 features**, **~32.0h** total (~4.0 dev-days).

## Alur kuis siswa dari penugasan sampai nilai  ·  ~32.0h

Sambungkan menu Kuis & latihan ke penugasan nyata, pengerjaan, dan hasil; pertahankan alur pembuatan serta penilaian tutor yang sudah berjalan.

**Traceability:** design/README.md: Buat Materi Kuis, Kerjakan kuis, Penilaian esai, Ritme hari ini; inspeksi kode 2026-10-02

### [BE] Sajikan daftar kuis siswa per penugasan dan nilai

**Type:** backend  ·  **Estimate:** 7h  ·  **Traceability:** QUIZ-05

TL;DR: Ganti route placeholder student.quizzes.index dengan QuizController::index. Context: siswa membutuhkan daftar kuis yang diberikan tutor beserta status dan nilai setiap penugasan. What to build: Query daily_plan_materials bertipe quiz yang kelasnya diikuti siswa dan materinya terbit; hubungkan quiz_attempts berdasarkan daily_plan_material_id dan student_id, bukan hanya material_id. Kembalikan judul, ringkasan, nama kelas, tanggal, jumlah soal/poin, status pengerjaan, nilai dan URL show berisi assignment. Terima filter material hanya bila milik penugasan siswa; paginasi terurut tanggal. Files: routes/web.php; app/Http/Controllers/Student/QuizController.php. Acceptance: siswa hanya melihat penugasan kelasnya; satu materi pada dua tanggal muncul dua kali dengan hasil berbeda; draft dan kelas lain tidak bocor; kuis masa depan terlihat tetapi belum dapat dikirim. Technical notes: ikuti pola MaterialPolicy dan DailyPlanStudentMaterialAssignments; jangan kirim kunci jawaban. Out of scope: mengubah editor tutor.

### [BE] Unit Test: Sajikan daftar kuis siswa per penugasan dan nilai

**Type:** unit-test  ·  **Estimate:** 3.5h  ·  **Traceability:** QUIZ-05  ·  **Depends on:** RUANG-2

Write unit tests for the work delivered in **Sajikan daftar kuis siswa per penugasan dan nilai**.

This is a separate task because aplikasi Laravel 12 has a coverage gate; the code change is not done until its tests pass that gate.

**Cover at minimum:**
- Daftar hanya memuat penugasan kuis terbit milik kelas siswa
- Hasil per penugasan ditampilkan tanpa kunci
- Siswa tidak bisa melihat kuis kelas lain
- The golden path plus at least one failure case per branch the parent task introduced.

**Definition of done:** `php artisan test --compact --filter=Quiz` is green and the new/changed code meets otorisasi, penugasan, pengiriman sekali, penilaian, dan hasil per penugasan. Test assertions reflect the parent task's acceptance criteria, not just line execution.

### [FE] Bangun halaman Kuis & latihan siswa yang terhubung ke backend

**Type:** frontend  ·  **Estimate:** 9h (1.1d)  ·  **Traceability:** QUIZ-06  ·  **Depends on:** RUANG-2

TL;DR: Buat resources/js/pages/student/Quizzes/Index.vue dari props QuizController::index. Context: menu siswa saat ini membuka WorkspacePlaceholder. What to build: kartu penugasan berisi tanggal, kelas, jumlah soal/poin, status Belum dikerjakan/Menunggu penilaian/Selesai dinilai, nilai akhir bila tersedia, serta tombol kerjakan atau lihat hasil. Tampilkan keadaan kosong dan navigasi halaman. Link ke student.quizzes.show harus membawa assignment. Files: resources/js/pages/student/Quizzes/Index.vue dan navigasi bila perlu. Acceptance: siswa dapat masuk dari menu, membuka kuis yang tepat dan kembali ke daftar; status penilaian esai jelas; tampilan ponsel dan keyboard layak. Technical notes: gunakan komponen Ruang dan token warna yang sudah ada. Out of scope: membuat ulang Quiz.vue.

### [FE] Hubungkan pintu masuk pustaka dan halaman hasil ke alur penugasan

**Type:** frontend  ·  **Estimate:** 6h  ·  **Traceability:** QUIZ-07  ·  **Depends on:** RUANG-4

TL;DR: Pastikan semua pintu masuk kuis siswa mempertahankan konteks penugasan. Context: MaterialCard dari pustaka membuka Quiz.vue tanpa assignment sehingga submit tidak menandai ritme selesai. What to change: untuk kuis pada MaterialCard, arahkan ke student.quizzes.index dengan filter material; pertahankan tautan assignment dari ritme. Di Quiz.vue, kembali ke daftar kuis dan tampilkan jawaban pilihan yang dipilih serta hasilnya tanpa membocorkan kunci sebelum submit. Files: resources/js/components/materials/MaterialCard.vue; resources/js/pages/student/Quiz.vue. Acceptance: siswa dari pustaka memilih penugasan yang benar; submit menandai progres; halaman hasil menampilkan pilihan siswa dan nilai sesuai status. Technical notes: gunakan props yang disediakan server dan jangan kirim is_correct pada props prapengiriman. Out of scope: mengubah skema data.

### [BE] Code Review: Alur kuis siswa dari penugasan sampai nilai

**Type:** code-review  ·  **Estimate:** 3h  ·  **Traceability:** design/README.md: Buat Materi Kuis, Kerjakan kuis, Penilaian esai, Ritme hari ini; inspeksi kode 2026-10-02  ·  **Depends on:** RUANG-2, RUANG-3

Review all BE work delivered for **Alur kuis siswa dari penugasan sampai nilai** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Sajikan daftar kuis siswa per penugasan dan nilai**  ·  _QUIZ-05_
  - Daftar hanya memuat penugasan kuis terbit milik kelas siswa
  - Hasil per penugasan ditampilkan tanpa kunci
  - Siswa tidak bisa melihat kuis kelas lain

Also covers **1 unit-test task(s)** for the above — verify the tests assert the acceptance criteria, not just execute lines.

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.

### [FE] Code Review: Alur kuis siswa dari penugasan sampai nilai

**Type:** code-review  ·  **Estimate:** 3.5h  ·  **Traceability:** design/README.md: Buat Materi Kuis, Kerjakan kuis, Penilaian esai, Ritme hari ini; inspeksi kode 2026-10-02  ·  **Depends on:** RUANG-4, RUANG-5

Review all FE work delivered for **Alur kuis siswa dari penugasan sampai nilai** before merge. Each item below is a task this review gates, with the requirements it must satisfy:

**Bangun halaman Kuis & latihan siswa yang terhubung ke backend**  ·  _QUIZ-06_
  - Menu tidak menuju placeholder
  - Tautan membawa ID penugasan
  - Nilai hanya tampil setelah selesai dinilai

**Hubungkan pintu masuk pustaka dan halaman hasil ke alur penugasan**  ·  _QUIZ-07_
  - Pustaka mengarahkan kuis ke daftar penugasan
  - Hasil menampilkan jawaban yang dipilih
  - Penugasan yang dikirim tercatat selesai di ritme

**For the review:** confirm every acceptance criterion above actually holds in the code; check adherence to project conventions and error handling; raise blocking comments for anything that would fail in production for plausible inputs.

