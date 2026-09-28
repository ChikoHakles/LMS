<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySessionInterval extends Model
{
    protected $fillable = ['study_date', 'sequence', 'seconds'];

    protected function casts(): array
    {
        return ['study_date' => 'date', 'sequence' => 'integer', 'seconds' => 'integer'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(StudySession::class, 'study_session_id');
    }
}
