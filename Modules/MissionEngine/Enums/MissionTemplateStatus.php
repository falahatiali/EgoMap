<?php

namespace Modules\MissionEngine\Enums;

enum MissionTemplateStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
