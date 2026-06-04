<?php

namespace Modules\MissionEngine\Models;

use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'enrollment_id',
    'name',
    'brand',
    'default_unit',
    'default_amount',
    'sort_order',
    'is_active',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class MissionSupplementProduct extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
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
     * @return HasMany<MissionSupplementIntake, $this>
     */
    public function intakes(): HasMany
    {
        return $this->hasMany(MissionSupplementIntake::class, 'supplement_product_id');
    }
}
