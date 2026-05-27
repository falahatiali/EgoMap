<?php

namespace App\Services\Recovery;

use App\Enums\BreakupDuration;
use App\Enums\BreakupInitiator;
use App\Enums\NoContactStatus;
use App\Enums\PrimaryStruggle;
use App\Enums\RecoveryPhase;
use App\Enums\RelationshipDuration;
use App\Enums\SessionStatus;
use App\Models\NoContactProtocol;
use App\Models\User;
use Carbon\CarbonImmutable;

class RecoveryJourneyService
{
    private const SESSION_KEY = 'recovery.journey';

    private const SESSION_NO_CONTACT_ACTIVATED = 'recovery.no_contact_activated';

    public const ADVANCED_UNLOCK_HOURS = 24;

    public function hasCompletedTriage(?User $user = null): bool
    {
        $user ??= auth()->user();

        if ($user !== null && $user->recovery_triage_completed_at !== null) {
            return true;
        }

        $data = session(self::SESSION_KEY, []);

        return is_array($data) && isset($data['completed_at']);
    }

    public function hasActivatedNoContact(?User $user = null): bool
    {
        if (session(self::SESSION_NO_CONTACT_ACTIVATED, false) === true) {
            return true;
        }

        return $this->activeNoContactProtocol($user) !== null;
    }

    public function markNoContactActivated(): void
    {
        session([self::SESSION_NO_CONTACT_ACTIVATED => true]);
    }

    public function hasAdvancedFeaturesUnlocked(?User $user = null): bool
    {
        $protocol = $this->activeNoContactProtocol($user);

        if ($protocol === null) {
            return false;
        }

        $startedAt = CarbonImmutable::parse($protocol->streak_started_at);

        return $startedAt->diffInHours(CarbonImmutable::now()) >= self::ADVANCED_UNLOCK_HOURS;
    }

    /**
     * @return array{show_explore_links: bool, show_no_contact_link: bool, show_profile_link: bool}
     */
    public function navigationState(?User $user = null): array
    {
        $user ??= auth()->user();

        return [
            'show_explore_links' => $this->hasAdvancedFeaturesUnlocked($user),
            'show_no_contact_link' => $this->hasActivatedNoContact($user),
            'show_profile_link' => $user !== null,
        ];
    }

    public function currentPhase(?User $user = null): ?RecoveryPhase
    {
        $user ??= auth()->user();

        if ($user !== null && $user->recovery_phase !== null) {
            return RecoveryPhase::tryFrom($user->recovery_phase);
        }

        $data = session(self::SESSION_KEY, []);
        $phase = is_array($data) ? ($data['phase'] ?? null) : null;

        return is_string($phase) ? RecoveryPhase::tryFrom($phase) : null;
    }

    public function saveTriage(
        RelationshipDuration $relationshipDuration,
        BreakupDuration $breakupDuration,
        BreakupInitiator $initiator,
        PrimaryStruggle $struggle,
        ?User $user = null,
    ): RecoveryPhase {
        $phase = $this->phaseForStruggle($struggle);
        $payload = [
            'relationship_duration' => $relationshipDuration->value,
            'breakup_duration' => $breakupDuration->value,
            'breakup_initiator' => $initiator->value,
            'primary_struggle' => $struggle->value,
            'phase' => $phase->value,
            'completed_at' => now()->toIso8601String(),
        ];

        session([self::SESSION_KEY => $payload]);

        $user ??= auth()->user();

        if ($user !== null) {
            $user->update([
                'relationship_duration' => $relationshipDuration->value,
                'breakup_duration' => $breakupDuration->value,
                'breakup_initiator' => $initiator->value,
                'primary_struggle' => $struggle->value,
                'recovery_phase' => $phase->value,
                'recovery_triage_completed_at' => now(),
            ]);
        }

        return $phase;
    }

