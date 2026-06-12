<?php

namespace Modules\AetherEngine\Enums;

enum ExerciseSetType: string
{
    case Warmup = 'warmup';
    case Working = 'working';
    case Drop = 'drop';
    case Backoff = 'backoff';
    case Amrap = 'amrap';
    case Failure = 'failure';
}
