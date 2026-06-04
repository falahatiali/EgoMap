<?php

namespace App\Enums;

enum GhostModeEventType: string
{
    case Emergency = 'emergency';
    case Blackhole = 'blackhole';
    case Slip = 'slip';
}
