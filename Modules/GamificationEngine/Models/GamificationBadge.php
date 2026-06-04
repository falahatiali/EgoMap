<?php

namespace Modules\GamificationEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Earnable achievement slug; granted via rule effects.badge. */
#[Fillable([
    'slug',
    'name',
    'description',
    'icon',
    'is_active',
])]
class GamificationBadge extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
