<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\InteractsWithEgoMapPermissions;
use App\Observers\AssignsUuidObserver;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Ai\Concerns\HasConversations;
use Laravel\Cashier\Billable;
use Modules\MissionEngine\Models\MissionEnrollment;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'recovery_phase',
    'breakup_duration',
    'relationship_duration',
    'breakup_initiator',
    'primary_struggle',
    'recovery_triage_completed_at',
    'premium_upsell_deferred_at',
    'premium_upsell_dismiss_count',
])]
#[Hidden(['password', 'remember_token'])]
#[ObservedBy([AssignsUuidObserver::class])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasConversations, HasFactory, HasRoles, InteractsWithEgoMapPermissions, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
            'recovery_triage_completed_at' => 'datetime',
            'premium_upsell_deferred_at' => 'datetime',
            'premium_upsell_dismiss_count' => 'integer',
            'trial_ends_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<QuizSession, $this>
     */
    public function quizSessions(): HasMany
    {
        return $this->hasMany(QuizSession::class)->latest('updated_at');
    }

    /**
     * @return HasMany<NoContactProtocol, $this>
     */
    public function noContactProtocols(): HasMany
    {
        return $this->hasMany(NoContactProtocol::class)->latest('updated_at');
    }

    /**
     * @return HasMany<MissionEnrollment, $this>
     */
    public function missionEnrollments(): HasMany
    {
        return $this->hasMany(MissionEnrollment::class)->latest('last_activity_at');
    }
}
