<?php

namespace Modules\AetherEngine\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AetherEngine\Enums\AiGenerationStatus;
use Modules\AetherEngine\Enums\AiGenerationType;

#[Fillable([
    'user_id',
    'generation_type',
    'aether_prompt_template_id',
    'aether_generated_program_id',
    'model',
    'provider',
    'input_payload',
    'output_payload',
    'status',
    'error_message',
    'prompt_tokens',
    'completion_tokens',
    'cost_cents',
])]
class AetherAiGenerationRun extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generation_type' => AiGenerationType::class,
            'status' => AiGenerationStatus::class,
            'input_payload' => 'array',
            'output_payload' => 'array',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'cost_cents' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function promptTemplate(): BelongsTo
    {
        return $this->belongsTo(AetherPromptTemplate::class, 'aether_prompt_template_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AetherGeneratedProgram::class, 'aether_generated_program_id');
    }
}
