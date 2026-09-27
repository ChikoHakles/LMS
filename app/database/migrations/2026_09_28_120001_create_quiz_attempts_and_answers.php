<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('awaiting_review')->index();
            $table->timestamp('submitted_at');
            $table->decimal('score', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['material_id', 'student_id']);
            $table->index(['student_id', 'submitted_at']);
        });

        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('quiz_questions')->restrictOnDelete();
            $table->foreignId('choice_id')->nullable()->constrained('quiz_choices')->restrictOnDelete();
            $table->text('response')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->text('reviewer_comment')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
            $table->index(['question_id', 'graded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_attempts');
    }
};
