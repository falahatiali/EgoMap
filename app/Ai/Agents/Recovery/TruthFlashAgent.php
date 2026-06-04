<?php

namespace App\Ai\Agents\Recovery;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\HasStructuredOutput;

class TruthFlashAgent extends LocalizedRecoveryAgent implements HasStructuredOutput
{
    protected function instructionsTranslationKey(): string
    {
        return 'recovery_ai.agents.truth_flash.instructions';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'truth_one' => $schema->string()->required(),
            'truth_two' => $schema->string()->required(),
            'truth_three' => $schema->string()->required(),
        ];
    }
}
