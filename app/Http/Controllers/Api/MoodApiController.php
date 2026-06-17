<?php

namespace App\Http\Controllers\Api;

use App\Enums\MoodEmotion;
use App\Http\Controllers\Controller;
use App\Services\Mood\MoodService;
use App\Support\LocaleConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MoodApiController extends Controller
{
    public function show(Request $request, MoodService $moodService): JsonResponse
    {
        $user = $request->user('sanctum');
        abort_unless($user !== null, 401);

        $locale = LocaleConfig::resolve(app()->getLocale());
        $dashboard = $moodService->dashboardForUser($user);

        return response()->json([
            'locale' => $locale,
            'labels' => $this->labels($locale),
            'emotions' => collect(MoodEmotion::cases())->map(fn (MoodEmotion $emotion): array => [
                'value' => $emotion->value,
                'label' => __('mood.emotions.'.$emotion->value, locale: $locale),
            ])->values()->all(),
            'today' => $dashboard['today'],
            'heatmap' => $dashboard['heatmap'],
        ]);
    }

    public function store(Request $request, MoodService $moodService): JsonResponse
    {
        $user = $request->user('sanctum');
        abort_unless($user !== null, 401);

        $validated = $request->validate([
            'emotion' => ['required', 'string', Rule::in(MoodEmotion::values())],
            'intensity' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $locale = LocaleConfig::resolve(app()->getLocale());
        $entry = $moodService->logMood(
            $user,
            MoodEmotion::from($validated['emotion']),
            (int) $validated['intensity'],
            $locale,
        );

        return response()->json([
            'locale' => $locale,
            'labels' => $this->labels($locale),
            'entry' => $entry,
        ], 201);
    }

    /**
     * @return array<string, string>
     */
    private function labels(string $locale): array
    {
        return [
            'compass_title' => __('mood.compass_title', locale: $locale),
            'compass_subtitle' => __('mood.compass_subtitle', locale: $locale),
            'intensity_label' => __('mood.intensity_label', locale: $locale),
            'save_mood' => __('mood.save_mood', locale: $locale),
            'save_to_ideas' => __('mood.save_to_ideas', locale: $locale),
            'dismiss' => __('mood.dismiss', locale: $locale),
        ];
    }
}
