<?php

namespace Modules\MissionEngine\Services;

use App\Models\User;
use Modules\MissionEngine\Enums\MissionActivityEvent;
use Modules\MissionEngine\Models\MissionActivityLog;
use Modules\MissionEngine\Models\MissionEnrollment;

final class MissionEnrollmentFieldService
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function merge(MissionEnrollment $enrollment, array $values, ?User $actor = null): MissionEnrollment
    {
        $current = $enrollment->field_values ?? [];
        $merged = array_merge($current, $values);

        $enrollment->update([
            'field_values' => $merged,
            'last_activity_at' => now(),
        ]);

        MissionActivityLog::query()->create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $actor?->id,
            'event_type' => MissionActivityEvent::FieldUpdated,
            'payload' => ['keys' => array_keys($values)],
            'logged_at' => now(),
        ]);

        return $enrollment->refresh();
    }
}
