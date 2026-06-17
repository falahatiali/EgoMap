<?php

namespace App\Services\Mood;

use App\Ai\Agents\MoodSageAgent;
use App\Enums\MoodEmotion;
use App\Models\MoodEntry;
use App\Models\User;
use App\Support\LocaleConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MoodService
{
    /**
     * @return array{
     *     today: ?array<string, mixed>,
     *     heatmap: list<array{date: string, emotion: string, intensity: int}>
     * }
     */
    public function dashboardForUser(User $user): array
    {
        $today = MoodEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->latest('id')
            ->first();

        $heatmap = MoodEntry::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->orderBy('created_at')
            ->get(['emotion', 'intensity', 'created_at'])
            ->map(fn (MoodEntry $entry): array => [
                'date' => $entry->created_at?->toDateString() ?? now()->toDateString(),
                'emotion' => $entry->emotion->value,
                'intensity' => $entry->intensity,
            ])
            ->values()
            ->all();

        return [
            'today' => $today !== null ? $this->presentEntry($today) : null,
            'heatmap' => $heatmap,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function logMood(User $user, MoodEmotion $emotion, int $intensity, string $locale): array
    {
        $intensity = max(1, min(10, $intensity));
        $aiResponse = $this->generateSageResponse($emotion, $intensity, $locale);

        $entry = MoodEntry::query()->create([
            'user_id' => $user->id,
            'emotion' => $emotion,
            'intensity' => $intensity,
            'ai_response' => $aiResponse,
        ]);

        return $this->presentEntry($entry);
    }

    /**
     * @return array{empathy: string, challenge: string, reframe: string, idea_seed: string}
     */
    public function generateSageResponse(MoodEmotion $emotion, int $intensity, string $locale): array
    {
        if (config('mood.ai_sage_enabled')) {
            try {
                $prompt = <<<PROMPT
Emotion: {$emotion->value}
Intensity (1-10): {$intensity}

Generate your mentor response now.
PROMPT;

                $response = (new MoodSageAgent(locale: $locale))->prompt($prompt);
                $structured = $response->structured ?? null;

                if (is_array($structured) && isset($structured['empathy'], $structured['challenge'], $structured['reframe'], $structured['idea_seed'])) {
                    return [
                        'empathy' => (string) $structured['empathy'],
                        'challenge' => (string) $structured['challenge'],
                        'reframe' => (string) $structured['reframe'],
                        'idea_seed' => (string) $structured['idea_seed'],
                    ];
                }
            } catch (\Throwable $exception) {
                Log::warning('Mood sage AI failed', ['error' => $exception->getMessage()]);
            }
        }

        $fallback = __('mood.fallback.'.$emotion->value, locale: $locale);

        return is_array($fallback) ? $fallback : __('mood.fallback.sadness', locale: $locale);
    }

    /**
     * @return array<string, mixed>
     */
    public function presentEntry(MoodEntry $entry): array
    {
        $locale = LocaleConfig::resolve(app()->getLocale());

        return [
            'id' => $entry->id,
            'emotion' => $entry->emotion->value,
            'emotion_label' => __('mood.emotions.'.$entry->emotion->value, locale: $locale),
            'intensity' => $entry->intensity,
            'ai_response' => $entry->ai_response,
            'logged_at' => $entry->created_at?->toIso8601String(),
            'logged_at_human' => $entry->created_at?->diffForHumans(),
        ];
    }

    /**
     * @return Collection<int, MoodEntry>
     */
    public function recentEntries(User $user, int $days = 30): Collection
    {
        return MoodEntry::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->latest('id')
            ->get();
    }
}
