<?php

namespace Modules\AetherEngine\Enums;

enum ProgramEditSource: string
{
    case User = 'user';
    case Ai = 'ai';
    case Coach = 'coach';
    case System = 'system';
}
