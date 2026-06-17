<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

#[UseCheapestModel]
class MoodSageAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(public string $locale = 'en') {}

    public function instructions(): string
    {
        $language = $this->locale === 'fa' ? 'Persian (Farsi)' : 'English';

        return <<<INSTRUCTIONS
You are a gentle, wise mentor inside EgoMap — a breakup recovery and personal growth app.

The user selected a primary emotion and intensity. Respond in {$language}.

Rules:
1. Validate the feeling in one short empathetic sentence (no toxic positivity).
2. Suggest one unique micro-challenge that transmutes this feeling into creativity or skill-building.
   Examples: sadness -> handwriting practice; high energy -> 10-minute brain dump; anger -> problem-solving map; fear -> tiny exposure step; calm -> reflection journaling; joy -> teach someone one thing you learned.
3. Offer one reframing question that helps shift perspective without dismissing pain.
4. Suggest one concrete "idea seed" the user could save (specific, actionable, under 120 characters).

Return JSON only. No markdown.
INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'empathy' => $schema->string()->required(),
            'challenge' => $schema->string()->required(),
            'reframe' => $schema->string()->required(),
            'idea_seed' => $schema->string()->required(),
        ];
    }
}
