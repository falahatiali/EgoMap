<?php

namespace App\Livewire\Profile;

use App\Enums\SessionStatus;
use App\Models\User;
use App\Models\QuizSession;
use App\Services\Auth\UserSessionService;
use App\Services\Profile\UserQuizHistoryService;
use App\Services\Recovery\RecoveryJourneyService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public ?User $user;

    #[Url(as: 'tab', history: true)]
    public string $filter = 'all';

    public string $revokePassword = '';

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

    public function deleteSession(string $uuid): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);

        $session = QuizSession::query()
            ->where('uuid', $uuid)
            ->firstOrFail();

        $ownsSession = $session->user_id === $user->id
            || ($session->email !== null && $user->email !== null && $session->email === $user->email);

        abort_unless($ownsSession, 403);

        // Soft delete behavior: mark the session as abandoned so it disappears from history.
        $session->update(['status' => SessionStatus::Abandoned]);

        $this->redirect(route('profile').'#my-tests', navigate: true);
    }

    public function revokeOtherSessions(UserSessionService $sessionService): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);

        $this->validate([
            'revokePassword' => ['required', 'string'],
        ]);

        if (! Hash::check($this->revokePassword, $user->password)) {
            throw ValidationException::withMessages([
                'revokePassword' => [__('profile.revoke_sessions_password_invalid')],
            ]);
        }

        $deleted = $sessionService->revokeOtherSessions($user);

        $this->reset('revokePassword');

        session()->flash(
            'profile_notice',
            __('profile.revoke_sessions_success', ['count' => $deleted]),
        );
    }

    public function render(UserQuizHistoryService $historyService, RecoveryJourneyService $journeyService): View
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
            'showQuizHistory' => $records->isNotEmpty(),
            'journey' => $journeyService->dashboardState($this->user),
        ]);
    }
}
