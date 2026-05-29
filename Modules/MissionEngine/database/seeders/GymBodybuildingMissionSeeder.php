<?php

namespace Modules\MissionEngine\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\MissionEngine\Enums\MissionCapabilityKey;
use Modules\MissionEngine\Enums\MissionDifficulty;
use Modules\MissionEngine\Enums\MissionFieldType;
use Modules\MissionEngine\Enums\MissionTemplateStatus;
use Modules\MissionEngine\Models\MissionCapabilityType;
use Modules\MissionEngine\Models\MissionCategory;
use Modules\MissionEngine\Models\MissionTemplate;
use Modules\MissionEngine\Models\MissionTemplateCapability;
use Modules\MissionEngine\Models\MissionTemplateField;
use Modules\MissionEngine\Models\MissionTemplatePhase;
use Modules\MissionEngine\Services\MissionTemplateCapabilitySync;

class GymBodybuildingMissionSeeder extends Seeder
{
    public const SLUG = 'gym-bodybuilding';

    public function run(): void
    {
        $category = MissionCategory::query()->where('slug', 'body')->firstOrFail();

        $template = MissionTemplate::query()->firstOrNew(['slug' => self::SLUG]);
        $template->fill([
            'category_id' => $category->id,
            'difficulty' => MissionDifficulty::Beginner,
            'estimated_days' => 90,
            'status' => MissionTemplateStatus::Published,
            'version' => 1,
            'is_featured' => true,
            'sort_order' => 1,
            'published_at' => now(),
            'icon' => 'fa-dumbbell',
            'meta' => [
                'ghost_mode_recommended' => true,
                'accent' => 'emerald',
            ],
        ]);
        $template->setTranslations('title', [
            'en' => 'Gym & Bodybuilding',
            'fa' => 'باشگاه و بدنسازی',
        ]);
        $template->setTranslations('summary', [
            'en' => 'Structure your training days, log workouts, track weight, and rebuild your body inside Ghost Mode.',
            'fa' => 'روزهای تمرین، ثبت باشگاه، وزن و بازسازی بدنت را داخل حالت شبح منظم کن.',
        ]);
        $template->setTranslations('description', [
            'en' => 'A 90-day friendly mission: pick your gym days, build a custom split, track supplements and meals (manual or Pro AI later), and log daily check-ins.',
            'fa' => 'مأموریت ۹۰ روزه: روزهای باشگاه، برنامه تمرین شخصی، مکمل و غذا (دستی یا AI برای Pro)، و گزارش روزانه.',
        ]);
        $template->save();

        app(MissionTemplateCapabilitySync::class)->sync($template, $this->enabledCapabilityTypeIds());

        $this->applyCapabilityConfigs($template);
        $this->seedPhases($template);
        $this->seedFields($template);
    }

    /**
     * @return list<int>
     */
    private function enabledCapabilityTypeIds(): array
    {
        $keys = [
            MissionCapabilityKey::Schedule,
            MissionCapabilityKey::Task,
            MissionCapabilityKey::Nutrition,
            MissionCapabilityKey::Supplement,
            MissionCapabilityKey::Equipment,
            MissionCapabilityKey::Measurement,
            MissionCapabilityKey::Mindset,
            MissionCapabilityKey::Registration,
        ];

        return MissionCapabilityType::query()
            ->whereIn('key', array_map(fn (MissionCapabilityKey $key): string => $key->value, $keys))
            ->pluck('id')
            ->all();
    }

