<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id', 'question_id', 'choice_id', 'response', 'score', 'reviewer_comment', 'graded_by', 'graded_at',
    ];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'graded_at' => 'datetime'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'question_id');
    }

    public function choice(): BelongsTo
    {
        return $this->belongsTo(QuizChoice::class, 'choice_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
