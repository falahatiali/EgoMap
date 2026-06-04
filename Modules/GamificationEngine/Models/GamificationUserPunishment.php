<?php

namespace Modules\GamificationEngine\Models;

use App\Models\NoContactProtocol;
use App\Models\User;
use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GamificationEngine\Enums\GamificationUserPunishmentStatus;

#[Fillable([
    'user_id',
    'gamification_punishment_id',
    'no_contact_protocol_id',
    'slip_trigger',
    'status',
    'assigned_at',
    'completed_at',
    'metadata',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class GamificationUserPunishment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GamificationUserPunishmentStatus::class,
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
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
     * @return BelongsTo<GamificationPunishment, $this>
     */
    public function punishment(): BelongsTo
    {
        return $this->belongsTo(GamificationPunishment::class, 'gamification_punishment_id');
    }

    /**
     * @return BelongsTo<NoContactProtocol, $this>
     */
    public function protocol(): BelongsTo
    {
        return $this->belongsTo(NoContactProtocol::class, 'no_contact_protocol_id');
    }
}
