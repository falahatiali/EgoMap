<?php

namespace Modules\AetherEngine\Data\External;

readonly class ExerciseRandomQuery
{
    public function __construct(
        public ?string $category = null,
        public ?string $gender = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toQueryParameters(): array
    {
        return array_filter([
            'category' => $this->category,
            'gender' => $this->gender,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
