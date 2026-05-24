<?php

namespace App\Services\Quiz\Scoring;

use App\Models\QuizSession;

interface ScoringEngineContract
{
    /**
     * @return array{
     *   type_code: string|null,
     *   outcome_profile_id: int|null,
     *   dimension_scores: array<string, float|int>,
     *   free_report: array<string, mixed>
     * }
     */
    public function score(QuizSession $session): array;
}
