<?php

namespace App\Services\Quiz\RebootProtocol;

use App\Models\Question;
use App\Models\QuizSession;
use App\Services\Quiz\SessionAnswersResolver;
use App\Support\RebootProtocolQuiz;

class RebootProtocolFlow
{
    public function __construct(
        private readonly SessionAnswersResolver $answers,
    ) {}

    public function isRebootQuiz(?string $slug): bool
    {
        return $slug === RebootProtocolQuiz::SLUG;
    }

    public function isCrisis(QuizSession $session): bool
    {
        return (bool) ($session->meta['crisis'] ?? false);
    }

    public function shouldPromptSafety(QuizSession $session, Question $question): bool
    {
        if ($this->isCrisis($session) || ($session->meta['safety_completed'] ?? false)) {
            return false;
        }

        $key = (string) ($question->config['key'] ?? '');

        if ($key !== RebootProtocolQuiz::IMMEDIATE_RISK_KEY) {
            return false;
        }

        return in_array(6, $this->answers->valuesForKey($session, $key), true);
    }

    /**
     * @return array{crisis: bool}
     */
    public function recordSafetyAnswer(QuizSession $session, int $value): array
    {
        $meta = $session->meta ?? [];
        $meta['safety_answer'] = $value;
        $meta['safety_completed'] = true;

        if ($value === 4) {
            $meta['crisis'] = true;
            $session->update(['meta' => $meta]);

            return ['crisis' => true];
        }

        $meta['crisis'] = false;
        $session->update(['meta' => $meta]);

        return ['crisis' => false];
    }

    public function clearSafetyPrompt(QuizSession $session): void
    {
        $meta = $session->meta ?? [];
        unset($meta['safety_pending']);
        $session->update(['meta' => $meta]);
    }

    public function markSafetyPending(QuizSession $session): void
    {
        $meta = $session->meta ?? [];
        $meta['safety_pending'] = true;
        $session->update(['meta' => $meta]);
    }

    public function isSafetyPending(QuizSession $session): bool
    {
        return (bool) ($session->meta['safety_pending'] ?? false);
    }

    /**
     * @return array<string, int|list<int>>
     */
    public function answersByKey(QuizSession $session): array
    {
        return $this->answers->answersByQuestionKey($session);
    }
}
