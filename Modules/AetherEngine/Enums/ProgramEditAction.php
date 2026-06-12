<?php

namespace Modules\AetherEngine\Enums;

enum ProgramEditAction: string
{
    case Replace = 'replace';
    case Edit = 'edit';
    case Skip = 'skip';
    case Add = 'add';
    case Remove = 'remove';
    case Reschedule = 'reschedule';
    case Regenerate = 'regenerate';
}
