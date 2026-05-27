<?php

namespace App\Services\Quiz\Scoring\Engines;

use App\Models\QuizSession;
use App\Services\Quiz\RebootProtocol\RebootProtocolFlow;
use App\Services\Quiz\RebootProtocol\RebootProtocolReportBuilder;
use App\Services\Quiz\Scoring\ScoringEngineContract;

class RebootProtocolScoringEngine implements ScoringEngineContract
{
    public function __construct(
        private readonly RebootProtocolFlow $flow,
        private readonly RebootProtocolReportBuilder $reportBuilder,
    ) {}

    public function score(QuizSession $session): array
    {
        $answers = $this->flow->answersByKey($session);
        $report = $this->reportBuilder->build($answers);

        $dimensions = $report['dimensions'] ?? [];

        return [
            'type_code' => $report['type_code'],
            'outcome_profile_id' => null,
            'dimension_scores' => [
                'stability' => (float) ($report['stability_score'] ?? 0),
                'anxiety' => round(($dimensions['anxiety'] ?? 0) * 100, 1),
                'identity_erosion' => round(($dimensions['identity_erosion'] ?? 0) * 100, 1),
                'dysregulation' => round(($dimensions['dysregulation'] ?? 0) * 100, 1),
                'readiness' => round(($dimensions['readiness'] ?? 0) * 100, 1),
                'obsession' => round(($dimensions['obsession'] ?? 0) * 100, 1),
                'urgency' => round(($dimensions['urgency'] ?? 0) * 100, 1),
            ],
            'free_report' => $report,
        ];
    }
}
