<?php

namespace Modules\AetherEngine\Enums;

enum EquipmentAccess: string
{
    case FullGym = 'full_gym';
    case HomeGym = 'home_gym';
    case ResistanceBands = 'resistance_bands';
    case BodyweightOnly = 'bodyweight_only';
    case Outdoor = 'outdoor';
}
