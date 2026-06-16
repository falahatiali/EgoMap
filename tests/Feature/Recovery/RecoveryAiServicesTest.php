<?php

namespace Tests\Feature\Recovery;

use App\Services\Quiz\RebootProtocol\RebootProtocolReportBuilder;
use App\Services\Recovery\GhostModeAiService;
use App\Services\Recovery\QuizAiReportService;
use App\Services\Recovery\RecoveryAiPromptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecoveryAiServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_ai_report_builds_fallback_premium_report_without_api_key(): void
    {
        $this->disableDefaultAiProvider();

        $freeReport = app(RebootProtocolReportBuilder::class)->build([
            'situation_type' => 1,
            'time_since_event' => 1,
            'immediate_risk' => [1],
            'contact_after_breakup' => 1,
            'self_pattern' => [1],
            'first_need' => 5,
            'pain_focus' => [7],
        ]);

        $payload = app(QuizAiReportService::class)->buildFallbackPayload($freeReport, 'en');

        $this->assertSame('fallback', $payload['source']);
        $this->assertArrayHasKey('assessment', $payload);
        $this->assertArrayHasKey('truth_flashes', $payload);
        $this->assertNotEmpty($payload['truth_flashes']);
        $this->assertLessThanOrEqual(3, count($payload['truth_flashes']));
    }

    public function test_blackhole_analysis_returns_fallback_structure_without_api_key(): void
    {
        $this->disableDefaultAiProvider();

        $analysis = app(GhostModeAiService::class)->analyzeBlackhole('I miss you please come back');

        $this->assertSame('fallback', $analysis['source']);
        $this->assertArrayHasKey('regret_probability', $analysis);
        $this->assertArrayHasKey('closing_line', $analysis);
        $this->assertGreaterThan(50, $analysis['regret_probability']);
    }

    public function test_emergency_message_returns_fallback_without_api_key(): void
    {
        $this->disableDefaultAiProvider();

        $message = app(GhostModeAiService::class)->emergencyMessage();

        $this->assertSame('fallback', $message['source']);
        $this->assertNotSame('', $message['message']);
        $this->assertNotSame('', $message['exercise']);
    }

    public function test_ai_enabled_follows_configured_default_provider(): void
    {
        config(['ai.default' => 'anthropic', 'ai.providers.anthropic.key' => 'sk-test']);

        $prompts = app(RecoveryAiPromptService::class);

        $this->assertSame('anthropic', $prompts->defaultProviderName());
        $this->assertTrue($prompts->isEnabled());

        config(['ai.providers.anthropic.key' => null]);

        $this->assertFalse($prompts->isEnabled());
    }

    public function test_truth_flashes_for_api_use_fallback_without_calling_ai(): void
    {
        config(['ai.default' => 'anthropic', 'ai.providers.anthropic.key' => 'sk-test']);

        $flashes = app(GhostModeAiService::class)->truthFlashesForApi();

        $this->assertNotEmpty($flashes);
        $this->assertLessThanOrEqual(3, count($flashes));
    }

    private function disableDefaultAiProvider(): void
    {
        $provider = (string) config('ai.default');

        config(["ai.providers.{$provider}.key" => null]);
    }
}
