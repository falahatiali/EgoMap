<?php

namespace Modules\AetherEngine\Data\External;

readonly class ExerciseListQuery
{
    public function __construct(
        public int $limit = 20,
        public int $offset = 0,
        public ?string $search = null,
        public ?string $gender = null,
        public ?string $category = null,
        public ?string $muscles = null,
        public ?string $difficulty = null,
        public ?string $force = null,
        public ?string $mechanic = null,
        public ?string $grips = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toQueryParameters(): array
    {
        return array_filter([
            'limit' => $this->limit,
            'offset' => $this->offset,
            'search' => $this->search,
            'gender' => $this->gender,
            'category' => $this->category,
            'muscles' => $this->muscles,
            'difficulty' => $this->difficulty,
            'force' => $this->force,
            'mechanic' => $this->mechanic,
            'grips' => $this->grips,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
