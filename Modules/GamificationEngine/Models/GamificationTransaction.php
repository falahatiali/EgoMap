<?php

namespace Modules\GamificationEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable ledger row for each reward, penalty, shop purchase, or admin adjustment.
 */
#[Fillable([
    'gamification_wallet_id',
    'gamification_rule_id',
    'event',
    'points_delta',
    'coins_delta',
    'xp_delta',
    'metadata',
])]
class GamificationTransaction extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points_delta' => 'integer',
            'coins_delta' => 'integer',
            'xp_delta' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<GamificationWallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(GamificationWallet::class, 'gamification_wallet_id');
    }

    /**
     * @return BelongsTo<GamificationRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(GamificationRule::class, 'gamification_rule_id');
    }
}
