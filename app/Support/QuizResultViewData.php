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

        $locale = LocaleConfig::resolve($locale ?? $session->locale ?? app()->getLocale());

        $report = $session->result?->free_report ?? [];
        $report = is_array($report) ? $report : [];

        if (($report['template'] ?? '') === 'reboot_protocol') {
            return self::fromRebootProtocolReport($report, $locale);
        }

        $typeCode = strtolower((string) ($report['type_code'] ?? ''));

        $content = self::resolveContent($session, $report);
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
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $report
     * @return array{report: array<string, mixed>, content: array<string, mixed>, palette: array{accent: string, soft: string, glow: string, group: string}}
     */
    private static function fromRebootProtocolReport(array $report, string $locale): array
    {
        return [
            'report' => RebootProtocolLocalizedCopy::localizeReport($report, $locale),
            'content' => RebootProtocolLocalizedCopy::localizeContent($report, $locale),
            'palette' => [
                'accent' => '#34D399',
                'soft' => 'rgba(52, 211, 153, 0.12)',
                'glow' => 'rgba(52, 211, 153, 0.35)',
                'group' => 'reboot',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private static function resolveContent(QuizSession $session, array $report): array
    {
        $fromReport = $report['content'] ?? [];

        if (is_array($fromReport) && $fromReport !== []) {
            return $fromReport;
        }

        $profile = $session->result?->outcomeProfile;

        if ($profile === null) {
            return [];
        }

        return $profile->getTranslation('content', app()->getLocale(), true) ?? [];
    }
}
