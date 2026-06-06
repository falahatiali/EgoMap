<?php

namespace Modules\AetherEngine\Contracts;

use Modules\AetherEngine\Data\WorkoutDayPlan;
use Modules\AetherEngine\Enums\WorkoutSplit;
use Modules\AetherEngine\Models\AetherUserProfile;

interface WorkoutGeneratorInterface
{
    /**
     * @return array{split: WorkoutSplit, days: array<int, WorkoutDayPlan>}
     */
    public function generate(AetherUserProfile $profile): array;
}
