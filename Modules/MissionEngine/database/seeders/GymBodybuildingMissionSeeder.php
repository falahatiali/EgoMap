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
            'difficulty' => MissionDifficulty::Intermediate,
            'estimated_days' => 84,
            'status' => MissionTemplateStatus::Published,
            'version' => 2,
            'is_featured' => true,
            'sort_order' => 1,
            'published_at' => now(),
            'icon' => 'fa-dumbbell',
            'meta' => [
                'ghost_mode_recommended' => true,
                'accent' => 'emerald',
                'engine_module' => 'aether',
                'highlights' => [
                    'en' => [
                        '12-week structure with clear phases',
                        'AI workout & meal plans via Aether (Pro)',
                        'Daily check-ins, supplements & gear tracking',
                        'Built for Ghost Mode — no ex drama, just reps',
                    ],
                    'fa' => [
                        'ساختار ۱۲ هفته‌ای با فازهای مشخص',
                        'برنامه تمرین و غذای AI از طریق Aether (Pro)',
                        'گزارش روزانه، مکمل‌ها و لیست تجهیزات',
                        'ساخته‌شده برای Ghost Mode — بدون حواس‌پرتی، فقط تمرکز',
                    ],
                ],
                'outcomes' => [
                    'en' => [
                        'Consistent gym attendance on your chosen days',
                        'Measurable progress via weight & adherence',
                        'A personalized training split you actually follow',
                        'Nutrition targets aligned with your goal',
                    ],
                    'fa' => [
                        'حضور منظم در باشگاه در روزهای انتخابی',
                        'پیشرفت قابل اندازه‌گیری با وزن و adherence',
                        'برنامه تمرینی شخصی که واقعاً اجرا می‌کنی',
                        'اهداف تغذیه هم‌راستا با هدفت',
                    ],
                ],
            ],
        ]);
        $template->setTranslations('title', [
            'en' => 'Gym & Bodybuilding',
            'fa' => 'باشگاه و بدنسازی',
        ]);
        $template->setTranslations('summary', [
            'en' => 'Your 12-week rebuild mission: commit to the gym, track the essentials, and let Aether handle the program details.',
            'fa' => 'مأموریت ۱۲ هفته‌ای بازسازی: به باشگاه متعهد شو، اصول را ثبت کن، جزئیات برنامه را به Aether بسپار.',
        ]);
        $template->setTranslations('description', [
            'en' => <<<'TXT'
This mission is your **container** — not your workout log.

You get a clear roadmap: join a gym, pick your training days, track supplements, gear, and daily mood. **Workout sets, meal plans, and exercise swaps** live in **Aether** (our coaching engine), linked to this mission when you generate a Pro AI program.

Phases walk you from signup to locked-in habit. Progress is measured by adherence and check-ins, not by typing every set here.

Perfect inside Ghost Mode when you want structure without noise.
TXT,
            'fa' => <<<'TXT'
این مأموریت **قاب** توست — نه دفترچه ثبت ست.

یک نقشه راه شفاف داری: عضویت باشگاه، انتخاب روزهای تمرین، ثبت مکمل‌ها، تجهیزات و حس روزانه. **ست‌ها، برنامه غذایی و جایگزینی حرکت** داخل **Aether** (موتور مربیگری) است و با تولید برنامه Pro به این مأموریت وصل می‌شود.

فازها از ثبت‌نام تا عادت پایدار را هدایت می‌کنند. پیشرفت با adherence و گزارش روزانه سنجیده می‌شود، نه با تایپ هر ست اینجا.

ایده‌آل داخل Ghost Mode وقتی ساختار می‌خواهی بدون شلوغی.
TXT,
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
                        'label' => ['en' => 'AI workout plan (Aether)', 'fa' => 'برنامه تمرین AI (Aether)'],
                        'description' => [
                            'en' => 'Generate splits, sets, and progression in the coaching module.',
                            'fa' => 'اسپلیت، ست و پروگرشن در ماژول مربیگری ساخته می‌شود.',
                        ],
                    ],
                ],
            ],
            MissionCapabilityKey::Nutrition->value => [
                'features' => [
                    'ai_meal_plan' => [
                        'requires_pro' => true,
                        'label' => ['en' => 'AI meal plan (Aether)', 'fa' => 'برنامه غذایی AI (Aether)'],
                        'description' => [
                            'en' => 'Macros and meals live in Aether — not duplicated here.',
                            'fa' => 'ماکرو و وعده‌ها در Aether است — اینجا تکرار نمی‌شود.',
                        ],
                    ],
                ],
            ],
            MissionCapabilityKey::Registration->value => [
                'checklist' => [
                    ['key' => 'visit_gym', 'label' => ['en' => 'Tour the gym & ask about membership', 'fa' => 'بازدید و پرسیدن شرایط عضویت']],
                    ['key' => 'compare_plans', 'label' => ['en' => 'Compare monthly vs annual pricing', 'fa' => 'مقایسه ماهانه و سالانه']],
                    ['key' => 'sign_contract', 'label' => ['en' => 'Sign membership contract', 'fa' => 'امضای قرارداد']],
                    ['key' => 'first_session', 'label' => ['en' => 'Book induction / first session', 'fa' => 'رزرو جلسه آشنایی / اول']],
                    ['key' => 'locker_setup', 'label' => ['en' => 'Set up locker or storage', 'fa' => 'راه‌اندازی کمد یا جای وسایل']],
                ],
            ],
            MissionCapabilityKey::Measurement->value => [
                'metrics' => [
                    ['key' => 'weight', 'unit' => 'kg', 'label' => ['en' => 'Body weight', 'fa' => 'وزن بدن']],
                    ['key' => 'body_fat', 'unit' => '%', 'label' => ['en' => 'Body fat (optional)', 'fa' => 'درصد چربی (اختیاری)']],
                    ['key' => 'chest', 'unit' => 'cm', 'label' => ['en' => 'Chest circumference', 'fa' => 'دور سینه']],
                    ['key' => 'waist', 'unit' => 'cm', 'label' => ['en' => 'Waist circumference', 'fa' => 'دور کمر']],
                ],
            ],
            MissionCapabilityKey::Mindset->value => [
                'weekly_prompts' => [
                    ['week' => 1, 'prompt' => ['en' => 'Why does showing up matter more than perfection this month?', 'fa' => 'چرا حضور مهم‌تر از کمال این ماه است؟']],
                    ['week' => 4, 'prompt' => ['en' => 'What habit already feels automatic?', 'fa' => 'کدام عادت الان خودکار شده؟']],
                    ['week' => 8, 'prompt' => ['en' => 'Who are you becoming in the mirror — honestly?', 'fa' => 'داری چه کسی می‌شوی — صادقانه؟']],
                    ['week' => 12, 'prompt' => ['en' => 'What will you protect after this mission ends?', 'fa' => 'بعد از پایان مأموریت چه چیزی را حفظ می‌کنی؟']],
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
                'slug' => 'foundation',
                'title' => ['en' => 'Foundation', 'fa' => 'پایه‌ریزی'],
                'description' => [
                    'en' => 'Join the gym, set your schedule, gather gear. Generate your first Aether program when ready.',
                    'fa' => 'عضویت باشگاه، زمان‌بندی، آماده‌کردن وسایل. وقتی آماده شدی اولین برنامه Aether را بساز.',
                ],
                'sort_order' => 10,
                'duration_days' => 14,
            ],
            [
                'slug' => 'activation',
                'title' => ['en' => 'Activation', 'fa' => 'فعال‌سازی'],
                'description' => [
                    'en' => 'Show up on your chosen days. Log check-ins and let Aether track your sets.',
                    'fa' => 'در روزهای انتخابی حاضر شو. گزارش روزانه بزن و ست‌ها را در Aether ثبت کن.',
                ],
                'sort_order' => 20,
                'duration_days' => 28,
            ],
            [
                'slug' => 'build',
                'title' => ['en' => 'Build', 'fa' => 'ساخت'],
                'description' => [
                    'en' => 'Push adherence past 70%. Adjust supplements, refine schedule, stay consistent.',
                    'fa' => 'adherence را بالای ۷۰٪ ببر. مکمل و زمان‌بندی را تنظیم کن، ثبات را حفظ کن.',
                ],
                'sort_order' => 30,
                'duration_days' => 28,
            ],
            [
                'slug' => 'lock-in',
                'title' => ['en' => 'Lock-in', 'fa' => 'تثبیت'],
                'description' => [
                    'en' => 'The gym is part of your identity. Review progress and plan what comes next.',
                    'fa' => 'باشگاه بخشی از هویت توست. پیشرفت را مرور کن و مرحله بعد را برنامه‌ریزی کن.',
                ],
                'sort_order' => 40,
                'duration_days' => 14,
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
                'help_text' => ['en' => 'Pick the days you train. Aether uses this when building your split.', 'fa' => 'روزهای تمرین را انتخاب کن. Aether برای اسپلیت از این استفاده می‌کند.'],
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
                'field_key' => 'supplements',
                'capability_type_id' => $capabilityIds[MissionCapabilityKey::Supplement->value] ?? null,
                'field_type' => MissionFieldType::Textarea,
                'section' => 'supplements',
                'sort_order' => 50,
                'label' => ['en' => 'Supplement notes', 'fa' => 'یادداشت مکمل‌ها'],
                'help_text' => ['en' => 'Quick reference — daily intake is logged separately.', 'fa' => 'مرجع سریع — مصرف روزانه جدا ثبت می‌شود.'],
                'default_value' => "Creatine 5g daily\nWhey after workout\nVitamin D (optional)",
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
                    'compare_plans' => false,
                    'sign_contract' => false,
                    'first_session' => false,
                    'locker_setup' => false,
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
