<?php

namespace App\Services\Pdf\Callbacks;

use App\Models\QuizSession;
use App\Services\Quiz\QuizSessionService;

class MarkQuizSessionReportDelivered
{
    public function __construct(private QuizSessionService $quizSessionService) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __invoke(array $payload): void
    {
        $uuid = (string) ($payload['session_uuid'] ?? '');

        if ($uuid === '') {
            return;
        }

        $session = QuizSession::query()->where('uuid', $uuid)->first();

        if ($session === null) {
            return;
        }

        $this->quizSessionService->markEmailReportSent($session);
    }
}
