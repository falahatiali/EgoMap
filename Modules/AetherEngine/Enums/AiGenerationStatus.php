<?php

namespace Modules\AetherEngine\Enums;

enum AiGenerationStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case ValidationFailed = 'validation_failed';
}
