<?php

namespace Modules\MissionEngine\Enums;

enum MissionActivityEvent: string
{
    case Enrolled = 'enrolled';
    case Paused = 'paused';
    case Resumed = 'resumed';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
    case PhaseAdvanced = 'phase_advanced';
    case FieldUpdated = 'field_updated';
    case MeasurementRecorded = 'measurement_recorded';
    case MediaUploaded = 'media_uploaded';
    case DailyCheckin = 'daily_checkin';
    case WorkoutLogged = 'workout_logged';
    case NutritionLogged = 'nutrition_logged';
    case SupplementLogged = 'supplement_logged';
    case DailyReportSaved = 'daily_report_saved';
}
