<?php

namespace Modules\AetherEngine\Services\ExerciseMedia;

use Modules\AetherEngine\Data\External\ExerciseMediaData;
use Modules\AetherEngine\Models\AetherExercise;

class ExerciseMediaResolver
{
    public function __construct(private ExerciseMediaProviderRegistry $providers) {}

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

        $fetched = $this->fetchMedia($exercise);

        if ($fetched instanceof ExerciseMediaData) {
            $exercise->update($fetched->toExerciseAttributes());

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

    private function fetchMedia(AetherExercise $exercise): ?ExerciseMediaData
    {
        if (is_string($exercise->api_source) && is_string($exercise->api_external_id) && $exercise->api_external_id !== '') {
            $byId = $this->providers->resolveByExternalId($exercise->api_source, $exercise->api_external_id);

            if ($byId instanceof ExerciseMediaData) {
                return $byId;
            }
        }

        return $this->providers->resolveByName($exercise->name);
    }

    private function hasCachedMedia(AetherExercise $exercise): bool
    {
        return $exercise->media_cached_at !== null
            && ($exercise->gif_url !== null || $exercise->video_url !== null || $exercise->image_url !== null);
    }
}
