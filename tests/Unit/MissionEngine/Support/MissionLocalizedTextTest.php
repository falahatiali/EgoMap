<?php

namespace Tests\Unit\MissionEngine\Support;

use Modules\MissionEngine\Support\MissionLocalizedText;
use Tests\TestCase;

class MissionLocalizedTextTest extends TestCase
{
    public function test_legacy_persian_focus_translates_to_english(): void
    {
        $this->assertSame('Chest', MissionLocalizedText::forLocale('سینه', 'en'));
        $this->assertSame('سینه', MissionLocalizedText::forLocale('سینه', 'fa'));
    }

    public function test_bilingual_array_returns_correct_locale(): void
    {
        $value = [
            'en' => 'Legs',
            'fa' => 'پا',
        ];

        $this->assertSame('Legs', MissionLocalizedText::forLocale($value, 'en'));
        $this->assertSame('پا', MissionLocalizedText::forLocale($value, 'fa'));
    }

    public function test_merge_updates_only_requested_locale(): void
    {
        $merged = MissionLocalizedText::merge(
            ['en' => 'Chest', 'fa' => 'سینه'],
            'Shoulders',
            'en',
        );

        $this->assertSame('Shoulders', $merged['en']);
        $this->assertSame('سینه', $merged['fa']);
    }

    public function test_english_focus_translates_to_persian(): void
    {
        $this->assertSame('پا', MissionLocalizedText::forLocale('Legs', 'fa'));
    }
}
