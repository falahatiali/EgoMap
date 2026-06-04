<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\NoContact\NoContactTimerService;
use Illuminate\Auth\Events\Login;
use Modules\GamificationEngine\Services\GamificationEngine;

readonly class ClaimGuestNoContactProtocols
{
    public function __construct(
        private NoContactTimerService $timerService,
        private GamificationEngine $gamification,
    ) {}

    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $this->timerService->claimForUser($user);
        $this->gamification->claimForUser($user);
    }
}
