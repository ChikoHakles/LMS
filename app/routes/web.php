<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'account.active', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');
        Route::put('users/{user}/access', [UserController::class, 'resetAccess'])->name('users.access.reset');

        Route::get('classes', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Kelas']))
            ->name('classes.index');
        Route::get('materials', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Pustaka materi']))
            ->name('materials.index');
    });

    Route::prefix('tutor')->name('tutor.')->middleware('role:tutor')->group(function () {
        Route::get('daily-plans', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Tasklist harian']))
            ->name('daily-plans.index');
        Route::get('materials/article/create', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Buat Materi: Artikel']))
            ->name('materials.article.create');
        Route::get('materials/video/create', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Buat Materi: Video']))
            ->name('materials.video.create');
        Route::get('materials/quiz/create', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Buat Materi: Kuis']))
            ->name('materials.quiz.create');
        Route::get('materials', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Pustaka materi']))
            ->name('materials.index');
        Route::get('essays', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Penilaian esai']))
            ->name('essays.index');
        Route::get('students/progress', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Progres murid']))
            ->name('students.progress');
    });

    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::get('daily-rhythm', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Ritme hari ini']))
            ->name('daily-rhythm');
        Route::get('materials', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Materi']))
            ->name('materials.index');
        Route::get('quizzes', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Kuis dan latihan']))
            ->name('quizzes.index');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
