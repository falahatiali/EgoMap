<?php

namespace App\Services\NoContact;

use App\DataTransferObjects\GhostMode\GhostModeActor;
use App\Models\User;
use App\Services\Quiz\ApiQuizGuestTokenService;
use App\Services\Quiz\QuizSessionClaimService;
use Illuminate\Http\Request;

class GhostModeActorResolver
{
    public function __construct(
        private readonly ApiQuizGuestTokenService $guestTokens,
        private readonly QuizSessionClaimService $guestSessions,
    ) {}

    public function fromRequest(Request $request): GhostModeActor
    {
        /** @var User|null $user */
        $user = $request->user('sanctum');

        return new GhostModeActor(
            user: $user,
            guestToken: $user !== null ? null : $this->guestTokens->resolveFromRequest($request),
        );
    }

    public function fromWeb(): GhostModeActor
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user !== null) {
            return new GhostModeActor(user: $user, guestToken: null);
        }

        $guestToken = request()->cookie('egomap_guest');

        if (! is_string($guestToken) || $guestToken === '') {
            $guestToken = $this->guestSessions->ensureGuestToken();
        }

        return new GhostModeActor(user: null, guestToken: $guestToken);
    }
}
