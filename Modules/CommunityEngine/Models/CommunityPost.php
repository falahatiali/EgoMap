<?php

namespace Modules\CommunityEngine\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CommunityEngine\Database\Factories\CommunityPostFactory;
use Modules\CommunityEngine\Enums\PostStatus;

#[Fillable([
    'user_id',
    'content',
    'is_anonymous',
    'status',
    'likes_count',
    'comments_count',
    'views_count',
    'rejection_reason',
])]
class CommunityPost extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static string $factory = CommunityPostFactory::class;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'is_anonymous' => 'boolean',
            'likes_count' => 'integer',
            'comments_count' => 'integer',
            'views_count' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(CommunityReaction::class, 'post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommunityComment::class, 'post_id');
    }

    public function topLevelComments(): HasMany
    {
        return $this->hasMany(CommunityComment::class, 'post_id')
            ->whereNull('parent_id')
            ->latest();
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('status', PostStatus::Approved);
    }

    public function scopeForFeed(Builder $query): void
    {
        $query->approved()->whereNull('deleted_at')->latest();
    }

    /**
     * Display name: anonymous mask or real name.
     */
    public function displayName(): string
    {
        if ($this->is_anonymous) {
            return 'Anonymous';
        }

        return $this->author?->name ?? 'Unknown';
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->user_id === $userId;
    }
}
