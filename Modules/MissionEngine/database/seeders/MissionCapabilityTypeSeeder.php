<?php

namespace Modules\MissionEngine\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\MissionEngine\Enums\MissionCapabilityKey;
use Modules\MissionEngine\Models\MissionCapabilityType;

class MissionCapabilityTypeSeeder extends Seeder
{
    public function run(): void
    {
        $capabilities = [
            [
                'key' => MissionCapabilityKey::Schedule,
                'name' => ['en' => 'Schedule', 'fa' => 'برنامه زمانی'],
                'description' => ['en' => 'Days, times, and recurring sessions.', 'fa' => 'روزها، ساعات و جلسات تکرارشونده.'],
                'icon' => 'fa-calendar-days',
                'sort_order' => 10,
            ],
            [
                'key' => MissionCapabilityKey::Nutrition,
                'name' => ['en' => 'Nutrition', 'fa' => 'تغذیه'],
                'description' => ['en' => 'Meal plans, macros, and dietary rules.', 'fa' => 'برنامه غذایی، ماکروها و قوانین تغذیه.'],
                'icon' => 'fa-utensils',
                'sort_order' => 20,
            ],
            [
                'key' => MissionCapabilityKey::Supplement,
                'name' => ['en' => 'Supplements', 'fa' => 'مکمل‌ها'],
                'description' => ['en' => 'Supplement stack and timing.', 'fa' => 'پشته مکمل و زمان‌بندی مصرف.'],
                'icon' => 'fa-capsules',
                'sort_order' => 30,
            ],
            [
                'key' => MissionCapabilityKey::Equipment,
                'name' => ['en' => 'Equipment', 'fa' => 'تجهیزات'],
                'description' => ['en' => 'Gear, tools, and prerequisites.', 'fa' => 'وسایل، ابزار و پیش‌نیازها.'],
                'icon' => 'fa-toolbox',
                'sort_order' => 40,
            ],
            [
                'key' => MissionCapabilityKey::Measurement,
                'name' => ['en' => 'Measurements', 'fa' => 'اندازه‌گیری'],
                'description' => ['en' => 'Track weight, body metrics, and performance numbers.', 'fa' => 'ثبت وزن، متریک‌های بدن و اعداد عملکرد.'],
                'icon' => 'fa-chart-line',
                'sort_order' => 50,
                'default_config' => ['metrics' => ['weight', 'body_fat', 'chest', 'waist']],
            ],
            [
                'key' => MissionCapabilityKey::Content,
                'name' => ['en' => 'Content', 'fa' => 'محتوا'],
                'description' => ['en' => 'Articles, videos, and reference files.', 'fa' => 'مقالات، ویدیو و فایل‌های مرجع.'],
                'icon' => 'fa-book-open',
                'sort_order' => 60,
            ],
            [
                'key' => MissionCapabilityKey::Task,
                'name' => ['en' => 'Tasks', 'fa' => 'تسک‌ها'],
                'description' => ['en' => 'Daily and weekly actionable tasks.', 'fa' => 'کارهای روزانه و هفتگی قابل اجرا.'],
                'icon' => 'fa-list-check',
                'sort_order' => 70,
            ],
            [
                'key' => MissionCapabilityKey::Mindset,
                'name' => ['en' => 'Mindset', 'fa' => 'ذهن و حس‌وحال'],
                'description' => ['en' => 'Mood, journaling, and mental checkpoints.', 'fa' => 'حس‌وحال، ژورنال و چک‌پوینت‌های ذهنی.'],
                'icon' => 'fa-brain',
                'sort_order' => 80,
            ],
            [
                'key' => MissionCapabilityKey::Registration,
                'name' => ['en' => 'Registration', 'fa' => 'ثبت‌نام و آماده‌سازی'],
                'description' => ['en' => 'Signup steps and onboarding checklist.', 'fa' => 'مراحل ثبت‌نام و چک‌لیست آماده‌سازی.'],
                'icon' => 'fa-clipboard-check',
                'sort_order' => 90,
            ],
            [
                'key' => MissionCapabilityKey::Finance,
                'name' => ['en' => 'Finance', 'fa' => 'هزینه و بودجه'],
                'description' => ['en' => 'Costs, budget, and payment milestones.', 'fa' => 'هزینه‌ها، بودجه و نقاط پرداخت.'],
                'icon' => 'fa-wallet',
                'sort_order' => 100,
            ],
            [
                'key' => MissionCapabilityKey::Checklist,
                'name' => ['en' => 'Checklist', 'fa' => 'چک‌لیست'],
                'description' => ['en' => 'Generic checklist blocks for any mission.', 'fa' => 'بلوک چک‌لیست عمومی برای هر مأموریت.'],
                'icon' => 'fa-square-check',
                'sort_order' => 110,
            ],
        ];

        foreach ($capabilities as $capability) {
            MissionCapabilityType::query()->updateOrCreate(
                ['key' => $capability['key']],
                [
                    'name' => $capability['name'],
                    'description' => $capability['description'],
                    'icon' => $capability['icon'],
                    'is_core' => true,
                    'sort_order' => $capability['sort_order'],
                    'default_config' => $capability['default_config'] ?? null,
                ],
            );
        }
    }
}
