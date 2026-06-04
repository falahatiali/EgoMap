<?php

namespace Modules\GamificationEngine\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Services\GamificationEngine;

/**
 * JSON HTTP surface over {@see GamificationEngine} for authenticated users.
 *
 * Primary integration in this app is still DI: app(GamificationEngine::class)->dispatch(...).
 */
class GamificationApiController extends Controller
{
    /**
     * GET /api/v1/gamification/wallet
     */
    public function wallet(GamificationEngine $engine, Request $request): JsonResponse
    {
        return response()->json([
            'wallet' => $engine->walletFor(
                $request->user(),
                $request->string('guest_token')->toString() ?: null,
            ),
        ]);
    }

    /**
     * GET /api/v1/gamification/transactions?limit=20
     */
    public function transactions(GamificationEngine $engine, Request $request): JsonResponse
    {
        $limit = min(50, max(1, $request->integer('limit', 10)));

        return response()->json([
            'transactions' => $engine->recentTransactions($request->user(), $limit),
        ]);
    }

    /**
     * POST /api/v1/gamification/dispatch  body: event, metadata?, guest_token?
     */
    public function dispatch(GamificationEngine $engine, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string', Rule::in(GamificationEvent::values())],
            'metadata' => ['nullable', 'array'],
            'guest_token' => ['nullable', 'string', 'max:64'],
        ]);

        $result = $engine->dispatch($validated['event'], [
            'user_id' => $request->user()?->id,
            'guest_token' => $validated['guest_token'] ?? null,
            'metadata' => $validated['metadata'] ?? [],
        ]);

        return response()->json($result);
    }

    /**
     * POST /api/v1/gamification/preview  body: event, metadata?
     */
    public function preview(GamificationEngine $engine, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string', Rule::in(GamificationEvent::values())],
            'metadata' => ['nullable', 'array'],
        ]);

        return response()->json([
            'event' => $validated['event'],
            'matches' => $engine->preview($validated['event'], [
                'metadata' => $validated['metadata'] ?? [],
            ]),
        ]);
    }

    /**
     * GET /api/v1/gamification/shop
     */
    public function shop(GamificationEngine $engine): JsonResponse
    {
        return response()->json(['items' => $engine->activeShopItems()]);
    }

    /**
     * POST /api/v1/gamification/shop/{slug}/purchase
     */
    public function purchaseShop(GamificationEngine $engine, Request $request, string $slug): JsonResponse
    {
        $result = $engine->purchaseShopItem($slug, [
            'user_id' => $request->user()?->id,
            'guest_token' => $request->string('guest_token')->toString() ?: null,
        ]);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    /**
     * POST /api/v1/gamification/perks/{slug}/consume
     */
    public function consumePerk(GamificationEngine $engine, Request $request, string $slug): JsonResponse
    {
        $result = $engine->consumePerk($slug, [
            'user_id' => $request->user()?->id,
            'guest_token' => $request->string('guest_token')->toString() ?: null,
        ]);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }
}
