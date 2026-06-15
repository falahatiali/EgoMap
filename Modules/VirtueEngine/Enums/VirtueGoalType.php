<?php

namespace Modules\VirtueEngine\Enums;

enum VirtueGoalType: string
{
    /** Reach N consecutive days without repeating the bad habit. */
    case DaysCount = 'days_count';

    /** Log N distinct success moments. */
    case SuccessCount = 'success_count';
}
