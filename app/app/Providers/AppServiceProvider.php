<?php

namespace App\Providers;

use App\Contracts\StudentMaterialAssignments;
use App\Services\NoStudentMaterialAssignments;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(StudentMaterialAssignments::class, NoStudentMaterialAssignments::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
