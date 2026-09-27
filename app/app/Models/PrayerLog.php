<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrayerLog extends Model
{
    use HasFactory;

    public const PRAYERS = ['subuh', 'zuhur', 'asar', 'magrib', 'isya'];

    protected $fillable = ['prayer_date', 'prayer', 'completed'];

    protected function casts(): array
    {
        return ['completed' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
