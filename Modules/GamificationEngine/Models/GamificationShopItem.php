<?php

namespace Modules\GamificationEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\GamificationEngine\Enums\GamificationShopEffectType;

#[Fillable([
    'slug',
    'name',
    'description',
    'icon',
    'cost_coins',
    'effect_type',
    'effects',
    'sort_order',
    'is_active',
])]
class GamificationShopItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_coins' => 'integer',
            'effect_type' => GamificationShopEffectType::class,
            'effects' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
