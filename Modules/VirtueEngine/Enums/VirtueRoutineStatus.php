<?php

namespace Modules\VirtueEngine\Enums;

enum VirtueRoutineStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
    case Paused = 'paused';
}
