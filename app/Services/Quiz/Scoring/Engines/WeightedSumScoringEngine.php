<?php

namespace App\Services\Quiz\Scoring\Engines;

use App\Models\OutcomeProfile;
use App\Models\QuizSession;
use App\Services\Quiz\Scoring\ScoringEngineContract;

class WeightedSumScoringEngine implements ScoringEngineContract
{
    public function score(QuizSession $session): array
    {
        $session->load([
            'quiz.dimensions',
            'quiz.outcomeProfiles',
            'responses.question.dimension',
        ]);

        /** @var array<string, float> $dimensionScores */
        $dimensionScores = [];

        foreach ($session->quiz->dimensions as $dimension) {
            $dimensionScores[$dimension->key] = 0.0;
        }

        foreach ($session->responses as $response) {
            $question = $response->question;
            $dimension = $question->dimension;

            if ($dimension === null) {
                continue;
            }

            $value = (int) ($response->value['value'] ?? 0);
            $weight = (float) ($question->config['weight'] ?? 1);
            $reverse = (bool) ($question->config['reverse_scored'] ?? false);

            $score = $reverse ? (6 - $value) : $value;
            $dimensionScores[$dimension->key] = ($dimensionScores[$dimension->key] ?? 0) + ($score * $weight);
        }

        $profile = $this->matchProfile($session, $dimensionScores);
        $locale = $session->locale;

        $freeReport = [
            'type_code' => $profile?->code,
            'title' => $profile?->getTranslation('title', $locale, true) ?? '',
            'summary' => $profile?->getTranslation('summary', $locale, true) ?? '',
            'dimension_scores' => $dimensionScores,
        ];

        return [
            'type_code' => $profile?->code,
            'outcome_profile_id' => $profile?->id,
            'dimension_scores' => $dimensionScores,
            'free_report' => $freeReport,
        ];
    }

    /**
     * @param  array<string, float>  $dimensionScores
     */
    private function matchProfile(QuizSession $session, array $dimensionScores): ?OutcomeProfile
    {
        foreach ($session->quiz->outcomeProfiles as $profile) {
            $rules = $profile->match_rules ?? [];

            if ($rules === []) {
                continue;
            }

            $matched = true;

            foreach ($rules as $dimensionKey => $rule) {
                $score = $dimensionScores[$dimensionKey] ?? 0;

                if (isset($rule['min']) && $score < $rule['min']) {
                    $matched = false;
                    break;
                }

                if (isset($rule['max']) && $score > $rule['max']) {
                    $matched = false;
                    break;
                }
            }

            if ($matched) {
                return $profile;
            }
        }

        return $session->quiz->outcomeProfiles->first();
    }
}
