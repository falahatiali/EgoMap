<?php

namespace Modules\MissionEngine\Enums;

enum MissionCapabilityKey: string
{
    case Schedule = 'schedule';
    case Nutrition = 'nutrition';
    case Supplement = 'supplement';
    case Equipment = 'equipment';
    case Measurement = 'measurement';
    case Content = 'content';
    case Task = 'task';
    case Mindset = 'mindset';
    case Registration = 'registration';
    case Finance = 'finance';
    case Checklist = 'checklist';
}
