<?php

namespace Modules\AetherEngine\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\AetherEngine\Enums\ProgramEditAction;
use Modules\AetherEngine\Enums\ProgramEditSource;

#[Fillable([
    'user_id',
    'aether_generated_program_id',
    'editable_type',
    'editable_id',
    'action',
    'before',
    'after',
    'source',
    'reason',
])]
class AetherProgramEditEvent extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ProgramEditAction::class,
            'source' => ProgramEditSource::class,
            'before' => 'array',
            'after' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AetherGeneratedProgram::class, 'aether_generated_program_id');
    }

    public function editable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'editable_type', 'editable_id');
    }
}
