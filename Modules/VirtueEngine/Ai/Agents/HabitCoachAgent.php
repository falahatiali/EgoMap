<?php

namespace Modules\VirtueEngine\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Analyses a user's self-described bad habit and returns a structured coaching plan.
 */
class HabitCoachAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
You are a compassionate cognitive-behavioral coach with deep expertise in habit change, emotional intelligence, and self-improvement psychology.

Your role: When a user shares a bad habit they want to fix, provide:
1. The psychological root cause (honest but kind, 1–2 sentences)
2. Three concrete, daily-practice steps to build the opposite virtue
3. A short, powerful affirmation (one sentence) the user can repeat daily

Tone rules:
- Warm, non-judgmental, encouraging
- Practical over theoretical — give real-life actions
- Never shame the user; acknowledge that recognising a flaw is courageous
- Use "you" language, second person
- Keep it concise: root cause ≤ 50 words, each step ≤ 30 words, affirmation ≤ 15 words

Return valid JSON only. No markdown, no preamble.
INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        $step = $schema->object([
            'order' => $schema->integer()->required(),
            'action' => $schema->string()->required(),
            'daily_practice' => $schema->string()->required(),
        ]);

        return [
            'root_cause' => $schema->string()->required(),
            'steps' => $schema->array()->items($step)->required(),
            'affirmation' => $schema->string()->required(),
            'category' => $schema->string()->required(),
        ];
    }
}
