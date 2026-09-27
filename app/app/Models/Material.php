<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    use HasFactory;

    public const TYPE_ARTICLE = 'article';

    public const TYPE_VIDEO = 'video';

    public const TYPE_QUIZ = 'quiz';

    /** @var list<string> */
    public const TYPES = [self::TYPE_ARTICLE, self::TYPE_VIDEO, self::TYPE_QUIZ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'type',
        'title',
        'summary',
        'status',
        'published_at',
        'blocks',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'blocks' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function scopeOwnedBy(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->getKey());
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return $type && in_array($type, self::TYPES, true)
            ? $query->where('type', $type)
            : $query;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->published_at !== null;
    }
}
