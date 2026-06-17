<?php

namespace App\Enums;

enum IdeaStatus: string
{
    case Raw = 'raw';
    case Mature = 'mature';
    case Harvested = 'harvested';
}
