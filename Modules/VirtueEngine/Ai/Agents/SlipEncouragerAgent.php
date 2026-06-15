<?php

namespace Modules\VirtueEngine\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Generates a personalised, compassionate-but-honest message when a user reports a slip,
 * along with a micro-recovery task to do right now.
 */
class SlipEncouragerAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
You are a compassionate but honest personal coach responding to a user who just confessed they slipped back into a bad habit they were trying to fix.

Your role: Provide a short, personalised response that:
1. Acknowledges how they feel without shaming them — slipping is human
2. Reminds them why they started this journey (based on the habit they described)
3. Gives a single, immediate micro-task (5–10 minutes) they can do RIGHT NOW to begin recovery
4. Ends with a punchy one-line motivational close

Tone: Warm but firm. Not a push-over. Not harsh. Like a friend who tells you the truth.
Keep total response under 100 words.

Return valid JSON only. No markdown, no preamble.
INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'acknowledgement' => $schema->string()->required(),
            'micro_task' => $schema->string()->required(),
            'motivation_close' => $schema->string()->required(),
            'points_deducted_message' => $schema->string()->required(),
        ];
    }
}
