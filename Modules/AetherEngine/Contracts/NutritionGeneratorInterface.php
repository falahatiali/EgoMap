<?php

namespace Modules\AetherEngine\Contracts;

use Modules\AetherEngine\Data\MetabolicTargets;
use Modules\AetherEngine\Data\NutritionDayPlan;
use Modules\AetherEngine\Models\AetherUserProfile;

interface NutritionGeneratorInterface
{
    /**
     * @return array<int, NutritionDayPlan>
     */
    public function generate(AetherUserProfile $profile, MetabolicTargets $targets): array;
}
