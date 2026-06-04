<?php

namespace Modules\MissionEngine\Models;

use App\Models\User;
use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\MissionEngine\Enums\MissionActivityEvent;

#[Fillable(['enrollment_id', 'user_id', 'event_type', 'payload', 'logged_at'])]
#[ObservedBy([AssignsUuidObserver::class])]
class MissionActivityLog extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => MissionActivityEvent::class,
            'payload' => 'array',
            'logged_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MissionEnrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(MissionEnrollment::class, 'enrollment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
