<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // A unique nullable key makes it impossible for one learner to have two open tabs counted at once.
            $table->foreignId('active_user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->foreignId('daily_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_plan_material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('seconds')->default(0);
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'daily_plan_id']);
            $table->index(['user_id', 'material_id']);
        });

        Schema::create('study_session_intervals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_session_id')->constrained()->cascadeOnDelete();
            $table->date('study_date');
            $table->unsignedInteger('sequence');
            $table->unsignedSmallInteger('seconds');
            $table->timestamps();
            $table->unique(['study_session_id', 'sequence', 'study_date'], 'study_session_interval_retry_unique');
            $table->index(['study_date', 'study_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_session_intervals');
        Schema::dropIfExists('study_sessions');
    }
};
