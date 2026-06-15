<?php

namespace Modules\VirtueEngine\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'virtue_routine_id', 'user_id', 'situation',
    'emotional_state', 'ai_encouragement', 'points_earned', 'logged_at',
])]
class VirtueSuccessLog extends Model
{
    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
            'points_earned' => 'integer',
        ];
    }

    public function routine(): BelongsTo
    {
        return $this->belongsTo(VirtueRoutine::class, 'virtue_routine_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
