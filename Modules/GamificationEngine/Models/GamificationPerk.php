<?php

namespace Modules\GamificationEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\GamificationEngine\Enums\GamificationPerkType;

#[Fillable([
    'slug',
    'name',
    'description',
    'type',
    'duration_days',
    'is_active',
])]
class GamificationPerk extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => GamificationPerkType::class,
            'duration_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
