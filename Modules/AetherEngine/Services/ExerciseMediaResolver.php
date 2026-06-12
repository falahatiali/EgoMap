<?php

namespace Modules\AetherEngine\Services;

use Modules\AetherEngine\Models\AetherExercise;

class ExerciseMediaResolver
{
    public function __construct(private WorkoutXApiClient $workoutX) {}

    /**
     * @return array{gif_url: ?string, video_url: ?string, image_url: ?string}
     */
    public function resolve(AetherExercise $exercise): array
    {
        if ($this->hasCachedMedia($exercise)) {
            return [
                'gif_url' => $exercise->gif_url,
                'video_url' => $exercise->video_url,
                'image_url' => $exercise->image_url,
            ];
        }

        $fetched = $this->workoutX->fetchByName($exercise->name);

        if ($fetched !== null) {
            $exercise->update([
                'gif_url' => $fetched['gif_url'] ?? null,
                'video_url' => $fetched['video_url'] ?? null,
                'image_url' => $fetched['image_url'] ?? null,
                'api_source' => $fetched['api_source'] ?? 'workoutx',
                'api_external_id' => $fetched['api_external_id'] ?? null,
                'media_cached_at' => now(),
            ]);

            return [
                'gif_url' => $exercise->gif_url,
                'video_url' => $exercise->video_url,
                'image_url' => $exercise->image_url,
            ];
        }

        return [
            'gif_url' => null,
            'video_url' => null,
            'image_url' => null,
        ];
    }

    public function resolveBySlug(string $slug): ?array
    {
        $exercise = AetherExercise::query()->where('slug', $slug)->first();

        if ($exercise === null) {
            return null;
        }

        return $this->resolve($exercise);
    }

    private function hasCachedMedia(AetherExercise $exercise): bool
    {
        return $exercise->media_cached_at !== null
            && ($exercise->gif_url !== null || $exercise->video_url !== null || $exercise->image_url !== null);
    }
}
