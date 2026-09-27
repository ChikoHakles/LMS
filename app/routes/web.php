<?php

use App\Http\Controllers\Admin\MaterialController as AdminMaterialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Student\MaterialController as StudentMaterialController;
use App\Http\Controllers\Tutor\MaterialController as TutorMaterialController;
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
        Route::get('materials', [AdminMaterialController::class, 'index'])->name('materials.index');
    });

    Route::prefix('tutor')->name('tutor.')->middleware('role:tutor')->group(function () {
        Route::get('daily-plans', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Tasklist harian']))
            ->name('daily-plans.index');
        Route::get('materials/article/create', [TutorMaterialController::class, 'createArticle'])
            ->name('materials.article.create');
        Route::post('materials', [TutorMaterialController::class, 'store'])->name('materials.store');
        Route::put('materials/{material}', [TutorMaterialController::class, 'update'])->name('materials.update');
        Route::patch('materials/{material}/publish', [TutorMaterialController::class, 'publish'])->name('materials.publish');
        Route::get('materials/{material}/edit', [TutorMaterialController::class, 'edit'])->name('materials.edit');
        Route::get('materials/{material}', [TutorMaterialController::class, 'show'])->name('materials.show');
        Route::get('materials/video/create', [TutorMaterialController::class, 'createVideo'])
            ->name('materials.video.create');
        Route::post('materials/video', [TutorMaterialController::class, 'storeVideo'])->name('materials.video.store');
        Route::get('materials/{material}/video/edit', [TutorMaterialController::class, 'editVideo'])->name('materials.video.edit');
        Route::put('materials/{material}/video', [TutorMaterialController::class, 'updateVideo'])->name('materials.video.update');
        Route::patch('materials/{material}/video/publish', [TutorMaterialController::class, 'publishVideo'])->name('materials.video.publish');
        Route::get('materials/quiz/create', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Buat Materi: Kuis']))
            ->name('materials.quiz.create');
        Route::get('materials', [TutorMaterialController::class, 'index'])->name('materials.index');
        Route::get('essays', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Penilaian esai']))
            ->name('essays.index');
        Route::get('students/progress', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Progres murid']))
            ->name('students.progress');
    });

    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::get('daily-rhythm', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Ritme hari ini']))
            ->name('daily-rhythm');
        Route::get('materials', [StudentMaterialController::class, 'index'])->name('materials.index');
        Route::get('materials/{material}', [StudentMaterialController::class, 'show'])->name('materials.show');
        Route::get('quizzes', fn () => Inertia::render('WorkspacePlaceholder', ['title' => 'Kuis dan latihan']))
            ->name('quizzes.index');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
