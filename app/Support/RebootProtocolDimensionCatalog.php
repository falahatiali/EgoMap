<?php

namespace App\Support;

/**
 * Display metadata for Reboot Protocol analysis dimensions (0–1 floats).
 */
final class RebootProtocolDimensionCatalog
{
    /** @var list<string> */
    private const ORDER = [
        'anxiety',
        'urgency',
        'dysregulation',
        'obsession',
        'identity_erosion',
        'avoidant_partner',
        'readiness',
    ];

    /**
     * @var array<string, array{label: array{en: string, fa: string}, description: array{en: string, fa: string}}>
     */
    private const META = [
        'anxiety' => [
            'label' => ['en' => 'Anxiety load', 'fa' => 'بار اضطراب'],
            'description' => [
                'en' => 'How activated your nervous system is around the breakup.',
                'fa' => 'میزان فعال‌شدن سیستم عصبی‌ات درباره جدایی.',
            ],
        ],
        'urgency' => [
            'label' => ['en' => 'Contact urgency', 'fa' => 'فوریت تماس'],
            'description' => [
                'en' => 'Pull to reach out, check in, or get closure tonight.',
                'fa' => 'کشش برای پیام دادن، چک کردن، یا گرفتن جواب همین الان.',
            ],
        ],
        'dysregulation' => [
            'label' => ['en' => 'Emotional dysregulation', 'fa' => 'بی‌ثباتی احساسی'],
            'description' => [
                'en' => 'Risk of overwhelm, shutdown, or losing stability.',
                'fa' => 'ریسک فروریختن، خاموشی، یا از دست دادن ثبات.',
            ],
        ],
        'obsession' => [
            'label' => ['en' => 'Obsessive loop', 'fa' => 'حلقه وسواس فکری'],
            'description' => [
                'en' => 'Mental replay, fantasy, and difficulty letting the story go.',
                'fa' => 'پخش ذهنی، خیال‌پردازی، و سخت بودن رها کردن داستان.',
            ],
        ],
        'identity_erosion' => [
            'label' => ['en' => 'Identity erosion', 'fa' => 'فرسایش هویت'],
            'description' => [
                'en' => 'How much you feel undefined without the relationship.',
                'fa' => 'چقدر بدون رابطه احساس تعریف‌نشده‌ای.',
            ],
        ],
        'avoidant_partner' => [
            'label' => ['en' => 'Avoidant dynamic', 'fa' => 'دینامیک دوری'],
            'description' => [
                'en' => 'Signals of push–pull or emotional distance on her side.',
                'fa' => 'نشانه‌های دوری–نزدیکی یا فاصله احساسی از طرف او.',
            ],
        ],
        'readiness' => [
            'label' => ['en' => 'Rebuild readiness', 'fa' => 'آمادگی بازسازی'],
            'description' => [
                'en' => 'Capacity for structure, routine, and forward action.',
                'fa' => 'ظرفیت برای ساختار، روال، و اقدام رو به جلو.',
            ],
        ],
    ];

    /**
     * @param  array<string, float|int>  $dimensions
     * @return list<array{key: string, label: string, description: string, percent: int, value: float}>
     */
    public static function rows(array $dimensions, string $locale): array
    {
        if ($dimensions === []) {
            return [];
        }

        $first = reset($dimensions);

        if (is_array($first)) {
            return [];
        }

        $rows = [];

        foreach (self::ORDER as $key) {
            if (! array_key_exists($key, $dimensions)) {
                continue;
            }

            $value = max(0.0, min(1.0, (float) $dimensions[$key]));
            $meta = self::META[$key] ?? [
                'label' => ['en' => $key, 'fa' => $key],
                'description' => ['en' => '', 'fa' => ''],
            ];

            $rows[] = [
                'key' => $key,
                'label' => LocaleConfig::pick($meta['label'], $locale),
                'description' => LocaleConfig::pick($meta['description'], $locale),
                'percent' => (int) round($value * 100),
                'value' => $value,
            ];
        }

        return $rows;
    }
}
