# Ruang 1.0 — desain awal

Prototipe navigasi dan halaman yang mengembangkan **konsep dashboard v2** di `../../dashboard-concepts/`. Buka `index.html` di browser. Data contoh dan perubahan tersimpan di penyimpanan lokal browser.

## Peta halaman

| Peran | Halaman | Tujuan |
|---|---|---|
| Murid | Beranda | Agenda v2 dengan kartu **Ritme hari ini** menggantikan Sesi terdekat |
| Murid | Ritme hari ini | Checklist tetap: Subuh, Zuhur, Asar, Magrib, Isya; artikel, video, kuis yang ditugaskan; target dan durasi belajar |
| Murid | Materi belajar | Daftar materi yang bisa dibuka |
| Murid | Baca artikel, Tonton video, Kerjakan kuis | Halaman belajar yang mencatat durasi aktif masing-masing murid |
| Tutor | Beranda | Ringkasan kelas dan jalan cepat ke pengaturan ritme serta materi |
| Tutor | Tasklist harian | Pilih materi harian, target menit, kelas, dan tanggal; lima sholat selalu ada |
| Tutor | Buat Materi → Artikel | Penyunting blok untuk teks, judul bagian, daftar, dan kutipan; rancangan interaksi berbasis pola editor blok |
| Tutor | Buat Materi → Video | Tempel tautan YouTube, cek pratinjau embed, isi keterangan |
| Tutor | Buat Materi → Kuis | Tambah soal pilihan ganda dan esai, opsi jawaban, kunci, dan poin |
| Tutor | Pustaka materi | Lihat serta pilih konten untuk tasklist |
| Tutor | Penilaian esai | Antrean jawaban esai murid |
| Tutor | Progres murid | Ringkasan checklist dan durasi aktif per murid |

## Aturan rancangan

- Checklist sholat selalu memuat lima waktu; tutor mengatur materi dan target durasi, bukan menghapus sholat.
- Kartu Ritme pada beranda murid merangkum status sholat, materi, dan menit aktif; tautannya membuka checklist lengkap.
- Jam belajar bertambah ketika halaman artikel, video, atau kuis terlihat dan murid aktif. Hitungan berhenti saat tab tersembunyi atau tidak ada interaksi selama 60 detik. Ini perilaku prototipe, belum bukti perhatian atau penyelesaian materi.
- Penyelesaian artikel dan video ditandai murid. Kuis ditandai selesai saat dikirim. Jawaban esai masuk antrean penilaian tutor.
- Data contoh memakai satu murid dan satu kelas. Pemilihan kelas/tanggal adalah rancangan alur; prototipe menyimpan satu rencana harian aktif.
- Saat tutor mengganti tanggal rencana, progres checklist dan menit harian dimulai ulang. Riwayat pengumpulan kuis tetap tersimpan.
