<?php

namespace App\Models;

use App\Enums\GhostModeEventType;
use App\Observers\AssignsUuidObserver;
use Database\Factories\GhostModeEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'no_contact_protocol_id',
    'type',
    'trigger',
    'user_text',
    'ai_result',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class GhostModeEvent extends Model
{
    /** @use HasFactory<GhostModeEventFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => GhostModeEventType::class,
            'ai_result' => 'array',
        ];
    }

    /**
     * @return BelongsTo<NoContactProtocol, $this>
     */
    public function protocol(): BelongsTo
    {
        return $this->belongsTo(NoContactProtocol::class, 'no_contact_protocol_id');
    }
}
