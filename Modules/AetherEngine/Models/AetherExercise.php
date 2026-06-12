<?php

namespace Modules\AetherEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\AetherEngine\Enums\MuscleGroup;

#[Fillable([
    'slug',
    'name',
    'muscle_group',
    'movement_pattern',
    'equipment_required',
    'difficulty',
    'instructions',
    'contraindications',
    'alternative_slugs',
    'gif_url',
    'video_url',
    'image_url',
    'api_source',
    'api_external_id',
    'media_cached_at',
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
            'media_cached_at' => 'datetime',
            'muscle_group' => MuscleGroup::class,
        ];
    }
}
