<?php

namespace App\Services\Quiz;

use App\Models\QuizResponse;
use App\Models\QuizSession;

/**
 * Normalizes quiz session responses into answer maps keyed by question config "key".
 *
 * All scoring engines should use this instead of reading raw responses directly.
 */
class SessionAnswersResolver
{
    /**
     * @return array<string, int|list<int>>
     */
    public function answersByQuestionKey(QuizSession $session): array
    {
        $session->loadMissing(['responses.question']);

        $map = [];

        foreach ($session->responses as $response) {
            if (! $response instanceof QuizResponse) {
                continue;
            }

            $key = (string) ($response->question?->config['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $values = $this->valuesFromResponse($response);
            $map[$key] = count($values) > 1 ? $values : ($values[0] ?? 0);
        }

        return $map;
    }

    /**
     * @return list<int>
     */
    public function valuesForKey(QuizSession $session, string $key): array
    {
        $session->loadMissing(['responses.question']);

        foreach ($session->responses as $response) {
            if (! $response instanceof QuizResponse) {
                continue;
            }

            $responseKey = (string) ($response->question?->config['key'] ?? '');

            if ($responseKey !== $key) {
                continue;
            }

            return $this->valuesFromResponse($response);
        }

        return [];
    }

    public function scalarForKey(QuizSession $session, string $key): int
    {
        $values = $this->valuesForKey($session, $key);

        return $values[0] ?? 0;
    }

    /**
     * @return list<int>
     */
    public function valuesFromAnswer(int|array $answer): array
    {
        if (is_array($answer)) {
            return array_values(array_filter(array_map('intval', $answer), fn (int $value): bool => $value > 0));
        }

        return $answer > 0 ? [(int) $answer] : [];
    }

    /**
     * @return list<int>
     */
    private function valuesFromResponse(QuizResponse $response): array
    {
        $raw = $response->value['value'] ?? 0;

        if (is_array($raw)) {
            return array_values(array_filter(array_map('intval', $raw), fn (int $value): bool => $value > 0));
        }

        if (is_numeric($raw)) {
            return [(int) $raw];
        }

        return [];
    }
}
