<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Recovery\RecoveryJourneyService;
use Illuminate\Auth\Events\Login;

class SyncRecoveryJourneyOnLogin
{
    public function __construct(
        private readonly RecoveryJourneyService $journeyService,
    ) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->journeyService->syncSessionToUser($event->user);
    }
}
