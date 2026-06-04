<?php

namespace Modules\MissionEngine\Enums;

enum EquipmentCategory: string
{
    case Belt = 'belt';
    case Shoes = 'shoes';
    case Straps = 'straps';
    case Sleeves = 'sleeves';
    case Apparel = 'apparel';
    case Accessories = 'accessories';
    case Other = 'other';

    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        return match ($this) {
            self::Belt => ['en' => 'Lifting belt', 'fa' => 'کمربند وزنه'],
            self::Shoes => ['en' => 'Lifting shoes', 'fa' => 'کفش وزنه‌برداری'],
            self::Straps => ['en' => 'Straps / wraps', 'fa' => 'مچ‌بند و بند'],
            self::Sleeves => ['en' => 'Sleeves', 'fa' => 'آستین / زانوبند'],
            self::Apparel => ['en' => 'Apparel', 'fa' => 'لباس تمرین'],
            self::Accessories => ['en' => 'Accessories', 'fa' => 'لوازم جانبی'],
            self::Other => ['en' => 'Other', 'fa' => 'سایر'],
        };
    }

    public function label(string $locale): string
    {
        $labels = $this->labels();

        return $labels[$locale] ?? $labels['en'];
    }
}
