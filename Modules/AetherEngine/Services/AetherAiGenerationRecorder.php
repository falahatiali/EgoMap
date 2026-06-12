<?php

namespace Modules\AetherEngine\Services;

use App\Models\User;
use Modules\AetherEngine\Enums\AiGenerationStatus;
use Modules\AetherEngine\Enums\AiGenerationType;
use Modules\AetherEngine\Models\AetherAiGenerationRun;
use Modules\AetherEngine\Models\AetherGeneratedProgram;

class AetherAiGenerationRecorder
{
    /**
     * @param  array<string, mixed>  $inputPayload
     */
    public function start(
        User $user,
        AiGenerationType $type,
        array $inputPayload,
        ?int $promptTemplateId = null,
    ): AetherAiGenerationRun {
        return AetherAiGenerationRun::query()->create([
            'user_id' => $user->id,
            'generation_type' => $type,
            'aether_prompt_template_id' => $promptTemplateId,
            'input_payload' => $inputPayload,
            'status' => AiGenerationStatus::Pending,
            'provider' => config('aether.ai_enrichment_enabled') ? 'laravel-ai' : 'rules-engine',
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $outputPayload
     */
    public function succeed(
        AetherAiGenerationRun $run,
        ?AetherGeneratedProgram $program = null,
        ?array $outputPayload = null,
    ): AetherAiGenerationRun {
        $run->update([
            'aether_generated_program_id' => $program?->id,
            'output_payload' => $outputPayload,
            'status' => AiGenerationStatus::Success,
        ]);

        return $run->fresh();
    }

    public function fail(AetherAiGenerationRun $run, string $message): AetherAiGenerationRun
    {
        $run->update([
            'status' => AiGenerationStatus::Failed,
            'error_message' => $message,
        ]);

        return $run->fresh();
    }
}
