<?php

namespace App\Livewire\Profile;

use App\Models\User;
use App\Services\Profile\UserQuizHistoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public User $user;

    #[Url(as: 'tab', history: true)]
    public string $filter = 'all';

    public function mount(UserQuizHistoryService $historyService): void
    {
        $this->user = Auth::user();
    }

    public function setFilter(string $filter): void
    {
        if (! in_array($filter, ['all', 'in_progress', 'completed'], true)) {
            return;
        }

        $this->filter = $filter;
    }

    public function render(UserQuizHistoryService $historyService): View
    {
        $records = $historyService->recordsForUser($this->user);

        $inProgress = $records->where('is_in_progress', true)->values();
        $completed = $records->where('is_in_progress', false)->values();

        $filtered = match ($this->filter) {
            'in_progress' => $inProgress,
            'completed' => $completed,
            default => $records,
        };

        return view('livewire.profile.show', [
            'records' => $records,
            'filteredRecords' => $filtered,
            'inProgressRecords' => $inProgress,
            'completedRecords' => $completed,
            'totalCompleted' => $completed->count(),
            'totalInProgress' => $inProgress->count(),
            'totalTests' => $records->count(),
        ]);
    }
}
