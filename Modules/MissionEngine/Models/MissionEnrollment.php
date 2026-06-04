<?php

namespace Modules\MissionEngine\Models;

use App\Models\User;
use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\MissionEngine\Enums\MissionEnrollmentStatus;

#[Fillable([
    'user_id',
    'template_id',
    'current_phase_id',
    'title',
    'status',
    'progress_percent',
    'started_at',
    'paused_at',
    'completed_at',
    'last_activity_at',
    'template_snapshot',
    'field_values',
    'reminder_settings',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class MissionEnrollment extends Model
{
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'status' => MissionEnrollmentStatus::class,
            'progress_percent' => 'decimal:2',
            'started_at' => 'datetime',
            'paused_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'template_snapshot' => 'array',
            'field_values' => 'array',
            'reminder_settings' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<MissionTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(MissionTemplate::class, 'template_id');
    }

    /**
     * @return BelongsTo<MissionTemplatePhase, $this>
     */
    public function currentPhase(): BelongsTo
    {
        return $this->belongsTo(MissionTemplatePhase::class, 'current_phase_id');
    }

    /**
     * @return HasMany<MissionMeasurement, $this>
     */
    public function measurements(): HasMany
    {
        return $this->hasMany(MissionMeasurement::class, 'enrollment_id');
    }

    /**
     * @return HasMany<MissionActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(MissionActivityLog::class, 'enrollment_id');
    }

    /**
     * @return HasMany<MissionMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(MissionMedia::class, 'enrollment_id');
    }

    /**
     * @return HasMany<MissionWorkoutSession, $this>
     */
    public function workoutSessions(): HasMany
    {
        return $this->hasMany(MissionWorkoutSession::class, 'enrollment_id');
    }

    /**
     * @return HasMany<MissionNutritionDay, $this>
     */
    public function nutritionDays(): HasMany
    {
        return $this->hasMany(MissionNutritionDay::class, 'enrollment_id');
    }

    /**
     * @return HasMany<MissionSupplementProduct, $this>
     */
    public function supplementProducts(): HasMany
    {
        return $this->hasMany(MissionSupplementProduct::class, 'enrollment_id');
    }

    /**
     * @return HasMany<MissionSupplementIntake, $this>
     */
    public function supplementIntakes(): HasMany
    {
        return $this->hasMany(MissionSupplementIntake::class, 'enrollment_id');
    }

    /**
     * @return HasMany<MissionDailyReport, $this>
     */
    public function dailyReports(): HasMany
    {
        return $this->hasMany(MissionDailyReport::class, 'enrollment_id');
    }

    public function touchActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }
}
