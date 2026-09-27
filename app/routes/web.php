<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('workspace/{page}', function (string $page) {
    $pages = [
        'admin-home' => 'Beranda Administrator',
        'admin-users' => 'Akun pengguna',
        'admin-classes' => 'Kelas',
        'admin-materials' => 'Pustaka materi',
        'tutor-home' => 'Beranda Tutor',
        'tutor-daily-plan' => 'Tasklist harian',
        'tutor-article-create' => 'Buat Materi: Artikel',
        'tutor-video-create' => 'Buat Materi: Video',
        'tutor-quiz-create' => 'Buat Materi: Kuis',
        'tutor-materials' => 'Pustaka materi',
        'tutor-essay-review' => 'Penilaian esai',
        'tutor-student-progress' => 'Progres murid',
        'student-home' => 'Beranda Siswa',
        'student-daily-rhythm' => 'Ritme hari ini',
        'student-materials' => 'Materi',
        'student-quizzes' => 'Kuis dan latihan',
    ];

    abort_unless(isset($pages[$page]), 404);

    return Inertia::render('WorkspacePlaceholder', ['title' => $pages[$page]]);
})->middleware(['auth', 'verified'])->name('workspace.placeholder');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
