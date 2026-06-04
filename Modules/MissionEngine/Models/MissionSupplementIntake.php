<?php

namespace Modules\MissionEngine\Models;

use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'enrollment_id',
    'supplement_product_id',
    'product_name',
    'brand',
    'amount',
    'unit',
    'intake_date',
    'intake_time',
    'taken',
    'notes',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class MissionSupplementIntake extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'intake_date' => 'date',
            'taken' => 'boolean',
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
     * @return BelongsTo<MissionSupplementProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(MissionSupplementProduct::class, 'supplement_product_id');
    }
}
