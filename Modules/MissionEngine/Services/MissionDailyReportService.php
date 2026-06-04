<?php

namespace Modules\MissionEngine\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\MissionEngine\Enums\MissionActivityEvent;
use Modules\MissionEngine\Models\MissionActivityLog;
use Modules\MissionEngine\Models\MissionDailyReport;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Models\MissionMeasurement;

final class MissionDailyReportService
{
    /**
     * @param  array{
     *     report_date: string,
     *     body_weight?: float|null,
     *     mood_score?: int|null,
     *     energy_score?: int|null,
     *     sleep_hours?: float|null,
     *     trained_today?: bool,
     *     nutrition_logged?: bool,
     *     highlights?: string|null,
     *     challenges?: string|null,
     *     notes?: string|null,
     *     workout_session_id?: int|null,
     *     nutrition_day_id?: int|null,
     * }  $data
     */
    public function save(MissionEnrollment $enrollment, User $user, array $data): MissionDailyReport
    {
        return DB::transaction(function () use ($enrollment, $user, $data): MissionDailyReport {
            $reportDate = Carbon::parse($data['report_date'])->toDateString();

            $report = MissionDailyReport::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereDate('report_date', $reportDate)
                ->first();

            $attributes = [
                'body_weight' => $data['body_weight'] ?? null,
                'mood_score' => $data['mood_score'] ?? null,
                'energy_score' => $data['energy_score'] ?? null,
                'sleep_hours' => $data['sleep_hours'] ?? null,
                'trained_today' => $data['trained_today'] ?? false,
                'nutrition_logged' => $data['nutrition_logged'] ?? false,
                'highlights' => $data['highlights'] ?? null,
                'challenges' => $data['challenges'] ?? null,
                'notes' => $data['notes'] ?? null,
                'workout_session_id' => $data['workout_session_id'] ?? null,
                'nutrition_day_id' => $data['nutrition_day_id'] ?? null,
            ];

            if ($report === null) {
                $report = MissionDailyReport::query()->create(array_merge([
                    'enrollment_id' => $enrollment->id,
                    'report_date' => $reportDate,
                ], $attributes));
            } else {
                $report->update($attributes);
            }

            if (isset($data['body_weight']) && is_numeric($data['body_weight'])) {
                MissionMeasurement::query()->create([
                    'enrollment_id' => $enrollment->id,
                    'metric_key' => 'weight',
                    'value' => (float) $data['body_weight'],
                    'unit' => 'kg',
                    'measured_at' => Carbon::parse($reportDate)->startOfDay(),
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            $enrollment->touchActivity();

            MissionActivityLog::query()->create([
                'enrollment_id' => $enrollment->id,
                'user_id' => $user->id,
                'event_type' => MissionActivityEvent::DailyReportSaved,
                'payload' => ['report_uuid' => $report->uuid, 'report_date' => $reportDate],
                'logged_at' => now(),
            ]);

            return $report;
        });
    }

    /**
     * @return LengthAwarePaginator<int, MissionDailyReport>
     */
    public function paginateReports(MissionEnrollment $enrollment, int $perPage = 10, string $pageName = 'dailyPage'): LengthAwarePaginator
    {
        return MissionDailyReport::query()
            ->where('enrollment_id', $enrollment->id)
            ->orderByDesc('report_date')
            ->paginate($perPage, ['*'], $pageName);
    }

    public function findForDate(MissionEnrollment $enrollment, string $date): ?MissionDailyReport
    {
        return MissionDailyReport::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereDate('report_date', Carbon::parse($date)->toDateString())
            ->first();
    }
}
