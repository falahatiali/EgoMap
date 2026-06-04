<?php

namespace Tests\Unit\Locale;

use App\Support\LocaleConfig;
use Tests\TestCase;

class LocaleConfigTest extends TestCase
{
    public function test_pick_returns_requested_locale_when_present(): void
    {
        $this->assertSame(
            'Bonjour',
            LocaleConfig::pick(['en' => 'Hello', 'fa' => 'Salam', 'fr' => 'Bonjour'], 'fr'),
        );
    }

    public function test_pick_falls_back_to_default_locale(): void
    {
        $this->assertSame(
            'Hello',
            LocaleConfig::pick(['en' => 'Hello', 'fa' => 'Salam'], 'de'),
        );
    }

    public function test_translate_uses_lang_file_for_locale(): void
    {
        $this->assertSame(
            '10 pushups or 5 minutes of deep breathing — repair the shield.',
            LocaleConfig::translate('recovery_ai.slip.recovery_task', 'en'),
        );

        $this->assertSame(
            '۱۰ دراز و نشست یا ۵ دقیقه تنفس عمیق — سپر را ترمیم کن.',
            LocaleConfig::translate('recovery_ai.slip.recovery_task', 'fa'),
        );
    }

    public function test_translate_lines_returns_array_from_lang_file(): void
    {
        $lines = LocaleConfig::translateLines('recovery_ai.fallback.truth_flashes', 'en');

        $this->assertCount(3, $lines);
        $this->assertStringContainsString('Contact in this moment', $lines[0]);
    }

    public function test_ai_language_name_reads_from_config(): void
    {
        $this->assertSame('Persian (Farsi)', LocaleConfig::aiLanguageName('fa'));
        $this->assertSame('English', LocaleConfig::aiLanguageName('en'));
    }
}
