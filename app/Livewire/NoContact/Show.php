<?php

namespace App\Livewire\NoContact;

use App\Services\NoContact\NoContactTimerService;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public int $selectedDays = 0;

    public bool $confirmSlip = false;

    public bool $showSetup = false;

    public function mount(NoContactTimerService $timerService): void
    {
        $this->selectedDays = $timerService->recommendedDays();
    }

    public function startProtocol(NoContactTimerService $timerService): void
    {
        try {
            $timerService->start($this->selectedDays);
            $this->confirmSlip = false;
            $this->showSetup = false;
        } catch (InvalidArgumentException) {
            $this->addError('duration', __('no_contact.invalid_duration'));
        }
    }

    public function recordSlip(NoContactTimerService $timerService): void
    {
        if (! $this->confirmSlip) {
            $this->confirmSlip = true;

            return;
        }

        try {
            $timerService->recordSlip();
            $this->confirmSlip = false;
        } catch (InvalidArgumentException) {
            $this->confirmSlip = false;
            $this->addError('slip', __('no_contact.no_active_protocol'));
        }
    }

    public function cancelSlipConfirm(): void
    {
        $this->confirmSlip = false;
    }

    public function restartAfterComplete(NoContactTimerService $timerService): void
    {
        $this->selectedDays = $timerService->recommendedDays();
        $this->showSetup = true;
        $this->confirmSlip = false;
    }

    public function render(NoContactTimerService $timerService): View
    {
        $state = $timerService->displayState();

        if ($this->showSetup && ($state['mode'] ?? '') === 'completed') {
            $state['mode'] = 'setup';
        }

        return view('livewire.no-contact.show', [
            'state' => $state,
            'presets' => $timerService->presets(),
        ]);
    }
}
