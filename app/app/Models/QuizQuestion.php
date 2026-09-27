<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizQuestion extends Model
{
    use HasFactory;

    public const TYPE_CHOICE = 'choice';

    public const TYPE_ESSAY = 'essay';

    protected $fillable = ['type', 'prompt', 'points', 'position'];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(QuizChoice::class, 'question_id')->orderBy('position');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'question_id');
    }
}