    private function applyCapabilityConfigs(MissionTemplate $template): void
    {
        $configs = [
            MissionCapabilityKey::Task->value => [
                'features' => [
                    'ai_workout_plan' => [
                        'requires_pro' => true,
                        'label' => ['en' => 'AI workout plan', 'fa' => 'برنامه تمرین با هوش مصنوعی'],
                    ],
                ],
            ],
            MissionCapabilityKey::Nutrition->value => [
                'features' => [
                    'ai_meal_plan' => [
                        'requires_pro' => true,
                        'label' => ['en' => 'AI meal plan', 'fa' => 'برنامه غذایی با هوش مصنوعی'],
                    ],
                ],
            ],
            MissionCapabilityKey::Registration->value => [
                'checklist' => [
                    ['key' => 'visit_gym', 'label' => ['en' => 'Visit gym & ask for membership', 'fa' => 'مراجعه و پرسیدن شرایط عضویت']],
                    ['key' => 'sign_contract', 'label' => ['en' => 'Sign membership contract', 'fa' => 'امضای قرارداد']],
                    ['key' => 'first_session', 'label' => ['en' => 'Book first session / induction', 'fa' => 'رزرو جلسه اول / آشنایی']],
                ],
            ],
            MissionCapabilityKey::Measurement->value => [
                'metrics' => [
                    ['key' => 'weight', 'unit' => 'kg', 'label' => ['en' => 'Body weight', 'fa' => 'وزن بدن']],
                    ['key' => 'body_fat', 'unit' => '%', 'label' => ['en' => 'Body fat', 'fa' => 'درصد چربی']],
                ],
            ],
        ];

        foreach ($configs as $key => $config) {
            $type = MissionCapabilityType::query()->where('key', $key)->first();

            if ($type === null) {
                continue;
            }

            MissionTemplateCapability::query()
                ->where('template_id', $template->id)
                ->where('capability_type_id', $type->id)
                ->update(['config' => $config]);
        }
    }

    private function seedPhases(MissionTemplate $template): void
    {
        $phases = [
            [
                'slug' => 'prepare',
                'title' => ['en' => 'Prepare', 'fa' => 'آماده‌سازی'],
                'description' => ['en' => 'Register at the gym and gather gear.', 'fa' => 'ثبت‌نام باشگاه و آماده‌کردن وسایل.'],
                'sort_order' => 10,
                'duration_days' => 14,
            ],
            [
                'slug' => 'execute',
                'title' => ['en' => 'Execute', 'fa' => 'اجرا'],
                'description' => ['en' => 'Train on schedule and log every week.', 'fa' => 'تمرین منظم و ثبت هفتگی.'],
                'sort_order' => 20,
                'duration_days' => 76,
            ],
        ];

        foreach ($phases as $phase) {
            $record = MissionTemplatePhase::query()->firstOrNew([
                'template_id' => $template->id,
                'slug' => $phase['slug'],
            ]);
            $record->fill([
                'sort_order' => $phase['sort_order'],
                'duration_days' => $phase['duration_days'],
            ]);
            $record->setTranslations('title', $phase['title']);
            $record->setTranslations('description', $phase['description']);
            $record->save();
        }
    }

