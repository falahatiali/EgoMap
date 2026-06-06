<?php

namespace Modules\AetherEngine\Data;

readonly class WorkoutDayPlan
{
    /**
     * @param  array<int, ExercisePrescription>  $exercises
     */
    public function __construct(
        public int $dayIndex,
        public string $label,
        public string $focus,
        public array $exercises,
        public string $warmup = '5–10 min light cardio + dynamic mobility.',
        public string $cooldown = '5 min stretching for trained muscle groups.',
        public ?string $motivation = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'day_index' => $this->dayIndex,
            'label' => $this->label,
            'focus' => $this->focus,
            'warmup' => $this->warmup,
            'cooldown' => $this->cooldown,
            'motivation' => $this->motivation,
            'exercises' => array_map(
                static fn (ExercisePrescription $exercise): array => $exercise->toArray(),
                $this->exercises,
            ),
        ];
    }
}
