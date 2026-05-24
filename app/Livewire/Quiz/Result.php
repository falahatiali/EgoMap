<?php

namespace App\Livewire\Quiz;

use App\Mail\QuizFullReportMail;
use App\Models\QuizSession;
use App\Services\Quiz\QuizSessionClaimService;
use App\Services\Quiz\QuizSessionService;
use App\Support\QuizResultViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.quiz-result')]
class Result extends Component
{
    public string $uuid = '';

    public QuizSession $session;

    public string $email = '';

    public bool $emailSent = false;

    public function mount(string $uuid, QuizSessionService $quizSessionService, QuizSessionClaimService $claimService): void
    {
        $this->uuid = $uuid;
        $this->session = $quizSessionService->findByUuid($uuid);
        $this->session->load(['result.outcomeProfile', 'quiz']);

        if (Auth::check() && $this->session->user_id === null) {
            $claimService->claimSession($this->session, Auth::user());
            $this->session->refresh();
        }

        $claimService->rememberGuestSession($this->session);

        if ($this->session->result === null) {
            $quizSessionService->complete($this->session);
            $this->session->refresh()->load(['result.outcomeProfile', 'quiz']);
        }

        $this->email = $this->session->email ?? '';
        $this->emailSent = $this->session->email_report_sent_at !== null;
    }

    public function sendFullReport(QuizSessionService $quizSessionService): void
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $quizSessionService->attachEmail($this->session, $this->email);

        Mail::to($this->email)->send(new QuizFullReportMail($this->session->fresh(['result', 'quiz'])));

        $quizSessionService->markEmailReportSent($this->session);

        $this->emailSent = true;
    }

    public function getReportProperty(): array
    {
        return $this->resultData['report'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getProfileContentProperty(): array
    {
        return $this->resultData['content'];
    }

    /**
     * @return array{accent: string, soft: string, glow: string, group: string}
     */
    public function getPaletteProperty(): array
    {
        return $this->resultData['palette'];
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
        return view('livewire.quiz.result', [
            'report' => $this->report,
            'content' => $this->profileContent,
            'palette' => $this->palette,
        ]);
    }
}
