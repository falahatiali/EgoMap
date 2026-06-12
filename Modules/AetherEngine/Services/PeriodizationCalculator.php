<?php

namespace Modules\AetherEngine\Services;

class PeriodizationCalculator
{
    /**
     * @return array{sets_multiplier: float, intensity_note: string, phase_label: string}
     */
    public function forWeek(int $weekNumber): array
    {
        $cycleWeek = (($weekNumber - 1) % 4) + 1;

        return match ($cycleWeek) {
            1 => [
                'sets_multiplier' => 1.0,
                'intensity_note' => 'Foundation — focus on form and control.',
                'phase_label' => 'foundation',
            ],
            2 => [
                'sets_multiplier' => 1.0,
                'intensity_note' => 'Progression — add weight or tighter rest.',
                'phase_label' => 'progression',
            ],
            3 => [
                'sets_multiplier' => 1.15,
                'intensity_note' => 'Overload — one extra set on key lifts.',
                'phase_label' => 'overload',
            ],
            default => [
                'sets_multiplier' => 0.75,
                'intensity_note' => 'Deload — lighter volume, full recovery.',
                'phase_label' => 'deload',
            ],
        };
    }

    public function adjustedSets(int $baseSets, int $weekNumber): int
    {
        $multiplier = $this->forWeek($weekNumber)['sets_multiplier'];

        return max(2, (int) round($baseSets * $multiplier));
    }
}
