<?php

namespace Modules\GamificationEngine\Models;

use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\GamificationEngine\Enums\GamificationRuleType;

/**
 * Configurable when-then rule: on event + conditions → apply effects to wallet.
 */
#[Fillable([
    'key',
    'name',
    'description',
    'event',
    'rule_type',
    'conditions',
    'effects',
    'max_per_day',
    'priority',
    'is_active',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class GamificationRule extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rule_type' => GamificationRuleType::class,
            'conditions' => 'array',
            'effects' => 'array',
            'max_per_day' => 'integer',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<GamificationTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(GamificationTransaction::class);
    }
}
