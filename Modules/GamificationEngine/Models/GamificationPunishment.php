<?php

namespace Modules\GamificationEngine\Models;

use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\GamificationEngine\Enums\GamificationPunishmentDifficulty;
use Modules\GamificationEngine\Enums\GamificationPunishmentType;

#[Fillable([
    'slug',
    'title',
    'description',
    'type',
    'difficulty',
    'points',
    'coins',
    'estimated_minutes',
    'min_slip_severity',
    'sort_order',
    'is_active',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class GamificationPunishment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => GamificationPunishmentType::class,
            'difficulty' => GamificationPunishmentDifficulty::class,
            'points' => 'integer',
            'coins' => 'integer',
            'estimated_minutes' => 'integer',
            'min_slip_severity' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<GamificationUserPunishment, $this>
     */
    public function userPunishments(): HasMany
    {
        return $this->hasMany(GamificationUserPunishment::class);
    }
}
