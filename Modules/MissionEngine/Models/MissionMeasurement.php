<?php

namespace Modules\MissionEngine\Models;

use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'enrollment_id',
    'metric_key',
    'value',
    'unit',
    'is_goal',
    'measured_at',
    'notes',
    'meta',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class MissionMeasurement extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'is_goal' => 'boolean',
            'measured_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<MissionEnrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(MissionEnrollment::class, 'enrollment_id');
    }
}
