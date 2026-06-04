<?php

namespace App\Support;

use App\Models\QuizSession;

class QuizResultViewData
{
    /**
     * @return array{report: array<string, mixed>, content: array<string, mixed>, palette: array{accent: string, soft: string, glow: string, group: string}}
     */
    public static function fromSession(QuizSession $session, ?string $locale = null): array
    {
        $session->loadMissing(['result.outcomeProfile', 'quiz']);

        $locale = LocaleConfig::resolve($locale ?? LocaleConfig::fromRoute());

        $report = $session->result?->free_report ?? [];
        $report = is_array($report) ? $report : [];
        $premium = $session->result?->premium_report;
        $premium = is_array($premium) ? $premium : null;

        if (($report['template'] ?? '') === 'reboot_protocol') {
            return self::fromRebootProtocolReport($report, $locale, $premium);
        }

        $typeCode = strtolower((string) ($report['type_code'] ?? ''));

        $content = self::resolveContent($session, $report, $locale);
        $content = MbtiContentCatalog::buildContentForType($typeCode, $locale, $content);

        if (! empty($report['dimensions']) && is_array($report['dimensions'])) {
            /** @var list<array<string, mixed>> $dimensions */
            $dimensions = $report['dimensions'];
            $report['dimensions'] = MbtiContentCatalog::enrichDimensions($dimensions, $locale);
        }

        $palette = MbtiTypePalette::for($typeCode);

        if (! empty($content['group']) && is_string($content['group'])) {
            $palette['group'] = $content['group'];
        }

        return [
            'report' => $report,
            'content' => $content,
            'palette' => $palette,
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  array<string, mixed>|null  $premium
     * @return array{report: array<string, mixed>, content: array<string, mixed>, palette: array{accent: string, soft: string, glow: string, group: string}}
     */
    private static function fromRebootProtocolReport(array $report, string $locale, ?array $premium = null): array
    {
        $localized = [
            'report' => RebootProtocolLocalizedCopy::localizeReport($report, $locale),
            'content' => RebootProtocolLocalizedCopy::localizeContent($report, $locale),
            'palette' => [
                'accent' => '#34D399',
                'soft' => 'rgba(52, 211, 153, 0.12)',
                'glow' => 'rgba(52, 211, 153, 0.35)',
                'group' => 'reboot',
            ],
        ];

        if ($premium !== null && isset($premium['assessment']) && is_array($premium['assessment'])) {
            $assessment = $premium['assessment'];
            $localized['content']['ai_insights'] = [
                'badge' => __('quiz.reboot.ai_insights_badge', locale: $locale),
                'title' => __('quiz.reboot.ai_insights_title', locale: $locale),
                'summary' => (string) ($assessment['summary'] ?? ''),
                'recovery_phase' => (string) ($assessment['recovery_phase'] ?? ''),
                'main_risk' => (string) ($assessment['main_risk'] ?? ''),
                'attachment_pattern' => (string) ($assessment['attachment_pattern'] ?? ''),
                'recommendations' => is_array($assessment['recommendations'] ?? null)
                    ? $assessment['recommendations']
                    : [],
                'truth_flashes' => is_array($premium['truth_flashes'] ?? null)
                    ? $premium['truth_flashes']
                    : [],
                'source' => (string) ($premium['source'] ?? 'fallback'),
            ];
        }

        return $localized;
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private static function resolveContent(QuizSession $session, array $report, string $locale): array
    {
        $fromReport = $report['content'] ?? [];

        if (is_array($fromReport) && $fromReport !== []) {
            return $fromReport;
        }

        $profile = $session->result?->outcomeProfile;

        if ($profile === null) {
            return [];
        }

        return $profile->getTranslation('content', $locale, true) ?? [];
    }
}
