<?php

namespace Modules\AetherEngine\Tests\Unit;

use Modules\AetherEngine\Services\PeriodizationCalculator;
use PHPUnit\Framework\TestCase;

class PeriodizationCalculatorTest extends TestCase
{
    public function test_week_four_is_deload(): void
    {
        $calculator = new PeriodizationCalculator;

        $this->assertSame('deload', $calculator->forWeek(4)['phase_label']);
        $this->assertSame(3, $calculator->adjustedSets(4, 4));
    }

    public function test_week_three_increases_volume(): void
    {
        $calculator = new PeriodizationCalculator;

        $this->assertGreaterThan(4, $calculator->adjustedSets(4, 3));
    }
}
