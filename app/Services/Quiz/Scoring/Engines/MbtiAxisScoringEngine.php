<?php

namespace App\Services\Quiz\Scoring\Engines;

use App\Enums\QuestionType;
use App\Models\OutcomeProfile;
use App\Models\Question;
use App\Models\QuizSession;
use App\Services\Quiz\Scoring\ScoringEngineContract;

class MbtiAxisScoringEngine implements ScoringEngineContract
{
    /** @var array<string, array{0: string, 1: string}> */
    private const PAIRS = [
        'ei' => ['E', 'I'],
        'sn' => ['S', 'N'],
        'tf' => ['T', 'F'],
        'jp' => ['J', 'P'],
    ];

    public function score(QuizSession $session): array
    {
        $session->load([
            'quiz.dimensions',
            'quiz.outcomeProfiles',
            'responses.question.dimension',
            'responses.question.options',
        ]);

        /** @var array<string, int> $letterCounts */
        $letterCounts = [
            'E' => 0, 'I' => 0, 'S' => 0, 'N' => 0,
            'T' => 0, 'F' => 0, 'J' => 0, 'P' => 0,
        ];

        foreach ($session->responses as $response) {
            $question = $response->question;
            $dimension = $question->dimension;

            if ($dimension === null) {
                continue;
            }

            $axisKey = strtolower($dimension->key);

            if (! isset(self::PAIRS[$axisKey])) {
                continue;
            }

            $letter = $this->resolveLetterFromResponse($question, $response->value);

            if ($letter !== null && isset($letterCounts[$letter])) {
                $letterCounts[$letter]++;
            }
        }

        /** @var array<string, float> $axisScores */
        $axisScores = [];
        $letters = [];

        foreach (self::PAIRS as $key => [$first, $second]) {
            $firstCount = $letterCounts[$first];
            $secondCount = $letterCounts[$second];
            $axisScores[$key] = (float) ($firstCount - $secondCount);
            $letters[$key] = $firstCount >= $secondCount ? $first : $second;
        }

        $typeCode = implode('', $letters);

        $profile = $session->quiz->outcomeProfiles
            ->firstWhere('code', strtolower($typeCode))
            ?? OutcomeProfile::query()
                ->where('quiz_id', $session->quiz_id)
                ->where('code', strtolower($typeCode))
                ->first();

        $locale = $session->locale;

        $dimensions = [];

        foreach (self::PAIRS as $key => [$first, $second]) {
            $firstCount = $letterCounts[$first];
            $secondCount = $letterCounts[$second];
            $total = max($firstCount + $secondCount, 1);

            $dimensions[] = [
                'key' => $key,
                'left_label' => $first,
                'right_label' => $second,
                'preference' => $letters[$key],
                'percent' => (int) round(($firstCount / $total) * 100),
            ];
        }

        $freeReport = [
            'type_code' => $typeCode,
            'title' => $profile?->getTranslation('title', $locale, true) ?? $typeCode,
            'summary' => $profile?->getTranslation('summary', $locale, true) ?? '',
            'content' => $profile?->getTranslation('content', $locale, true) ?? [],
            'letters' => $letters,
            'letter_counts' => $letterCounts,
            'dimension_scores' => $axisScores,
            'dimensions' => $dimensions,
        ];

        return [
            'type_code' => $typeCode,
            'outcome_profile_id' => $profile?->id,
            'dimension_scores' => $axisScores,
            'free_report' => $freeReport,
        ];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function resolveLetterFromResponse(Question $question, array $value): ?string
    {
        if ($question->type === QuestionType::Likert) {
            return $this->resolveLikertLetter($question, (int) ($value['value'] ?? 0));
        }

        $chosen = (string) ($value['value'] ?? '');

        if ($chosen === '') {
            return null;
        }

        $question->loadMissing('options');

        $option = $question->options->firstWhere('value', $chosen);

        if ($option === null) {
            return null;
        }

        return strtoupper((string) ($option->scores['letter'] ?? ''));
    }

    private function resolveLikertLetter(Question $question, int $value): ?string
    {
        if ($value === 3) {
            return null;
        }

        $dimension = $question->dimension;

        if ($dimension === null) {
            return null;
        }

        $axisKey = strtolower($dimension->key);

        if (! isset(self::PAIRS[$axisKey])) {
            return null;
        }

        [$first, $second] = self::PAIRS[$axisKey];
        $pole = strtoupper((string) ($question->config['pole'] ?? $first));
        $prefersPole = $value > 3;

        if ($pole === $first) {
            return $prefersPole ? $first : $second;
        }

        if ($pole === $second) {
            return $prefersPole ? $second : $first;
        }

        return null;
    }
}
