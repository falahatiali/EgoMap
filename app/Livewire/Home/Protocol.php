<?php

namespace App\Livewire\Home;

use App\Livewire\Concerns\HandlesRecoveryTriage;
use App\Services\Recovery\RecoveryJourneyService;
use App\Support\RebootProtocolQuiz;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.protocol')]
class Protocol extends Component
{
    use HandlesRecoveryTriage;

    public string $screen = 'landing';

    public function mount(RecoveryJourneyService $journey): void
    {
        $this->mountRecoveryTriage($journey);

        if (request()->routeIs('onboarding')) {
            $this->screen = 'triage';

            return;
        }

        if ($journey->hasCompletedTriage() && $this->step >= 6) {
            $this->screen = 'triage';
        }
    }

    public function beginProtocol(): void
    {
        $this->startCheckIn();
    }

    public function startCheckIn(): void
    {
        $this->redirect(route('quiz.start', ['slug' => RebootProtocolQuiz::SLUG, 'locale' => app()->getLocale()]), navigate: true);
    }

    public function render(RecoveryJourneyService $journey): View
    {
        return view('livewire.home.protocol', [
            'screen' => $this->screen,
            'triage' => $this->screen === 'triage'
                ? $this->triageViewData($journey)
                : null,
            'ncPreviewDay' => 1,
            'ncPreviewTotal' => 90,
        ]);
    }
}
