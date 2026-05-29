<?php

namespace Modules\MissionEngine\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Modules\MissionEngine\Enums\MissionActivityEvent;
use Modules\MissionEngine\Enums\MissionEnrollmentStatus;
use Modules\MissionEngine\Enums\MissionTemplateStatus;
use Modules\MissionEngine\Models\MissionActivityLog;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Models\MissionTemplate;

final class MissionEnrollmentService
{
    public function __construct(
        private readonly MissionTemplateSnapshotBuilder $snapshotBuilder,
    ) {}

    public function enroll(User $user, MissionTemplate $template, ?string $title = null): MissionEnrollment
    {
        if ($template->status !== MissionTemplateStatus::Published) {
            throw new \InvalidArgumentException('Only published mission templates can be enrolled.');
        }

        $snapshot = $this->snapshotBuilder->build($template);
        $firstPhaseId = Arr::get($snapshot, 'phases.0.id');

        $enrollment = MissionEnrollment::query()->create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'current_phase_id' => $firstPhaseId,
            'title' => $title,
            'status' => MissionEnrollmentStatus::Active,
            'progress_percent' => 0,
            'started_at' => now(),
            'last_activity_at' => now(),
            'template_snapshot' => $snapshot,
            'field_values' => $this->initialFieldValues($snapshot),
        ]);

        MissionActivityLog::query()->create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'event_type' => MissionActivityEvent::Enrolled,
            'payload' => [
                'template_slug' => $template->slug,
                'template_version' => $template->version,
            ],
            'logged_at' => now(),
        ]);

        return $enrollment;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function initialFieldValues(array $snapshot): array
    {
        $values = [];

        foreach ($snapshot['fields'] ?? [] as $field) {
            $key = $field['field_key'] ?? null;

            if (! is_string($key) || $key === '') {
                continue;
            }

            $values[$key] = $field['default_value'] ?? null;
        }

        return $values;
    }
}
