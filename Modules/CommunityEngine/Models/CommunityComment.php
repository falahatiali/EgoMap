<?php

namespace Modules\CommunityEngine\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CommunityEngine\Database\Factories\CommunityCommentFactory;

#[Fillable([
    'user_id',
    'post_id',
    'parent_id',
    'content',
    'is_anonymous',
    'likes_count',
])]
class CommunityComment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static string $factory = CommunityCommentFactory::class;

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'likes_count' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'post_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CommunityComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CommunityComment::class, 'parent_id')->latest();
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(CommunityCommentReaction::class, 'comment_id');
    }

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

    public function isRecent(): bool
    {
        return $this->created_at?->diffInHours(now()) < 1;
    }
}
