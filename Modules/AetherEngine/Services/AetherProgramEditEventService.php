<?php

namespace Modules\AetherEngine\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\AetherEngine\Enums\ProgramEditAction;
use Modules\AetherEngine\Enums\ProgramEditSource;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Models\AetherProgramEditEvent;

class AetherProgramEditEventService
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        User $user,
        AetherGeneratedProgram $program,
        Model $editable,
        ProgramEditAction $action,
        ?array $before = null,
        ?array $after = null,
        ProgramEditSource $source = ProgramEditSource::User,
        ?string $reason = null,
    ): AetherProgramEditEvent {
        return AetherProgramEditEvent::query()->create([
            'user_id' => $user->id,
            'aether_generated_program_id' => $program->id,
            'editable_type' => $editable::class,
            'editable_id' => $editable->getKey(),
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'source' => $source,
            'reason' => $reason,
        ]);
    }
}
