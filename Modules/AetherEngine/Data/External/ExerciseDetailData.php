<?php

namespace Modules\AetherEngine\Data\External;

readonly class ExerciseDetailData
{
    /**
     * @param  list<string>  $primaryMuscles
     * @param  list<string>  $grips
     * @param  list<string>  $steps
     * @param  list<array<string, mixed>>  $videos
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $source,
        public int $id,
        public string $name,
        public array $primaryMuscles = [],
        public ?string $category = null,
        public ?string $force = null,
        public array $grips = [],
        public ?string $mechanic = null,
        public ?string $difficulty = null,
        public array $steps = [],
        public array $videos = [],
        public ?string $bodymapMaleUrl = null,
        public ?string $bodymapFemaleUrl = null,
        public ?ExerciseMediaData $media = null,
        public array $raw = [],
    ) {}

    public function toMediaData(): ExerciseMediaData
    {
        if ($this->media instanceof ExerciseMediaData) {
            return $this->media;
        }

        return new ExerciseMediaData(
            source: $this->source,
            externalId: (string) $this->id,
            gifUrl: null,
            videoUrl: null,
            imageUrl: null,
            videos: $this->videos,
            metadata: [
                'name' => $this->name,
            ],
        );
    }
}
