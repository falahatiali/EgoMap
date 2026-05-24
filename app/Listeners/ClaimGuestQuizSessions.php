<?php

namespace App\Listeners;

use App\Services\Quiz\QuizSessionClaimService;
use Illuminate\Auth\Events\Login;

class ClaimGuestQuizSessions
{
    public function __construct(
        private readonly QuizSessionClaimService $claimService,
    ) {}

    public function handle(Login $event): void
    {
        $this->claimService->claimForUser($event->user);
    }
}
