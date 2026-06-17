<?php

namespace App\Http\Controllers\Api;

use App\Enums\IdeaGoalCadence;
use App\Http\Controllers\Controller;
use App\Models\UserIdea;
use App\Services\Mood\IdeaService;
use App\Support\LocaleConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IdeaApiController extends Controller
{
    public function index(Request $request, IdeaService $ideaService): JsonResponse
    {
        $user = $request->user('sanctum');
        abort_unless($user !== null, 401);

        $locale = LocaleConfig::resolve(app()->getLocale());

        return response()->json([
            'locale' => $locale,
            'labels' => $this->labels($locale),
            'garden' => $ideaService->gardenForUser($user),
        ]);
    }

    public function store(Request $request, IdeaService $ideaService): JsonResponse
    {
        $user = $request->user('sanctum');
        abort_unless($user !== null, 401);

        $validated = $request->validate([
            'seed_text' => ['required', 'string', 'max:500'],
            'source' => ['nullable', 'string', 'max:32'],
            'mood_entry_id' => ['nullable', 'integer', 'exists:mood_entries,id'],
        ]);

        $idea = $ideaService->createSeed($user, $validated);

        return response()->json(['idea' => $idea], 201);
    }

    public function mature(Request $request, UserIdea $idea, IdeaService $ideaService): JsonResponse
    {
        $user = $request->user('sanctum');
        abort_unless($user !== null, 401);

        $details = $request->validate([
            'goal_question' => ['nullable', 'string', 'max:500'],
            'first_step' => ['nullable', 'string', 'max:500'],
            'why_it_matters' => ['nullable', 'string', 'max:500'],
        ]);

        $presented = $ideaService->matureIdea($user, $idea, $details ?: null);

        return response()->json(['idea' => $presented]);
    }

    public function harvest(Request $request, UserIdea $idea, IdeaService $ideaService): JsonResponse
    {
        $user = $request->user('sanctum');
        abort_unless($user !== null, 401);

        $validated = $request->validate([
            'goal_cadence' => ['required', 'string', Rule::in(array_column(IdeaGoalCadence::cases(), 'value'))],
        ]);

        $presented = $ideaService->harvestIdea(
            $user,
            $idea,
            IdeaGoalCadence::from($validated['goal_cadence']),
        );

        return response()->json(['idea' => $presented]);
    }

    public function progress(Request $request, UserIdea $idea, IdeaService $ideaService): JsonResponse
    {
        $user = $request->user('sanctum');
        abort_unless($user !== null, 401);

        $validated = $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $presented = $ideaService->updateProgress($user, $idea, (int) $validated['progress']);

        return response()->json(['idea' => $presented]);
    }

    /**
     * @return array<string, string>
     */
    private function labels(string $locale): array
    {
        return [
            'idea_garden_title' => __('mood.idea_garden_title', locale: $locale),
            'idea_garden_subtitle' => __('mood.idea_garden_subtitle', locale: $locale),
            'mature_idea' => __('mood.mature_idea', locale: $locale),
            'harvest_idea' => __('mood.harvest_idea', locale: $locale),
            'check_in' => __('mood.check_in', locale: $locale),
            'no_ideas' => __('mood.no_ideas', locale: $locale),
            'status_raw' => __('mood.idea_status.raw', locale: $locale),
            'status_mature' => __('mood.idea_status.mature', locale: $locale),
            'status_harvested' => __('mood.idea_status.harvested', locale: $locale),
        ];
    }
}
