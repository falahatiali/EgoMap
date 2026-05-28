<?php

namespace App\Livewire\Admin\Sessions;

use App\Enums\Permission;
use App\Enums\SessionStatus;
use App\Livewire\Admin\Concerns\WithAdminPage;
use App\Models\QuizSession;
use App\Support\QuizResultViewData;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Show extends Component
{
    use WithAdminPage;

    public QuizSession $session;

    public function mount(QuizSession $session): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminAccess->value), 403);

        $this->session = $session->load(['quiz', 'user', 'result.outcomeProfile', 'responses']);
    }

    public function markAbandoned(): void
    {
        if ($this->session->status === SessionStatus::Abandoned) {
            return;
        }

        $this->session->update(['status' => SessionStatus::Abandoned]);
        $this->session->refresh();
        $this->adminFlash(__('admin.sessions.marked_abandoned'));
    }

    /**
     * @return array{report: array<string, mixed>, content: array<string, mixed>, palette: array<string, mixed>}|null
     */
    public function getResultPreviewProperty(): ?array
    {
        if ($this->session->result === null) {
            return null;
        }

        return QuizResultViewData::fromSession($this->session, 'en');
    }

    public function render(): View
    {
        return $this->adminView('livewire.admin.sessions.show', [
            'resultPreview' => $this->resultPreview,
        ], 'sessions');
    }
}
