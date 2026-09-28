<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // MariaDB may use the old composite unique index to satisfy the
            // material foreign key, so keep a standalone index before replacing it.
            $table->index('material_id', 'quiz_attempts_material_id_index');
            $table->dropUnique('quiz_attempts_material_id_student_id_unique');
            $table->foreignId('daily_plan_material_id')->nullable()->after('student_id')
                ->constrained('daily_plan_materials')->nullOnDelete();
            $table->unique(
                ['material_id', 'student_id', 'daily_plan_material_id'],
                'quiz_attempts_plan_submission_unique',
            );
        });

        Schema::create('prayer_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('prayer_date');
            $table->string('prayer', 20);
            $table->boolean('completed')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'prayer_date', 'prayer']);
            $table->index(['user_id', 'prayer_date']);
        });

        Schema::create('material_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_plan_material_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->timestamps();
            $table->unique(['user_id', 'daily_plan_material_id']);
            $table->index(['daily_plan_material_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_completions');
        Schema::dropIfExists('prayer_logs');

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropUnique('quiz_attempts_plan_submission_unique');
            $table->dropForeign(['daily_plan_material_id']);
            $table->dropColumn('daily_plan_material_id');
            $table->unique(['material_id', 'student_id']);
            $table->dropIndex('quiz_attempts_material_id_index');
        });
    }
};
