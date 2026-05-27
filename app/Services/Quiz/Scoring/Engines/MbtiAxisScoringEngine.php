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

            foreach ($this->resolveLettersFromResponse($question, $response->value) as $letter) {
                if (isset($letterCounts[$letter])) {
                    $letterCounts[$letter]++;
                }
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
    /**
     * @param  array<string, mixed>  $value
     * @return list<string>
     */
    private function resolveLettersFromResponse(Question $question, array $value): array
    {
        if ($question->type === QuestionType::Likert) {
            $letter = $this->resolveLikertLetter($question, (int) ($value['value'] ?? 0));

            return $letter !== null ? [$letter] : [];
        }

        $raw = $value['value'] ?? null;

        /** @var list<string> $chosen */
        $chosen = [];

        if (is_array($raw)) {
            $chosen = array_values(array_filter(array_map(static fn ($item) => (string) $item, $raw), static fn ($item) => $item !== ''));
        } elseif (is_string($raw) && $raw !== '') {
            $chosen = [$raw];
        }

        if ($chosen === []) {
            return [];
        }

        $question->loadMissing('options');

        $letters = [];

        foreach ($chosen as $valueKey) {
            $option = $question->options->firstWhere('value', $valueKey);

            if ($option === null) {
                continue;
            }

            $letter = strtoupper((string) ($option->scores['letter'] ?? ''));

            if ($letter !== '') {
                $letters[] = $letter;
            }
        }

        return $letters;
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
