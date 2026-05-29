<?php

namespace Modules\MissionEngine\Enums;

enum EquipmentStatus: string
{
    case Owned = 'owned';
    case NeedToBuy = 'need_to_buy';
    case AtGym = 'at_gym';
    case Borrowed = 'borrowed';

    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        return match ($this) {
            self::Owned => ['en' => 'I have it', 'fa' => 'دارم'],
            self::NeedToBuy => ['en' => 'Need to buy', 'fa' => 'باید بخرم'],
            self::AtGym => ['en' => 'At the gym', 'fa' => 'تو باشگاه'],
            self::Borrowed => ['en' => 'Borrowed / shared', 'fa' => 'قرضی / مشترک'],
        };
    }

    public function label(string $locale): string
    {
        $labels = $this->labels();

        return $labels[$locale] ?? $labels['en'];
    }
}
