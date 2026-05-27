<?php

namespace Tests\Unit\Quiz;

use App\Services\Quiz\RebootProtocol\RebootProtocolAnalysisEngine;
use App\Services\Quiz\RebootProtocol\RebootProtocolReportBuilder;
use PHPUnit\Framework\TestCase;

class RebootProtocolAnalysisEngineTest extends TestCase
{
    public function test_fuzzy_memberships_and_patterns_are_produced(): void
    {
        $engine = new RebootProtocolAnalysisEngine;

        $analysis = $engine->analyze([
            'situation_type' => 1,
            'time_since_event' => 1,
            'pain_focus' => [7, 8],
            'immediate_risk' => [1],
            'contact_after_breakup' => 3,
            'breakup_reason' => [5, 7],
            'timing_feeling' => 4,
            'relationship_pattern' => 1,
            'self_pattern' => [2],
            'first_need' => 4,
        ]);

        $this->assertSame('2.1', $analysis['version']);
        $this->assertArrayHasKey('shock', $analysis['phase_memberships']);
        $this->assertGreaterThan(0, $analysis['stability_score']);
        $this->assertNotEmpty($analysis['detected_patterns']);
        $this->assertTrue($analysis['prescription']['emergency']);
    }

    public function test_stable_risk_only_does_not_trigger_emergency(): void
    {
        $engine = new RebootProtocolAnalysisEngine;

        $analysis = $engine->analyze([
            'situation_type' => 1,
            'time_since_event' => 5,
            'pain_focus' => [1],
            'immediate_risk' => [7],
            'contact_after_breakup' => 1,
            'breakup_reason' => [11],
            'timing_feeling' => 7,
            'relationship_pattern' => 8,
            'self_pattern' => [9],
            'first_need' => 9,
        ]);

        $this->assertFalse($analysis['prescription']['emergency']);
        $this->assertGreaterThanOrEqual(1.0, $analysis['features']['stable_tonight']);
    }

    public function test_report_builder_includes_detected_patterns_section(): void
    {
        $report = (new RebootProtocolReportBuilder(new RebootProtocolAnalysisEngine))->build([
            'situation_type' => 3,
            'time_since_event' => 4,
            'pain_focus' => [2],
            'immediate_risk' => [7],
            'contact_after_breakup' => 1,
            'breakup_reason' => [6],
            'timing_feeling' => 3,
            'relationship_pattern' => 4,
            'self_pattern' => [5, 6],
            'first_need' => 6,
        ]);

        $this->assertSame('reboot_protocol', $report['template']);
        $this->assertArrayHasKey('detected_patterns', $report);
        $this->assertArrayHasKey('dimensions', $report);
        $this->assertArrayHasKey('disclaimer_en', $report['content']);
        $headings = array_column($report['content']['sections'], 'heading_en');
        $this->assertContains('Profile blend', $headings);
    }
}
