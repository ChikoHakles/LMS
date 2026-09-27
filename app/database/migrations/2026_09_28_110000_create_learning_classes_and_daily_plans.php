<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->timestamps();
            $table->unique(['tutor_id', 'name']);
        });

        Schema::create('class_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('learning_classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['class_id', 'student_id']);
            $table->index(['student_id', 'class_id']);
        });

        Schema::create('daily_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('learning_classes')->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->date('plan_date');
            $table->unsignedSmallInteger('target_minutes')->default(45);
            $table->timestamps();
            $table->unique(['class_id', 'plan_date']);
            $table->index(['tutor_id', 'plan_date']);
        });

        Schema::create('daily_plan_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20);
            $table->unsignedTinyInteger('position');
            $table->unsignedSmallInteger('target_minutes')->nullable();
            $table->timestamps();
            $table->unique(['daily_plan_id', 'type']);
            $table->unique(['daily_plan_id', 'position']);
            $table->index(['material_id', 'daily_plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_plan_materials');
        Schema::dropIfExists('daily_plans');
        Schema::dropIfExists('class_students');
        Schema::dropIfExists('learning_classes');
    }
};
