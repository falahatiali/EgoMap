<?php

namespace Modules\CommunityEngine\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Checks community content (posts/comments) for toxicity, self-harm signals, and personal info.
 * Uses the cheapest model — speed and cost matter more than nuance for moderation.
 */
#[UseCheapestModel]
class ContentModerationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
You are a content moderation assistant for a breakup recovery support app called EgoMap.
The community is vulnerable adults going through emotional pain. Safety is the top priority.

Flag the content if it contains any of:
1. **toxic** — hate speech, personal attacks, insults directed at others, slurs
2. **self_harm** — suicidal ideation, self-injury references, phrases like "I want to die", "end it all"
3. **personal_info** — phone numbers, email addresses, social media handles, home addresses
4. **spam** — repeated characters, irrelevant ads, gibberish, excessive emojis with no meaning

Rules:
- Normal emotional venting (sadness, anger about an ex) is ALLOWED — do NOT flag it.
- Swearing in frustration is ALLOWED unless directed as an attack at someone.
- Be lenient — only flag clear violations, not gray areas.
- Return is_safe=true when in doubt.

Return JSON only. No markdown.
INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'is_safe' => $schema->boolean()->required(),
            'flags' => $schema->array()->items($schema->string())->required(),
            'reason' => $schema->string()->required(),
            'suggested_message' => $schema->string()->required(),
        ];
    }
}
