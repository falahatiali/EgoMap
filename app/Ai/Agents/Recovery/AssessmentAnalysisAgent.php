<?php

namespace App\Ai\Agents\Recovery;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\HasStructuredOutput;

class AssessmentAnalysisAgent extends LocalizedRecoveryAgent implements HasStructuredOutput
{
    protected function instructionsTranslationKey(): string
    {
        return 'recovery_ai.agents.assessment.instructions';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->required(),
            'recovery_phase' => $schema->string()->required(),
            'main_risk' => $schema->string()->required(),
            'attachment_pattern' => $schema->string()->required(),
            'recommendation_one' => $schema->string()->required(),
            'recommendation_two' => $schema->string()->required(),
            'recommendation_three' => $schema->string()->required(),
        ];
    }
}
