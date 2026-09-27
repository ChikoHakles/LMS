<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyPlan extends Model
{
    use HasFactory;

    protected $fillable = ['tutor_id', 'plan_date', 'target_minutes'];

    protected function casts(): array
    {
        return ['plan_date' => 'date'];
    }

    public function learningClass(): BelongsTo
    {
        return $this->belongsTo(LearningClass::class, 'class_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(DailyPlanMaterial::class)->orderBy('position');
    }
}
