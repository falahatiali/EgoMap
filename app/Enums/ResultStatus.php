<?php

namespace App\Enums;

enum ResultStatus: string
{
    case Pending = 'pending';
    case Generating = 'generating';
    case Ready = 'ready';
    case Failed = 'failed';
}
