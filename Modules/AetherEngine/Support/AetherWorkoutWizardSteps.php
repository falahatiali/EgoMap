<?php

namespace Modules\AetherEngine\Support;

final class AetherWorkoutWizardSteps
{
    /** @var list<string> */
    public const STEPS = [
        'gender',
        'age',
        'height',
        'weight',
        'current_body',
        'target_body',
        'goal',
        'gym_confidence',
        'days',
        'session',
        'equipment',
        'injuries',
        'style',
        'motivation',
        'review',
    ];

    public static function count(): int
    {
        return count(self::STEPS);
    }

    public static function keyForStep(int $step): string
    {
        return self::STEPS[max(0, min($step - 1, self::count() - 1))];
    }

    public static function isReviewStep(int $step): bool
    {
        return self::keyForStep($step) === 'review';
    }
}
