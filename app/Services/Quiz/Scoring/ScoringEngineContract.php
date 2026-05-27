<?php

namespace App\Services\Quiz\Scoring;

use App\Models\QuizResponse;
use App\Models\QuizSession;

interface ScoringEngineContract
{
    /**
     * Score a completed (or in-progress) session and build the free report payload.
     *
     * Implementations should read normalized answers via {@see SessionAnswersResolver}
     * (or a quiz-specific wrapper) rather than parsing raw {@see QuizResponse} rows.
     *
     * Register the engine key in {@see ScoringEngineFactory} and set `quizzes.scoring_config.engine`.
     *
     * @return array{
     *   type_code: string|null,
     *   outcome_profile_id: int|null,
     *   dimension_scores: array<string, float|int>,
     *   free_report: array<string, mixed>
     * }
     */
    public function score(QuizSession $session): array;
}
