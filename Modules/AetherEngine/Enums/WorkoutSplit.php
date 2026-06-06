<?php

namespace Modules\AetherEngine\Enums;

enum WorkoutSplit: string
{
    case FullBody = 'full_body';
    case UpperLower = 'upper_lower';
    case PushPullLegs = 'push_pull_legs';
    case BroSplit = 'bro_split';
}
