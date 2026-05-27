<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Quiz\QuizSessionClaimService;
use Illuminate\Auth\Events\Login;

class ClaimGuestQuizSessions
{
    public function __construct(
        private readonly QuizSessionClaimService $claimService,
    ) {}

    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $this->claimService->claimForUser($user);
    }
}
