<?php

namespace App\Livewire\Onboarding;

use App\Livewire\Concerns\HandlesRecoveryTriage;
use App\Services\Recovery\RecoveryJourneyService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guided')]
class Triage extends Component
{
    use HandlesRecoveryTriage;

    public function mount(RecoveryJourneyService $journey): void
    {
        $this->mountRecoveryTriage($journey);
    }

    public function render(RecoveryJourneyService $journey): View
    {
        return view('livewire.onboarding.triage', $this->triageViewData($journey));
    }
}
