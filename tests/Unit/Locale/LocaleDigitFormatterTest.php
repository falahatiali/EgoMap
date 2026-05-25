<?php

namespace Tests\Unit\Locale;

use App\Services\Locale\LocaleDigitFormatter;
use Tests\TestCase;

class LocaleDigitFormatterTest extends TestCase
{
    public function test_formats_western_digits_for_english_locale(): void
    {
        $formatter = new LocaleDigitFormatter;

        $this->assertSame('89', $formatter->format(89, 'en'));
        $this->assertSame('05', $formatter->pad(5, 2, 'en'));
    }

    public function test_formats_persian_digits_for_farsi_locale(): void
    {
        $formatter = new LocaleDigitFormatter;

        $this->assertSame('۸۹', $formatter->format(89, 'fa'));
        $this->assertSame('۰۵', $formatter->pad(5, 2, 'fa'));
        $this->assertSame('۲۰:۵۹:۴۷', $formatter->convertWesternDigitsInString('20:59:47', 'fa'));
    }
}
