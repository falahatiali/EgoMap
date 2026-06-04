<?php

namespace Tests\Unit\MissionEngine\Enums;

use Modules\MissionEngine\Enums\EquipmentCategory;
use Modules\MissionEngine\Enums\EquipmentStatus;
use Modules\MissionEngine\Enums\MealType;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocalizedEnumLabelsTest extends TestCase
{
    #[DataProvider('mealTypeProvider')]
    public function test_meal_type_labels_are_localized(MealType $type, string $fa, string $en): void
    {
        $this->assertSame($fa, $type->label('fa'));
        $this->assertSame($en, $type->label('en'));
    }

    /**
     * @return array<string, array{0: MealType, 1: string, 2: string}>
     */
    public static function mealTypeProvider(): array
    {
        return [
            'breakfast' => [MealType::Breakfast, 'صبحانه', 'Breakfast'],
            'lunch' => [MealType::Lunch, 'ناهار', 'Lunch'],
            'dinner' => [MealType::Dinner, 'شام', 'Dinner'],
            'snack' => [MealType::Snack, 'میان‌وعده', 'Snack'],
        ];
    }

    public function test_equipment_category_falls_back_to_english_for_unknown_locale(): void
    {
        $this->assertSame('Lifting belt', EquipmentCategory::Belt->label('xx'));
    }

    public function test_equipment_status_labels_in_persian(): void
    {
        $this->assertSame('دارم', EquipmentStatus::Owned->label('fa'));
        $this->assertSame('باید بخرم', EquipmentStatus::NeedToBuy->label('fa'));
    }

    public function test_all_equipment_categories_have_fa_and_en_labels(): void
    {
        foreach (EquipmentCategory::cases() as $category) {
            $labels = $category->labels();
            $this->assertNotEmpty($labels['fa']);
            $this->assertNotEmpty($labels['en']);
        }
    }
}
