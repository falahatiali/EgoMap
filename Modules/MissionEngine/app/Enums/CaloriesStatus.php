<?php

namespace Modules\MissionEngine\Enums;

enum CaloriesStatus: string
{
    case Unknown = 'unknown';
    case Low = 'low';
    case OnTarget = 'on_target';
    case High = 'high';

    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        return match ($this) {
            self::Unknown => ['en' => 'Not calculated', 'fa' => 'محاسبه نشده'],
            self::Low => ['en' => 'Below target', 'fa' => 'کمتر از هدف'],
            self::OnTarget => ['en' => 'On target', 'fa' => 'در محدوده هدف'],
            self::High => ['en' => 'Above target', 'fa' => 'بیشتر از هدف'],
        };
    }

    public function label(string $locale): string
    {
        $labels = $this->labels();

        return $labels[$locale] ?? $labels['en'];
    }
}
