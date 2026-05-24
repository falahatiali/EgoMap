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
        $content = self::resolveContent($session, $report);
        $palette = MbtiTypePalette::for(strtolower((string) ($report['type_code'] ?? '')));

        return [
            'report' => is_array($report) ? $report : [],
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
