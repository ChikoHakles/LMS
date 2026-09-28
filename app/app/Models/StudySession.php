<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudySession extends Model
{
    protected $fillable = [
        'public_id',
        'user_id',
        'active_user_id',
        'daily_plan_id',
        'daily_plan_material_id',
        'material_id',
        'started_at',
        'last_seen_at',
        'ended_at',
        'seconds',
        'last_sequence',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'last_seen_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
            'seconds' => 'integer',
            'last_sequence' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(DailyPlan::class, 'daily_plan_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(DailyPlanMaterial::class, 'daily_plan_material_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function intervals(): HasMany
    {
        return $this->hasMany(StudySessionInterval::class);
    }
}
