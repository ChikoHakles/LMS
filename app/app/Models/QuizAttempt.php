<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    use HasFactory;

    public const STATUS_AWAITING_REVIEW = 'awaiting_review';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = ['material_id', 'student_id', 'daily_plan_material_id', 'status', 'submitted_at', 'score'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'score' => 'decimal:2'];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'attempt_id');
    }

    public function dailyPlanMaterial(): BelongsTo
    {
        return $this->belongsTo(DailyPlanMaterial::class);
    }
}
