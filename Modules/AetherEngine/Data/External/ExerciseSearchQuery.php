<?php

namespace Modules\AetherEngine\Data\External;

readonly class ExerciseSearchQuery
{
    public function __construct(
        public string $term,
        public int $limit = 20,
        public int $offset = 0,
        public ?string $difficulty = null,
        public ?string $category = null,
        public ?string $gender = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toQueryParameters(): array
    {
        return array_filter([
            'q' => $this->term,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'difficulty' => $this->difficulty,
            'category' => $this->category,
            'gender' => $this->gender,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
