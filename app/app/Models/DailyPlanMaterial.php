<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyPlanMaterial extends Model
{
    use HasFactory;

    protected $fillable = ['material_id', 'tutor_id', 'type', 'position', 'target_minutes'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(DailyPlan::class, 'daily_plan_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(MaterialCompletion::class);
    }
}
