<?php

namespace Modules\MissionEngine\Enums;

enum MissionFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Date = 'date';
    case Datetime = 'datetime';
    case Time = 'time';
    case Select = 'select';
    case MultiSelect = 'multiselect';
    case Url = 'url';
    case File = 'file';
    case Image = 'image';
    case Json = 'json';
}