    public function syncSessionToUser(User $user): void
    {
        if (! session()->has(self::SESSION_KEY)) {
            return;
        }

        if ($user->recovery_triage_completed_at !== null) {
            return;
        }

        $data = session(self::SESSION_KEY);

        $user->update([
            'relationship_duration' => $data['relationship_duration'] ?? null,
            'breakup_duration' => $data['breakup_duration'] ?? null,
            'breakup_initiator' => $data['breakup_initiator'] ?? null,
            'primary_struggle' => $data['primary_struggle'] ?? null,
            'recovery_phase' => $data['phase'] ?? null,
            'recovery_triage_completed_at' => isset($data['completed_at']) ? now() : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function actionPlanForStruggle(PrimaryStruggle $struggle): array
    {
        /** @var array<string, array<string, string>> $map */
        $map = config('recovery_journey.action_plans', []);
        $config = $map[$struggle->value] ?? $map[PrimaryStruggle::Stalking->value];

        return [
            'icon' => (string) ($config['icon'] ?? 'hourglass-half'),
            'status_label' => __((string) ($config['status_key'] ?? 'recovery.plan_status_red')),
            'diagnosis_title' => __((string) ($config['diagnosis_title_key'] ?? '')),
            'diagnosis_body' => __((string) ($config['diagnosis_body_key'] ?? '')),
            'priority_title' => __((string) ($config['priority_title_key'] ?? 'recovery.plan_priority_title')),
            'priority_why' => __((string) ($config['priority_why_key'] ?? '')),
            'cta' => __((string) ($config['cta_key'] ?? 'recovery.plan_activate_cta')),
            'url' => route('no-contact'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function recommendationForStruggle(PrimaryStruggle $struggle): array
    {
        $plan = $this->actionPlanForStruggle($struggle);

        return [
            'icon' => $plan['icon'],
            'title' => $plan['priority_title'],
            'body' => $plan['priority_why'],
            'cta' => $plan['cta'],
            'url' => $plan['url'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardState(User $user): array
    {
        $this->syncSessionToUser($user);

        $current = $this->currentPhase($user) ?? RecoveryPhase::Diagnose;
        $struggle = $this->primaryStruggle($user);
        $advancedUnlocked = $this->hasAdvancedFeaturesUnlocked($user);

        return [
            'current_phase' => $current,
            'struggle' => $struggle,
            'steps' => $this->buildSteps($current, $user),
            'show_tests' => $advancedUnlocked,
            'show_no_contact' => $current === RecoveryPhase::Detox,
            'show_ai_coach' => $current === RecoveryPhase::Detox && $this->hasActivatedNoContact($user),
            'show_deliver' => $advancedUnlocked && $current === RecoveryPhase::Deliver,
            'needs_triage' => ! $this->hasCompletedTriage($user),
            'advanced_locked' => ! $advancedUnlocked,
            'primary_tool' => $this->primaryToolForPhase($current, $struggle, $user),
        ];
    }

    public function phaseForStruggle(PrimaryStruggle $struggle): RecoveryPhase
    {
        /** @var array<string, string> $map */
        $map = config('recovery_journey.struggle_phase', []);
        $value = $map[$struggle->value] ?? RecoveryPhase::Detox->value;

        return RecoveryPhase::from($value);
    }

    public function primaryStruggle(?User $user = null): ?PrimaryStruggle
    {
        $user ??= auth()->user();

        if ($user !== null && $user->primary_struggle !== null) {
            return PrimaryStruggle::tryFrom($user->primary_struggle);
        }

        $data = session(self::SESSION_KEY, []);
        $value = is_array($data) ? ($data['primary_struggle'] ?? null) : null;

        return is_string($value) ? PrimaryStruggle::tryFrom($value) : null;
    }

    public function activeNoContactProtocol(?User $user = null): ?NoContactProtocol
    {
        $user ??= auth()->user();

        if ($user !== null) {
            return NoContactProtocol::query()
                ->where('user_id', $user->id)
                ->where('status', NoContactStatus::Active)
                ->latest('updated_at')
                ->first();
        }

        $guestToken = request()->cookie('egomap_guest');

        if (! is_string($guestToken) || $guestToken === '') {
            return null;
        }

        return NoContactProtocol::query()
            ->where('guest_token', $guestToken)
            ->where('status', NoContactStatus::Active)
            ->latest('updated_at')
            ->first();
    }

    /**
     * @return list<array{phase: RecoveryPhase, label: string, unlocked: bool, lock_reason: ?string, is_current: bool}>
     */
    private function buildSteps(RecoveryPhase $current, User $user): array
    {
        $steps = [];

        foreach ([RecoveryPhase::Diagnose, RecoveryPhase::Detox, RecoveryPhase::Deliver] as $phase) {
            $unlocked = $this->isPhaseUnlocked($phase, $current, $user);

            $steps[] = [
                'phase' => $phase,
                'label' => $phase->label(),
                'unlocked' => $unlocked,
                'lock_reason' => $unlocked ? null : $this->lockReason($phase, $current, $user),
                'is_current' => $phase === $current,
            ];
        }

        return $steps;
    }

    private function isPhaseUnlocked(RecoveryPhase $phase, RecoveryPhase $current, User $user): bool
    {
        if ($phase === $current) {
            return true;
        }

        if ($phase->order() < $current->order()) {
            return $this->hasCompletedPhase($phase, $user);
        }

        return false;
    }

    private function hasCompletedPhase(RecoveryPhase $phase, User $user): bool
    {
        return match ($phase) {
            RecoveryPhase::Diagnose => $user->quizSessions()
                ->where('status', SessionStatus::Completed)
                ->exists(),
            RecoveryPhase::Detox => NoContactProtocol::query()
                ->where('user_id', $user->id)
                ->where('status', NoContactStatus::Completed)
                ->exists(),
            RecoveryPhase::Deliver => false,
        };
    }

    private function lockReason(RecoveryPhase $phase, RecoveryPhase $current, User $user): string
    {
        if ($phase->order() > $current->order()) {
            return match ($current) {
                RecoveryPhase::Diagnose => __('recovery.lock_deliver_after_diagnose'),
                RecoveryPhase::Detox => __('recovery.lock_deliver_after_detox'),
                default => __('recovery.lock_phase_default'),
            };
        }

        return match ($phase) {
            RecoveryPhase::Diagnose => __('recovery.lock_diagnose_focus_detox'),
            RecoveryPhase::Detox => __('recovery.lock_detox_after_diagnose'),
            default => __('recovery.lock_phase_default'),
        };
    }

    /**
     * @return array{title: string, body: string, url: string, icon: string}|null
     */
    private function primaryToolForPhase(RecoveryPhase $current, ?PrimaryStruggle $struggle, User $user): ?array
    {
        if ($current === RecoveryPhase::Detox && ! $this->hasActivatedNoContact($user)) {
            return [
                'title' => __('recovery.tool_detox_title'),
                'body' => __('recovery.tool_detox_activate_body'),
                'url' => route('no-contact'),
                'icon' => 'hourglass-half',
            ];
        }

        return match ($current) {
            RecoveryPhase::Diagnose => [
                'title' => __('recovery.tool_diagnose_title'),
                'body' => __('recovery.tool_diagnose_body'),
                'url' => route('quiz.start', ['slug' => 'mbti-personality']),
                'icon' => 'flask',
            ],
            RecoveryPhase::Detox => [
                'title' => __('recovery.tool_detox_title'),
                'body' => __('recovery.tool_detox_body'),
                'url' => route('no-contact'),
                'icon' => 'hourglass-half',
            ],
            RecoveryPhase::Deliver => [
                'title' => __('recovery.tool_deliver_title'),
                'body' => __('recovery.tool_deliver_body'),
                'url' => route('home').'#pricing',
                'icon' => 'dumbbell',
            ],
        };
    }
}
