<?php

namespace Modules\MissionEngine\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\MissionEngine\Enums\MissionActivityEvent;
use Modules\MissionEngine\Models\MissionActivityLog;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Models\MissionMeasurement;

final class MissionDailyCheckinService
{
    /**
     * @param  array{
     *     workout_focus?: string|null,
     *     duration_minutes?: int|null,
     *     weight?: float|null,
     *     notes?: string|null,
     * }  $data
     */
    public function record(MissionEnrollment $enrollment, User $user, array $data): MissionActivityLog
    {
        $log = MissionActivityLog::query()->create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'event_type' => MissionActivityEvent::DailyCheckin,
            'payload' => [
                'workout_focus' => $data['workout_focus'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'notes' => $data['notes'] ?? null,
            ],
            'logged_at' => now(),
        ]);

        if (isset($data['weight']) && is_numeric($data['weight'])) {
            MissionMeasurement::query()->create([
                'metric_key' => 'weight',
                'value' => (float) $data['weight'],
                'unit' => 'kg',
                'measured_at' => now(),
                'notes' => $data['notes'] ?? null,
                'enrollment_id' => $enrollment->id,
            ]);

            MissionActivityLog::query()->create([
                'enrollment_id' => $enrollment->id,
                'user_id' => $user->id,
                'event_type' => MissionActivityEvent::MeasurementRecorded,
                'payload' => ['metric_key' => 'weight', 'value' => (float) $data['weight']],
                'logged_at' => now(),
            ]);
        }

        $enrollment->update(['last_activity_at' => now()]);

        return $log;
    }

    /**
     * @return list<array{uuid: string, logged_at: string, payload: array<string, mixed>}>
     */
    public function recentCheckins(MissionEnrollment $enrollment, int $limit = 14): array
    {
        return MissionActivityLog::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('event_type', MissionActivityEvent::DailyCheckin)
            ->orderByDesc('logged_at')
            ->limit($limit)
            ->get()
            ->map(fn (MissionActivityLog $log): array => [
                'uuid' => $log->uuid,
                'logged_at' => $log->logged_at->toIso8601String(),
                'payload' => $log->payload ?? [],
            ])
            ->all();
    }

    public function localizedDayLabel(string $dayKey, string $locale): string
    {
        $labels = [
            'sat' => ['en' => 'Saturday', 'fa' => 'شنبه'],
            'sun' => ['en' => 'Sunday', 'fa' => 'یکشنبه'],
            'mon' => ['en' => 'Monday', 'fa' => 'دوشنبه'],
            'tue' => ['en' => 'Tuesday', 'fa' => 'سه‌شنبه'],
            'wed' => ['en' => 'Wednesday', 'fa' => 'چهارشنبه'],
            'thu' => ['en' => 'Thursday', 'fa' => 'پنجشنبه'],
            'fri' => ['en' => 'Friday', 'fa' => 'جمعه'],
        ];

        return $labels[$dayKey][$locale] ?? Str::title($dayKey);
    }
}
