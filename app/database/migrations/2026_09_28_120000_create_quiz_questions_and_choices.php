<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->text('prompt');
            $table->decimal('points', 8, 2);
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->unique(['material_id', 'position']);
            $table->index(['material_id', 'type']);
        });

        Schema::create('quiz_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('quiz_questions')->cascadeOnDelete();
            $table->text('label');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->unique(['question_id', 'position']);
            $table->index(['question_id', 'is_correct']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_choices');
        Schema::dropIfExists('quiz_questions');
    }
};
