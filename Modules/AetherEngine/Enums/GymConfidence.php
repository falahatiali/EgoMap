<?php

namespace Modules\AetherEngine\Enums;

enum GymConfidence: string
{
    case NeverBeen = 'never_been';
    case LostUnsure = 'lost_unsure';
    case BasicsUnsure = 'basics_unsure';
    case ComfortableGuidance = 'comfortable_guidance';
    case ConfidentPlan = 'confident_plan';
}
