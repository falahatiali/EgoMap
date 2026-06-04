<?php

namespace Modules\GamificationEngine\Enums;

/** Whether a rule adds value (reward) or applies loss caps (penalty). */
enum GamificationRuleType: string
{
    case Reward = 'reward';
    case Penalty = 'penalty';
}
