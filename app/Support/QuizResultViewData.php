<?php

namespace App\Support;

use App\Models\QuizSession;

class QuizResultViewData
{
    /**
     * @return array{report: array<string, mixed>, content: array<string, mixed>, palette: array{accent: string, soft: string, glow: string, group: string}}
     */
    public static function fromSession(QuizSession $session): array
    {
        $session->loadMissing(['result.outcomeProfile', 'quiz']);

        $report = $session->result?->free_report ?? [];
        $report = is_array($report) ? $report : [];

        $typeCode = strtolower((string) ($report['type_code'] ?? ''));
        $locale = LocaleConfig::resolve($session->locale ?? app()->getLocale());

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
