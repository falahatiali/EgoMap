<?php

namespace Modules\AetherEngine\Enums;

enum WorkoutSessionStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Skipped = 'skipped';
    case PartiallyCompleted = 'partially_completed';
}
