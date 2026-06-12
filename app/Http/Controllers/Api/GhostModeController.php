<?php

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\GhostMode\GhostModeActor;
use App\Http\Controllers\Controller;
use App\Services\NoContact\GhostModeActorResolver;
use App\Services\NoContact\GhostModeOrchestrator;
use App\Services\Quiz\ApiQuizGuestTokenService;
use App\Support\LocaleConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class GhostModeController extends Controller
{
    public function show(
        Request $request,
        GhostModeActorResolver $actorResolver,
        GhostModeOrchestrator $orchestrator,
        ApiQuizGuestTokenService $guestTokens,
    ): JsonResponse {
        [$actor, $issuedGuestToken] = $this->resolveActor($request, $actorResolver, $guestTokens);

        $payload = $orchestrator->bootstrapForApi(
            $actor,
            LocaleConfig::resolve($request->header('Accept-Language')),
        );

        if ($issuedGuestToken !== null) {
            $payload['guest_token'] = $issuedGuestToken;
        }

        return response()->json($payload);
    }

    public function startProtocol(
        Request $request,
        GhostModeActorResolver $actorResolver,
        GhostModeOrchestrator $orchestrator,
        ApiQuizGuestTokenService $guestTokens,
    ): JsonResponse {
        $validated = $request->validate([
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        [$actor, $issuedGuestToken] = $this->resolveActor($request, $actorResolver, $guestTokens);

        try {
            $payload = $orchestrator->startProtocolForApi(
                $actor,
                (int) $validated['duration_days'],
                LocaleConfig::resolve($request->header('Accept-Language')),
            );
        } catch (InvalidArgumentException) {
            return response()->json([
                'message' => __('no_contact.invalid_duration'),
            ], 422);
        }

        if ($issuedGuestToken !== null) {
            $payload['guest_token'] = $issuedGuestToken;
        }

        return response()->json($payload, 201);
    }

    /**
     * @return array{0: GhostModeActor, 1: ?string}
     */
    private function resolveActor(
        Request $request,
        GhostModeActorResolver $actorResolver,
        ApiQuizGuestTokenService $guestTokens,
    ): array {
        $actor = $actorResolver->fromRequest($request);
        $issuedGuestToken = null;

        if (! $actor->isAuthenticated() && $actor->guestToken === null) {
            $issuedGuestToken = $guestTokens->issue();
            $actor = new GhostModeActor(user: null, guestToken: $issuedGuestToken);
        }

        return [$actor, $issuedGuestToken];
    }
}
