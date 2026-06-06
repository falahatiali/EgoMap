<?php

namespace Modules\AetherEngine\Enums;

enum ProgramStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
