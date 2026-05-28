<?php

namespace App\Livewire\Admin\Sessions;

use App\Enums\Permission;
use App\Enums\SessionStatus;
use App\Livewire\Admin\Concerns\WithAdminPage;
use App\Models\Quiz;
use App\Models\QuizSession;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithAdminPage;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $quizFilter = '';

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminAccess->value), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingQuizFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $sessions = QuizSession::query()
            ->with(['quiz', 'user'])
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('uuid', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhereHas('user', fn ($user) => $user->where('email', 'like', $term)->orWhere('name', 'like', $term));
                });
            })
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->quizFilter !== '', fn ($query) => $query->where('quiz_id', $this->quizFilter))
            ->latest('updated_at')
            ->paginate(25);

        return $this->adminView('livewire.admin.sessions.index', [
            'sessions' => $sessions,
            'statusOptions' => SessionStatus::cases(),
            'quizOptions' => Quiz::query()->orderBy('slug')->get(['id', 'slug', 'name']),
        ], 'sessions');
    }
}
