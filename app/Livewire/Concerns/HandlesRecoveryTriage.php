<?php

namespace App\Livewire\Concerns;

use App\Enums\BreakupDuration;
use App\Enums\BreakupInitiator;
use App\Enums\PrimaryStruggle;
use App\Enums\RelationshipDuration;
use App\Services\Recovery\RecoveryJourneyService;
use Illuminate\Contracts\View\View;

trait HandlesRecoveryTriage
{
    public const TRIAGE_QUESTION_COUNT = 4;

    private const TRIAGE_DRAFT_SESSION_KEY = 'recovery.triage_draft';

    public int $step = 1;

    public ?string $relationshipDuration = null;

    public ?string $breakupDuration = null;

    public ?string $initiator = null;

    public ?string $struggle = null;

    public function mountRecoveryTriage(RecoveryJourneyService $journey): void
    {
        if ($journey->hasCompletedTriage()) {
            $struggle = $journey->primaryStruggle();

            if ($struggle !== null) {
                $this->struggle = $struggle->value;
                $this->step = 6;
            }

            return;
        }

        /** @var array<string, mixed> $draft */
        $draft = session(self::TRIAGE_DRAFT_SESSION_KEY, []);

        if (! is_array($draft) || ! isset($draft['step'])) {
            return;
        }

        $this->step = max(1, (int) $draft['step']);
        $this->relationshipDuration = is_string($draft['relationship_duration'] ?? null)
            ? $draft['relationship_duration']
            : null;
        $this->breakupDuration = is_string($draft['breakup_duration'] ?? null)
            ? $draft['breakup_duration']
            : null;
        $this->initiator = is_string($draft['initiator'] ?? null)
            ? $draft['initiator']
            : null;
        $this->struggle = is_string($draft['struggle'] ?? null)
            ? $draft['struggle']
            : null;
    }

    public function selectRelationshipDuration(string $value): void
    {
        if (RelationshipDuration::tryFrom($value) === null) {
            return;
        }

        $this->relationshipDuration = $value;
        $this->step = 2;
        $this->syncTriageDraft();
    }

    public function selectBreakupDuration(string $value): void
    {
        if (BreakupDuration::tryFrom($value) === null) {
            return;
        }

        $this->breakupDuration = $value;
        $this->step = 3;
        $this->syncTriageDraft();
    }

    public function selectInitiator(string $value): void
    {
        if (BreakupInitiator::tryFrom($value) === null) {
            return;
        }

        $this->initiator = $value;
        $this->step = 4;
        $this->syncTriageDraft();
    }

    public function selectStruggle(string $value, RecoveryJourneyService $journey): void
    {
        $struggle = PrimaryStruggle::tryFrom($value);

        if ($struggle === null
            || $this->relationshipDuration === null
            || $this->breakupDuration === null
            || $this->initiator === null) {
            return;
        }

        $this->struggle = $value;
        $journey->saveTriage(
            RelationshipDuration::from($this->relationshipDuration),
            BreakupDuration::from($this->breakupDuration),
            BreakupInitiator::from($this->initiator),
            $struggle,
        );
        $this->clearTriageDraft();
        $this->step = 5;
    }

    public function finishDiagnosis(): void
    {
        if ($this->step === 5) {
            $this->step = 6;
        }
    }

    public function goBack(): void
    {
        if ($this->step > 1 && $this->step < 5) {
            $this->step--;
            $this->syncTriageDraft();
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function triageViewData(RecoveryJourneyService $journey): array
    {
        $actionPlan = null;
        $phase = null;

        if ($this->step === 6 && $this->struggle !== null) {
            $struggle = PrimaryStruggle::from($this->struggle);
            $actionPlan = $journey->actionPlanForStruggle($struggle);
            $phase = $journey->phaseForStruggle($struggle);
        }

        return [
            'step' => $this->step,
            'relationshipDurations' => RelationshipDuration::cases(),
            'breakupDurations' => BreakupDuration::cases(),
            'initiators' => BreakupInitiator::cases(),
            'struggles' => PrimaryStruggle::cases(),
            'actionPlan' => $actionPlan,
            'phase' => $phase,
            'questionStep' => min($this->step, static::TRIAGE_QUESTION_COUNT),
            'questionTotal' => static::TRIAGE_QUESTION_COUNT,
        ];
    }

    protected function renderTriageWizard(RecoveryJourneyService $journey): View
    {
        return view('livewire.partials.recovery-triage-wizard', $this->triageViewData($journey));
    }

    private function syncTriageDraft(): void
    {
        if ($this->step >= 5) {
            return;
        }

        session([
            self::TRIAGE_DRAFT_SESSION_KEY => [
                'step' => $this->step,
                'relationship_duration' => $this->relationshipDuration,
                'breakup_duration' => $this->breakupDuration,
                'initiator' => $this->initiator,
                'struggle' => $this->struggle,
            ],
        ]);
    }

    private function clearTriageDraft(): void
    {
        session()->forget(self::TRIAGE_DRAFT_SESSION_KEY);
    }
}
