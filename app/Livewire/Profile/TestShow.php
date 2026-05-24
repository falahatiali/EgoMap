<?php

namespace App\Livewire\Profile;

use App\Enums\SessionStatus;
use App\Models\QuizSession;
use App\Services\Quiz\QuizSessionClaimService;
use App\Services\Quiz\QuizSessionService;
use App\Support\QuizResultViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TestShow extends Component
{
    public QuizSession $session;

    public function mount(string $uuid, QuizSessionClaimService $claimService, QuizSessionService $quizSessionService): void
    {
        $claimService->claimForUser(Auth::user());

        $this->session = QuizSession::query()
            ->where('uuid', $uuid)
            ->with(['quiz', 'result.outcomeProfile'])
            ->firstOrFail();

        if ($this->session->user_id === null && $this->session->email === Auth::user()->email) {
            $claimService->claimSession($this->session, Auth::user());
            $this->session->refresh();
        }

        abort_unless($this->session->user_id === Auth::id(), 403);

        if ($this->session->status === SessionStatus::InProgress) {
            $this->redirectRoute('quiz.session', ['uuid' => $this->session->uuid], navigate: true);

            return;
        }

        if ($this->session->result === null) {
            $quizSessionService->complete($this->session);
            $this->session->refresh()->load(['result.outcomeProfile', 'quiz']);
        }
    }

    /**
     * @return array{report: array<string, mixed>, content: array<string, mixed>, palette: array{accent: string, soft: string, glow: string, group: string}}
     */
    public function getResultDataProperty(): array
    {
        return QuizResultViewData::fromSession($this->session);
    }

    public function render(): View
    {
        $data = $this->resultData;

        return view('livewire.profile.test-show', [
            'report' => $data['report'],
            'content' => $data['content'],
            'palette' => $data['palette'],
            'quizName' => $this->session->quiz->getTranslation('name', app()->getLocale()),
            'completedAt' => $this->session->completed_at,
        ]);
    }
}
