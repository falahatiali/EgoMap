<?php

namespace Modules\AetherEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\AetherEngine\Enums\MuscleGroup;

#[Fillable([
    'slug',
    'name',
    'muscle_group',
    'equipment_required',
    'difficulty',
    'instructions',
    'contraindications',
    'alternative_slugs',
    'is_active',
])]
class AetherExercise extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'equipment_required' => 'array',
            'contraindications' => 'array',
            'alternative_slugs' => 'array',
            'difficulty' => 'integer',
            'is_active' => 'boolean',
            'muscle_group' => MuscleGroup::class,
        ];
    }
}
