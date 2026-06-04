<?php

namespace Tests\Unit\Support;

use App\Support\Ai\ToonPromptBuilder;
use Tests\TestCase;

class ToonPromptBuilderTest extends TestCase
{
    public function test_build_wraps_toon_encoded_context_with_instructions_and_task(): void
    {
        $prompt = ToonPromptBuilder::build(
            'You are a coach.',
            ['locale' => 'en', 'score' => 42],
            'Summarize the user state.',
        );

        $this->assertStringContainsString('You are a coach.', $prompt);
        $this->assertStringContainsString('TOON', $prompt);
        $this->assertStringContainsString('locale', $prompt);
        $this->assertStringContainsString('Summarize the user state.', $prompt);
    }
}
