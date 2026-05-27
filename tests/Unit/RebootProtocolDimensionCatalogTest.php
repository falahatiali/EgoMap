<?php

namespace Tests\Unit;

use App\Services\Quiz\RebootProtocol\RebootProtocolAnalysisEngine;
use App\Services\Quiz\RebootProtocol\RebootProtocolReportBuilder;
use App\Support\RebootProtocolDimensionCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RebootProtocolDimensionCatalogTest extends TestCase
{
    #[Test]
    public function test_dimension_rows_reflect_engine_scores_not_default_fifty(): void
    {
        $analysis = (new RebootProtocolAnalysisEngine)->analyze([
            'situation_type' => 1,
            'time_since_event' => 1,
            'pain_focus' => [1, 2, 7],
            'immediate_risk' => [1, 6],
            'contact_after_breakup' => 4,
            'breakup_reason' => [5, 8],
            'timing_feeling' => 4,
            'relationship_pattern' => 1,
            'self_pattern' => [1, 2],
            'first_need' => 4,
        ]);

        $rows = RebootProtocolDimensionCatalog::rows($analysis['dimensions'], 'fa');

        $this->assertCount(7, $rows);
        $this->assertNotEmpty($rows[0]['label']);
        $this->assertNotSame('گرایش شما به', $rows[0]['label']);

        $percents = array_column($rows, 'percent');
        $this->assertNotSame([50, 50, 50, 50, 50, 50, 50], $percents);
        $this->assertGreaterThan(0, max($percents));
        $this->assertLessThan(100, min($percents));

        foreach ($rows as $row) {
            $this->assertSame(
                (int) round($analysis['dimensions'][$row['key']] * 100),
                $row['percent'],
            );
        }
    }

    #[Test]
    public function test_localized_report_includes_dimension_rows(): void
    {
        $report = (new RebootProtocolReportBuilder(new RebootProtocolAnalysisEngine))->build([
            'situation_type' => 1,
            'time_since_event' => 2,
            'pain_focus' => [1],
            'immediate_risk' => [7],
            'contact_after_breakup' => 1,
            'breakup_reason' => [11],
            'timing_feeling' => 7,
            'relationship_pattern' => 8,
            'self_pattern' => [9],
            'first_need' => 9,
        ]);

        $rows = RebootProtocolDimensionCatalog::rows($report['dimensions'], 'en');

        $this->assertSame('Anxiety load', $rows[0]['label']);
        $this->assertSame(
            (int) round($report['dimensions']['anxiety'] * 100),
            $rows[0]['percent'],
        );
    }
}
