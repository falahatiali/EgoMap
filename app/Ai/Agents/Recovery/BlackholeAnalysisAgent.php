<?php

namespace App\Ai\Agents\Recovery;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\HasStructuredOutput;

class BlackholeAnalysisAgent extends LocalizedRecoveryAgent implements HasStructuredOutput
{
    protected function instructionsTranslationKey(): string
    {
        return 'recovery_ai.agents.blackhole.instructions';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'regret_probability' => $schema->integer()->min(0)->max(100)->required(),
            'dominant_emotions' => $schema->string()->required(),
            'analysis' => $schema->string()->required(),
            'rewrite_suggestion' => $schema->string()->required(),
            'closing_line' => $schema->string()->required(),
            'commitment_suggestion' => $schema->string()->required(),
        ];
    }
}
