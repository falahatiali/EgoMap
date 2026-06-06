<?php

namespace Modules\AetherEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\AetherEngine\Enums\CoachingTone;

#[Fillable([
    'slug',
    'name',
    'tone',
    'system_prompt',
    'task_prompt',
    'is_default',
    'is_active',
])]
class AetherPromptTemplate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'tone' => CoachingTone::class,
        ];
    }
}
