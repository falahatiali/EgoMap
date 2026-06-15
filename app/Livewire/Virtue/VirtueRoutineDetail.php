<?php

namespace App\Livewire\Virtue;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\VirtueEngine\Models\VirtueRoutine;
use Modules\VirtueEngine\Services\VirtueProgressService;

#[Layout('layouts.app')]
class VirtueRoutineDetail extends Component
{
    public VirtueRoutine $routine;

    public string $locale = 'en';

    public bool $showSuccessModal = false;

    public bool $showSlipModal = false;

    public string $situation = '';

    public string $emotionalState = '';

    public string $whatHappened = '';

    public bool $isLogging = false;

    public ?string $aiEncouragement = null;

    public ?array $slipResult = null;

    public function mount(int $routineId): void
    {
        $this->locale = session('locale', 'en');
        $this->routine = VirtueRoutine::query()
            ->with(['habit', 'successLogs' => fn ($q) => $q->latest()->limit(10), 'slipLogs' => fn ($q) => $q->latest()->limit(5)])
            ->where('user_id', Auth::id())
            ->findOrFail($routineId);
    }

    public function openSuccessModal(): void
    {
        $this->showSuccessModal = true;
        $this->situation = '';
        $this->emotionalState = '';
        $this->aiEncouragement = null;
    }

    public function openSlipModal(): void
    {
        $this->showSlipModal = true;
        $this->whatHappened = '';
        $this->slipResult = null;
    }

    public function closeModals(): void
    {
        $this->showSuccessModal = false;
        $this->showSlipModal = false;
    }

    public function logSuccess(): void
    {
        $this->isLogging = true;

        try {
            $result = app(VirtueProgressService::class)->logSuccess(
                $this->routine,
                Auth::user(),
                [
                    'situation' => $this->situation ?: null,
                    'emotional_state' => $this->emotionalState ?: null,
                ],
            );

            $this->aiEncouragement = $result['success_log']->ai_encouragement;
            $this->routine->refresh();
            $this->routine->load('habit', 'successLogs', 'slipLogs');
        } catch (\Throwable) {
            session()->flash('virtue_error', 'Could not log success. Try again.');
        } finally {
            $this->isLogging = false;
        }
    }

    public function closeSuccessModal(): void
    {
        $this->showSuccessModal = false;
        $this->aiEncouragement = null;
    }

    public function logSlip(): void
    {
        $this->isLogging = true;

        try {
            $result = app(VirtueProgressService::class)->logSlip(
                $this->routine,
                Auth::user(),
                ['what_happened' => $this->whatHappened ?: null],
            );

            $this->slipResult = [
                'ai_response' => $result['ai_response'] ?? null,
                'punishment_suggestions' => $result['punishment_suggestions'] ?? [],
            ];

            $this->routine->refresh();
            $this->routine->load('habit', 'successLogs', 'slipLogs');
        } catch (\Throwable) {
            session()->flash('virtue_error', 'Could not log slip. Try again.');
        } finally {
            $this->isLogging = false;
        }
    }

    public function render(): View
    {
        return view('livewire.virtue.routine-detail');
    }
}
