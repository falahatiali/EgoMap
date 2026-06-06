<?php

namespace Modules\AetherEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AetherEngine\Enums\ProgramScheduleEntryType;

#[Fillable([
    'aether_generated_program_id',
    'iso_weekday',
    'entry_type',
    'workout_day_index',
    'meal_timing_note',
])]
class AetherProgramScheduleEntry extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'iso_weekday' => 'integer',
            'workout_day_index' => 'integer',
            'entry_type' => ProgramScheduleEntryType::class,
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AetherGeneratedProgram::class, 'aether_generated_program_id');
    }
}
