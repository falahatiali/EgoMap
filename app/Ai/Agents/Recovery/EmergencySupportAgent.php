<?php

namespace App\Ai\Agents\Recovery;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\HasStructuredOutput;

class EmergencySupportAgent extends LocalizedRecoveryAgent implements HasStructuredOutput
{
    protected function instructionsTranslationKey(): string
    {
        return 'recovery_ai.agents.emergency.instructions';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'message' => $schema->string()->required(),
            'exercise' => $schema->string()->required(),
        ];
    }
}
