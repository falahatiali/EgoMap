<?php

namespace App\Livewire\Virtue;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\VirtueEngine\Enums\VirtueRoutineStatus;
use Modules\VirtueEngine\Models\VirtueRoutine;
use Modules\VirtueEngine\Services\VirtueProgressService;

#[Layout('layouts.app')]
class VirtueHub extends Component
{
    public string $locale = 'en';

    public function mount(): void
    {
        $this->locale = session('locale', 'en');
    }

    #[On('routine-updated')]
    public function refreshRoutines(): void
    {
        // Livewire re-renders automatically on property changes; just trigger a re-render.
    }

    public function completeRoutine(int $routineId): void
    {
        $routine = VirtueRoutine::query()
            ->where('user_id', Auth::id())
            ->findOrFail($routineId);

        app(VirtueProgressService::class)->completeRoutine($routine, Auth::user());

        $this->dispatch('routine-updated');
        session()->flash('virtue_status', 'Mission complete! +200 pts 🎉');
    }

    public function render(): View
    {
        $routines = VirtueRoutine::query()
            ->with('habit')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('livewire.virtue.hub', [
            'activeRoutines' => $routines->filter(fn ($r) => $r->status === VirtueRoutineStatus::Active)->values(),
            'completedRoutines' => $routines->filter(fn ($r) => $r->status === VirtueRoutineStatus::Completed)->values(),
        ]);
    }
}
