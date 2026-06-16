<?php

namespace Modules\CommunityEngine\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CommunityEngine\Enums\ReactionType;

#[Fillable([
    'user_id',
    'post_id',
    'reaction_type',
])]
class CommunityReaction extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'reaction_type' => ReactionType::class,
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class);
    }
}
