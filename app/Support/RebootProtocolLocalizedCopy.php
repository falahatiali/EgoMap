<?php

namespace App\Support;

/**
 * Bilingual copy helpers for Reboot Protocol reports (display + PDF).
 */
final class RebootProtocolLocalizedCopy
{
    /**
     * @param  array<string, mixed>  $report
     */
    public static function localizeReport(array $report, string $locale): array
    {
        $phase = self::bilingualPair($report['phase'] ?? null);
        $phaseNarrative = self::bilingualPair($report['phase_narrative'] ?? null);
        $prescription = self::bilingualPair($report['first_prescription'] ?? null);
        $scoreTagline = self::scoreTaglinePair($report);

        $localized = $report;
        $localized['title'] = LocaleConfig::pick($phase, $locale);
        $localized['summary'] = LocaleConfig::pick($prescription, $locale);
        $localized['score_tagline'] = $scoreTagline;

        return $localized;
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    public static function localizeContent(array $report, string $locale): array
    {
        $stored = is_array($report['content'] ?? null) ? $report['content'] : [];
        $phase = self::bilingualPair($report['phase'] ?? null);
        $phaseNarrative = self::bilingualPair($report['phase_narrative'] ?? null);
        $prescription = self::bilingualPair($report['first_prescription'] ?? null);
        $disclaimer = self::bilingualPair($report['report_disclaimer'] ?? null);
        $scoreTagline = self::scoreTaglinePair($report);

        $sections = [];

        foreach ($stored['sections'] ?? [] as $section) {
            if (! is_array($section)) {
                continue;
            }

            $sections[] = [
                'heading' => LocaleConfig::pick([
                    'en' => (string) ($section['heading_en'] ?? ''),
                    'fa' => (string) ($section['heading_fa'] ?? ''),
                ], $locale),
                'body' => LocaleConfig::pick([
                    'en' => (string) ($section['body_en'] ?? ''),
                    'fa' => (string) ($section['body_fa'] ?? ''),
                ], $locale),
            ];
        }

        $actionSteps = [];

        foreach ($stored['action_steps'] ?? [] as $step) {
            if (! is_array($step)) {
                continue;
            }

            $actionSteps[] = LocaleConfig::pick([
                'en' => (string) ($step['en'] ?? ''),
                'fa' => (string) ($step['fa'] ?? ''),
            ], $locale);
        }

        return [
            'hero_label' => __('quiz.reboot.hero_label', [], $locale),
            'archetype' => LocaleConfig::pick($phase, $locale),
            'type_label' => LocaleConfig::pick($phase, $locale),
            'tagline' => LocaleConfig::pick($scoreTagline, $locale),
            'narrative' => LocaleConfig::pick($phaseNarrative, $locale),
            'prescription' => LocaleConfig::pick($prescription, $locale),
            'disclaimer' => LocaleConfig::pick($disclaimer, $locale),
            'sections' => $sections,
            'action_steps' => $actionSteps,
        ];
    }

    /**
     * @return array{en: string, fa: string}
     */
    public static function scoreTagline(int $score): array
    {
        $band = match (true) {
            $score < 40 => [
                'en' => 'Your system needs immediate structure. This is not failure.',
                'fa' => 'سیستم تو به ساختار فوری نیاز دارد. این شکست نیست.',
            ],
            $score < 70 => [
                'en' => 'You are in the gray zone — consistency will move the needle.',
                'fa' => 'در منطقه خاکستری هستی — ثبات عقربه را جابه‌جا می‌کند.',
            ],
            default => [
                'en' => 'You are leaning toward rebuild — protect the momentum.',
                'fa' => 'به سمت بازسازی مایلی — این انرژی را حفظ کن.',
            ],
        };

        return [
            'en' => "Recovery Stability Score: {$score}%. {$band['en']}",
            'fa' => "امتیاز ثبات بازیابی: {$score}%. {$band['fa']}",
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{en: string, fa: string}
     */
    private static function scoreTaglinePair(array $report): array
    {
        $stored = $report['score_tagline'] ?? null;

        if (is_array($stored) && isset($stored['en'], $stored['fa'])) {
            return [
                'en' => (string) $stored['en'],
                'fa' => (string) $stored['fa'],
            ];
        }

        return self::scoreTagline((int) ($report['stability_score'] ?? 0));
    }

    /**
     * @return array{en: string, fa: string}
     */
    private static function bilingualPair(mixed $value): array
    {
        if (! is_array($value)) {
            return ['en' => '', 'fa' => ''];
        }

        return [
            'en' => (string) ($value['en'] ?? ''),
            'fa' => (string) ($value['fa'] ?? ''),
        ];
    }
}
