<?php

namespace Modules\MissionEngine\Enums;

enum MealType: string
{
    case Breakfast = 'breakfast';
    case Lunch = 'lunch';
    case Dinner = 'dinner';
    case Snack = 'snack';

    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        return match ($this) {
            self::Breakfast => ['en' => 'Breakfast', 'fa' => 'صبحانه'],
            self::Lunch => ['en' => 'Lunch', 'fa' => 'ناهار'],
            self::Dinner => ['en' => 'Dinner', 'fa' => 'شام'],
            self::Snack => ['en' => 'Snack', 'fa' => 'میان‌وعده'],
        };
    }

    public function label(string $locale): string
    {
        $labels = $this->labels();

        return $labels[$locale] ?? $labels['en'];
    }
}
