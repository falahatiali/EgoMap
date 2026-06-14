<?php

namespace Modules\AetherEngine\Data\External;

readonly class ExerciseMediaData
{
    /**
     * @param  list<array<string, mixed>>  $videos
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $source,
        public ?string $externalId,
        public ?string $gifUrl,
        public ?string $videoUrl,
        public ?string $imageUrl,
        public array $videos = [],
        public array $metadata = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toExerciseAttributes(): array
    {
        return [
            'gif_url' => $this->gifUrl,
            'video_url' => $this->videoUrl,
            'image_url' => $this->imageUrl,
            'api_source' => $this->source,
            'api_external_id' => $this->externalId,
            'media_cached_at' => now(),
        ];
    }
}