    private function seedFields(MissionTemplate $template): void
    {
        $capabilityIds = MissionCapabilityType::query()
            ->pluck('id', 'key')
            ->mapWithKeys(fn ($id, $key) => [$key => $id]);

        $dayOptions = [
            ['value' => 'sat', 'label' => ['en' => 'Saturday', 'fa' => 'شنبه']],
            ['value' => 'sun', 'label' => ['en' => 'Sunday', 'fa' => 'یکشنبه']],
            ['value' => 'mon', 'label' => ['en' => 'Monday', 'fa' => 'دوشنبه']],
            ['value' => 'tue', 'label' => ['en' => 'Tuesday', 'fa' => 'سه‌شنبه']],
            ['value' => 'wed', 'label' => ['en' => 'Wednesday', 'fa' => 'چهارشنبه']],
            ['value' => 'thu', 'label' => ['en' => 'Thursday', 'fa' => 'پنجشنبه']],
            ['value' => 'fri', 'label' => ['en' => 'Friday', 'fa' => 'جمعه']],
        ];

        $fields = [
            [
                'field_key' => 'gym_days',
                'capability_type_id' => $capabilityIds[MissionCapabilityKey::Schedule->value] ?? null,
                'field_type' => MissionFieldType::MultiSelect,
                'section' => 'schedule',
                'sort_order' => 10,
                'label' => ['en' => 'Gym days', 'fa' => 'روزهای باشگاه'],
                'help_text' => ['en' => 'Pick the days you train (e.g. even weekdays or Sat–Mon).', 'fa' => 'روزهایی که می‌روی باشگاه را انتخاب کن.'],
                'options' => $dayOptions,
                'default_value' => ['sat', 'mon', 'wed'],
            ],
            [
                'field_key' => 'preferred_gym_time',
                'capability_type_id' => $capabilityIds[MissionCapabilityKey::Schedule->value] ?? null,
                'field_type' => MissionFieldType::Time,
                'section' => 'schedule',
                'sort_order' => 20,
                'label' => ['en' => 'Preferred time', 'fa' => 'ساعت تقریبی'],
                'default_value' => '18:00',
            ],
            [
                'field_key' => 'workout_plan',
                'capability_type_id' => $capabilityIds[MissionCapabilityKey::Task->value] ?? null,
                'field_type' => MissionFieldType::Json,
                'section' => 'workout',
                'sort_order' => 30,
                'label' => ['en' => 'Training split', 'fa' => 'برنامه تمرینی'],
                'help_text' => ['en' => 'Example: Saturday legs, Monday chest.', 'fa' => 'مثال: شنبه پا، دوشنبه سینه.'],
                'default_value' => [
                    ['day' => 'sat', 'focus' => 'legs', 'notes' => ''],
                    ['day' => 'mon', 'focus' => 'chest', 'notes' => ''],
                    ['day' => 'wed', 'focus' => 'back', 'notes' => ''],
                ],
            ],
            [
                'field_key' => 'meal_plan_notes',
                'capability_type_id' => $capabilityIds[MissionCapabilityKey::Nutrition->value] ?? null,
                'field_type' => MissionFieldType::Textarea,
                'section' => 'nutrition',
                'sort_order' => 40,
                'label' => ['en' => 'Meal plan (manual)', 'fa' => 'برنامه غذایی (دستی)'],
                'default_value' => '',
            ],
            [
                'field_key' => 'supplements',
                'capability_type_id' => $capabilityIds[MissionCapabilityKey::Supplement->value] ?? null,
                'field_type' => MissionFieldType::Textarea,
                'section' => 'supplements',
                'sort_order' => 50,
                'label' => ['en' => 'Supplements', 'fa' => 'مکمل‌ها'],
                'default_value' => "Creatine 5g daily\nWhey after workout",
            ],
            [
                'field_key' => 'equipment_notes',
                'capability_type_id' => $capabilityIds[MissionCapabilityKey::Equipment->value] ?? null,
                'field_type' => MissionFieldType::Textarea,
                'section' => 'equipment',
                'sort_order' => 60,
                'label' => ['en' => 'Gear & equipment', 'fa' => 'وسایل و تجهیزات'],
                'help_text' => [
                    'en' => 'Build your gear list with categories and ownership status.',
                    'fa' => 'لیست تجهیزات با دسته و وضعیت (دارم / باید بخرم / تو باشگاه).',
                ],
                'default_value' => '',
            ],
            [
                'field_key' => 'registration_progress',
                'capability_type_id' => $capabilityIds[MissionCapabilityKey::Registration->value] ?? null,
                'field_type' => MissionFieldType::Json,
                'section' => 'registration',
                'sort_order' => 70,
                'label' => ['en' => 'Gym signup checklist', 'fa' => 'چک‌لیست ثبت‌نام باشگاه'],
                'default_value' => [
                    'visit_gym' => false,
                    'sign_contract' => false,
                    'first_session' => false,
                ],
            ],
        ];

        foreach ($fields as $field) {
            $record = MissionTemplateField::query()->firstOrNew([
                'template_id' => $template->id,
                'field_key' => $field['field_key'],
            ]);
            $record->fill([
                'capability_type_id' => $field['capability_type_id'],
                'field_type' => $field['field_type'],
                'section' => $field['section'],
                'sort_order' => $field['sort_order'],
                'options' => $field['options'] ?? null,
                'default_value' => $field['default_value'] ?? null,
                'is_required' => false,
            ]);
            $record->setTranslations('label', $field['label']);

            if (isset($field['help_text'])) {
                $record->setTranslations('help_text', $field['help_text']);
            }

            $record->save();
        }
    }
}
