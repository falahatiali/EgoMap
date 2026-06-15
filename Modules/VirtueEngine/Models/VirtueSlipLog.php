<?php

namespace Modules\VirtueEngine\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'virtue_routine_id', 'user_id', 'what_happened',
    'ai_personalized_punishment', 'gamification_user_punishment_id',
    'punishment_completed', 'logged_at',
])]
class VirtueSlipLog extends Model
{
    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
            'punishment_completed' => 'boolean',
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
