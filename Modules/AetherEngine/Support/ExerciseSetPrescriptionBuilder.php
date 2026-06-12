<?php

namespace Modules\AetherEngine\Support;

use Modules\AetherEngine\Enums\ExerciseSetType;

class ExerciseSetPrescriptionBuilder
{
    /**
     * @return list<array{
     *     set_number: int,
     *     set_type: string,
     *     target_reps_min: ?int,
     *     target_reps_max: ?int,
     *     target_weight_kg: ?float,
     *     target_rpe: ?int,
     *     target_rir: ?int,
     *     rest_seconds: int,
     *     tempo: ?string,
     *     notes: ?string
     * }>
     */
    public function build(int $setCount, string $reps, int $restSeconds): array
    {
        [$minReps, $maxReps] = $this->parseReps($reps);
        $sets = [];

        for ($number = 1; $number <= max(1, $setCount); $number++) {
            $sets[] = [
                'set_number' => $number,
                'set_type' => $number === 1 && $setCount > 2
                    ? ExerciseSetType::Warmup->value
                    : ExerciseSetType::Working->value,
                'target_reps_min' => $minReps,
                'target_reps_max' => $maxReps,
                'target_weight_kg' => null,
                'target_rpe' => null,
                'target_rir' => 2,
                'rest_seconds' => $restSeconds,
                'tempo' => null,
                'notes' => null,
            ];
        }

        if ($sets !== [] && $sets[0]['set_type'] === ExerciseSetType::Warmup->value) {
            $sets[0]['target_rir'] = 4;
        }

        return $sets;
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    public function parseReps(string $reps): array
    {
        $normalized = trim($reps);

        if (preg_match('/^(\d+)\s*[-–]\s*(\d+)$/', $normalized, $matches) === 1) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        if (preg_match('/^(\d+)/', $normalized, $matches) === 1) {
            $value = (int) $matches[1];

            return [$value, $value];
        }

        return [null, null];
    }

    public function displayReps(?int $min, ?int $max): string
    {
        if ($min === null && $max === null) {
            return '—';
        }

        if ($min !== null && $max !== null && $min !== $max) {
            return $min.'-'.$max;
        }

        return (string) ($max ?? $min);
    }
}
