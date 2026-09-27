<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialCompletion extends Model
{
    use HasFactory;

    protected $fillable = ['daily_plan_material_id', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function planMaterial(): BelongsTo
    {
        return $this->belongsTo(DailyPlanMaterial::class, 'daily_plan_material_id');
    }
}
