<?php

namespace Tests\Unit;

use App\Support\DurationFormatter;
use Tests\TestCase;

class DurationFormatterTest extends TestCase
{
    public function test_persian_duration_uses_full_words_and_digits(): void
    {
        $formatted = DurationFormatter::formatDaysHoursMinutes(610, 'fa');

        $this->assertStringContainsString('روز', $formatted);
        $this->assertStringContainsString('ساعت', $formatted);
        $this->assertStringContainsString('دقیقه', $formatted);
        $this->assertStringContainsString('۰', $formatted);
        $this->assertStringContainsString('۱۰', $formatted);
        $this->assertDoesNotMatchRegularExpression('/\d+\s+س\s+\d+/u', $formatted);
        $this->assertDoesNotMatchRegularExpression('/\d+\s+د\s*$/u', $formatted);
    }

    public function test_english_duration_uses_full_words(): void
    {
        $formatted = DurationFormatter::formatDaysHoursMinutes(610, 'en');

        $this->assertSame('0 days, 0 hours, and 10 minutes', $formatted);
    }
}
