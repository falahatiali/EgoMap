<?php

namespace Modules\MissionEngine\Enums;

enum MissionEnrollmentStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
}
