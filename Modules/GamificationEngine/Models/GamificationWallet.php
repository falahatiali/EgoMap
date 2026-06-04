<?php

namespace Modules\GamificationEngine\Models;

use App\Models\User;
use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Per-user or per-guest wallet storing points, coins, XP, streak, badges, perks.
 */
#[Fillable([
    'user_id',
    'guest_token',
    'points',
    'coins',
    'xp',
    'level',
    'streak_days',
    'badges',
    'perks',
    'metadata',
    'last_login_date',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class GamificationWallet extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'coins' => 'integer',
            'xp' => 'integer',
            'level' => 'integer',
            'streak_days' => 'integer',
            'badges' => 'array',
            'perks' => 'array',
            'metadata' => 'array',
            'last_login_date' => 'date',
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
     * @return HasMany<GamificationTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(GamificationTransaction::class);
    }

    /**
     * @return list<string>
     */
    public function badgeSlugs(): array
    {
        $badges = $this->badges ?? [];

        return is_array($badges) ? array_values(array_filter($badges, is_string(...))) : [];
    }

    public function hasBadge(string $slug): bool
    {
        return in_array($slug, $this->badgeSlugs(), true);
    }
}
