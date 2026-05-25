<?php

namespace App\Listeners;

use App\Services\NoContact\NoContactTimerService;
use Illuminate\Auth\Events\Login;

class ClaimGuestNoContactProtocols
{
    public function __construct(
        private readonly NoContactTimerService $timerService,
    ) {}

    public function handle(Login $event): void
    {
        $this->timerService->claimForUser($event->user);
    }
}
