<?php

namespace Modules\VirtueEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\VirtueEngine\Enums\VirtueHabitCategory;

#[Fillable([
    'slug', 'name', 'category', 'description',
    'ai_root_cause', 'ai_steps', 'ai_affirmation',
    'is_predefined', 'is_active', 'sort_order',
])]
class VirtueHabit extends Model
{
    protected function casts(): array
    {
        return [
            'ai_steps' => 'array',
            'is_predefined' => 'boolean',
            'is_active' => 'boolean',
            'category' => VirtueHabitCategory::class,
        ];
    }

    public function routines(): HasMany
    {
        return $this->hasMany(VirtueRoutine::class);
    }
}
