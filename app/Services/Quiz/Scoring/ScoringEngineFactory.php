<?php

namespace App\Services\Quiz\Scoring;

use App\Enums\QuizType;
use App\Models\Quiz;
use App\Services\Quiz\Scoring\Engines\MbtiAxisScoringEngine;
use App\Services\Quiz\Scoring\Engines\WeightedSumScoringEngine;
use InvalidArgumentException;

class ScoringEngineFactory
{
    public function forQuiz(Quiz $quiz): ScoringEngineContract
    {
        $engine = $quiz->scoring_config['engine'] ?? match ($quiz->type) {
            QuizType::Mbti => 'mbti_axis',
            QuizType::Likert => 'weighted_sum',
            default => 'weighted_sum',
        };

        return match ($engine) {
            'mbti_axis' => app(MbtiAxisScoringEngine::class),
            'weighted_sum' => app(WeightedSumScoringEngine::class),
            default => throw new InvalidArgumentException("Unknown scoring engine: {$engine}"),
        };
    }
}
