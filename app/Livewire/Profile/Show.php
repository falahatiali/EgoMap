<?php

namespace App\Livewire\Profile;

use App\Enums\SessionStatus;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\Quiz\QuizSessionClaimService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
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

    public function mount(QuizSessionClaimService $claimService): void
    {
        $this->user = Auth::user();
        $claimService->claimForUser($this->user);
    }

    public function setFilter(string $filter): void
    {
        if (! in_array($filter, ['all', 'in_progress', 'completed'], true)) {
            return;
        }

        $this->filter = $filter;
    }

    public function render(): View
    {
        $sessions = $this->loadSessions();

        $inProgress = $sessions->where('status', SessionStatus::InProgress)->values();
        $completed = $sessions->where('status', SessionStatus::Completed)->values();

        $filtered = match ($this->filter) {
            'in_progress' => $inProgress,
            'completed' => $completed,
            default => $sessions,
        };

        return view('livewire.profile.show', [
            'sessions' => $sessions,
            'filteredSessions' => $filtered,
            'inProgressSessions' => $inProgress,
            'completedSessions' => $completed,
            'totalCompleted' => $completed->count(),
            'totalInProgress' => $inProgress->count(),
            'totalTests' => $sessions->count(),
        ]);
    }

    /**
     * @return Collection<int, QuizSession>
     */
    private function loadSessions(): Collection
    {
        return QuizSession::query()
            ->where('user_id', $this->user->id)
            ->with([
                'quiz' => fn ($query) => $query->withCount([
                    'questions' => fn ($questions) => $questions->where('is_active', true),
                ]),
                'result.outcomeProfile',
            ])
            ->latest('updated_at')
            ->get();
    }
}
