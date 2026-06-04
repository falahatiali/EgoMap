<?php

namespace App\Models;

use App\Enums\NoContactStatus;
use App\Observers\AssignsUuidObserver;
use Database\Factories\NoContactProtocolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'guest_token',
    'duration_days',
    'status',
    'streak_started_at',
    'target_ends_at',
    'slip_count',
    'last_slip_at',
    'completed_at',
    'gamification_rewarded_at',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class NoContactProtocol extends Model
{
    /** @use HasFactory<NoContactProtocolFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => NoContactStatus::class,
            'duration_days' => 'integer',
            'slip_count' => 'integer',
            'streak_started_at' => 'datetime',
            'target_ends_at' => 'datetime',
            'last_slip_at' => 'datetime',
            'completed_at' => 'datetime',
            'gamification_rewarded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<GhostModeEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(GhostModeEvent::class);
    }

    public function isActive(): bool
    {
        return $this->status === NoContactStatus::Active;
    }
}
