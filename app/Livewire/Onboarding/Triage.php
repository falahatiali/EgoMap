<?php

namespace App\Livewire\Onboarding;

use App\Enums\BreakupDuration;
use App\Enums\PrimaryStruggle;
use App\Services\Recovery\RecoveryJourneyService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Triage extends Component
{
    public int $step = 1;

    public ?string $duration = null;

    public ?string $struggle = null;

    public function selectDuration(string $value): void
    {
        if (BreakupDuration::tryFrom($value) === null) {
            return;
        }

        $this->duration = $value;
        $this->step = 2;
    }

    public function selectStruggle(string $value, RecoveryJourneyService $journey): void
    {
        $struggle = PrimaryStruggle::tryFrom($value);

        if ($struggle === null || $this->duration === null) {
            return;
        }

        $duration = BreakupDuration::from($this->duration);
        $this->struggle = $value;
        $journey->saveTriage($duration, $struggle);
        $this->step = 3;
    }

    public function goBack(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function render(RecoveryJourneyService $journey): View
    {
        $recommendation = null;

        if ($this->step === 3 && $this->struggle !== null) {
            $struggle = PrimaryStruggle::from($this->struggle);
            $recommendation = $journey->recommendationForStruggle($struggle);
        }

        return view('livewire.onboarding.triage', [
            'durations' => BreakupDuration::cases(),
            'struggles' => PrimaryStruggle::cases(),
            'recommendation' => $recommendation,
            'phase' => $this->struggle !== null
                ? $journey->phaseForStruggle(PrimaryStruggle::from($this->struggle))
                : null,
        ]);
    }
}
