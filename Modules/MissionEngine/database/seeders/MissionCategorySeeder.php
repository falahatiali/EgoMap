<?php

namespace Modules\MissionEngine\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\MissionEngine\Models\MissionCategory;

class MissionCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'body',
                'name' => ['en' => 'Body', 'fa' => 'بدن'],
                'description' => ['en' => 'Training, strength, and physical rebuild.', 'fa' => 'تمرین، قدرت و بازسازی فیزیکی.'],
                'icon' => 'fa-dumbbell',
                'sort_order' => 10,
            ],
            [
                'slug' => 'mind',
                'name' => ['en' => 'Mind', 'fa' => 'ذهن'],
                'description' => ['en' => 'Meditation, journaling, and emotional clarity.', 'fa' => 'مدیتیشن، ژورنال و شفافیت احساسی.'],
                'icon' => 'fa-brain',
                'sort_order' => 20,
            ],
            [
                'slug' => 'discipline',
                'name' => ['en' => 'Discipline', 'fa' => 'نظم'],
                'description' => ['en' => 'Habits, focus, and daily execution.', 'fa' => 'عادت‌ها، تمرکز و اجرای روزانه.'],
                'icon' => 'fa-bolt',
                'sort_order' => 30,
            ],
            [
                'slug' => 'social',
                'name' => ['en' => 'Social', 'fa' => 'اجتماعی'],
                'description' => ['en' => 'Relationships, community, and boundaries.', 'fa' => 'روابط، جامعه و مرزها.'],
                'icon' => 'fa-people-group',
                'sort_order' => 40,
            ],
            [
                'slug' => 'skill',
                'name' => ['en' => 'Skill', 'fa' => 'مهارت'],
                'description' => ['en' => 'Shooting, sports, and specialized practice.', 'fa' => 'تیراندازی، ورزش و تمرین تخصصی.'],
                'icon' => 'fa-bullseye',
                'sort_order' => 50,
            ],
        ];

        foreach ($categories as $category) {
            MissionCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'icon' => $category['icon'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
