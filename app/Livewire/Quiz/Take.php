<?php

namespace App\Livewire\Quiz;

use App\Enums\QuestionType;
use App\Enums\SessionStatus;
use App\Livewire\Quiz\Concerns\AnswersQuizQuestions;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Services\Quiz\QuizSessionClaimService;
use App\Services\Quiz\QuizSessionService;
use App\Services\Quiz\RebootProtocol\RebootProtocolFlow;
use App\Support\LocaleConfig;
use App\Support\QuizResultViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.quiz')]
class Take extends Component
{
    use AnswersQuizQuestions;

    public ?string $slug = null;

    public string $uuid = '';

    public ?QuizSession $session = null;

    public ?QuizSession $returningSession = null;

    public bool $starting = false;

    public bool $soundEnabled = true;

    /** @var list<string> */
    public array $multiSelection = [];

    public ?string $singleSelection = null;

    public function mount(?string $slug = null, ?string $uuid = null): void
    {
        $quizSessionService = app(QuizSessionService::class);
        $slug = $slug ?? request()->route('slug');

        if ($uuid !== null) {
            $this->uuid = $uuid;
            $session = $quizSessionService->findByUuidOrNull($uuid);

            if ($session === null) {
                session()->flash('quiz_notice', __('quiz.session_not_found'));

                $this->redirectRoute('home', navigate: true);

                return;
            }

            $this->session = $session;
            $this->slug = $session->quiz->slug;
            $this->soundEnabled = (bool) ($session->quiz->settings['sound_enabled'] ?? true);
            $this->syncSelections();

            $claimService = app(QuizSessionClaimService::class);
            $claimService->rememberGuestSession($session);

            if (auth()->check() && $session->user_id === null) {
                $claimService->claimSession($session, auth()->user());
                $this->session->refresh();
            }

            if ($this->session->status === SessionStatus::Completed) {
                $this->redirectRoute('quiz.result', ['uuid' => $uuid], navigate: true);

                return;
            }

            return;
        }

        if (is_string($slug) && $slug !== '') {
            $this->slug = $slug;

            if ($quizSessionService->findActiveQuizBySlugOrNull($slug) === null) {
                session()->flash('quiz_notice', __('quiz.quiz_unavailable'));

                $this->redirectRoute('home', navigate: true);

                return;
            }

            if (Auth::check()) {
                $this->applyAuthenticatedEntry($quizSessionService);
            }

            return;
        }

        abort(404);
    }

    public function beginOrResume(?string $resumeUuid, QuizSessionService $quizSessionService): void
    {
        if ($this->slug === null || $this->starting) {
            return;
        }

        $this->starting = true;

        $quiz = $quizSessionService->findActiveQuizBySlug($this->slug);

        if (Auth::check()) {
            $this->beginOrResumeForAuthenticatedUser($quiz, $resumeUuid, $quizSessionService);

            return;
        }

        $this->beginOrResumeForGuest($quiz, $resumeUuid, $quizSessionService);
    }

    public function startRetake(QuizSessionService $quizSessionService): void
    {
        if ($this->slug === null) {
            return;
        }

        $quiz = $quizSessionService->findActiveQuizBySlug($this->slug);

        $this->dispatch('quiz-clear-stored-session', slug: $this->slug);

        $session = $quizSessionService->start($quiz);

        $this->redirectRoute('quiz.session', ['uuid' => $session->uuid], navigate: true);
    }

    private function beginOrResumeForAuthenticatedUser(
        Quiz $quiz,
        ?string $resumeUuid,
        QuizSessionService $quizSessionService,
    ): void {
        if ($resumeUuid !== null) {
            $this->dispatch('quiz-clear-stored-session', slug: $this->slug);
        }

        $this->applyAuthenticatedEntry($quizSessionService, $quiz);
    }

    private function applyAuthenticatedEntry(
        QuizSessionService $quizSessionService,
        ?Quiz $quiz = null,
    ): void {
        $quiz ??= $quizSessionService->findActiveQuizBySlug((string) $this->slug);
        $entry = $quizSessionService->resolveAuthenticatedEntry($quiz, Auth::user());

        if ($entry['action'] === 'resume' && $entry['session'] !== null) {
            $this->redirectRoute('quiz.session', ['uuid' => $entry['session']->uuid], navigate: true);

            return;
        }

        if ($entry['action'] === 'show_previous' && $entry['session'] !== null) {
            $this->returningSession = $entry['session'];
            $this->starting = false;

            return;
        }

        $session = $quizSessionService->start($quiz);
        $this->redirectRoute('quiz.session', ['uuid' => $session->uuid], navigate: true);
    }

    private function beginOrResumeForGuest(
        Quiz $quiz,
        ?string $resumeUuid,
        QuizSessionService $quizSessionService,
    ): void {
        if ($resumeUuid !== null) {
            $existing = $quizSessionService->findByUuidOrNull($resumeUuid);

            if ($existing !== null && $existing->quiz_id === $quiz->id) {
                if ($existing->status === SessionStatus::InProgress) {
                    $this->redirectRoute('quiz.session', ['uuid' => $existing->uuid], navigate: true);

                    return;
                }

                if ($existing->status === SessionStatus::Completed) {
                    $this->dispatch('quiz-clear-stored-session', slug: $this->slug);
                }
            } else {
                $this->dispatch('quiz-clear-stored-session', slug: $this->slug);
            }
        }

        $session = $quizSessionService->resolveSessionForQuiz(
            $quiz,
            null,
            request()->cookie('egomap_guest'),
        );

        $this->redirectRoute('quiz.session', ['uuid' => $session->uuid], navigate: true);
    }

    public function submitSafetyAnswer(string $value, RebootProtocolFlow $flow, QuizSessionService $quizSessionService): void
    {
        if ($this->session === null || ! $this->isRebootProtocol) {
            return;
        }

        $numeric = (int) $value;

        if ($numeric < 1 || $numeric > 4) {
            return;
        }

        $result = $flow->recordSafetyAnswer($this->session, $numeric);
        $flow->clearSafetyPrompt($this->session);
        $this->session->refresh();

        if ($result['crisis']) {
            return;
        }

        $this->advanceOrComplete($quizSessionService);
    }

    public function cancelSafetyCheck(RebootProtocolFlow $flow): void
    {
        if ($this->session === null) {
            return;
        }

        $flow->clearSafetyPrompt($this->session);

        if ($this->session->current_sort_order > 1) {
            $this->session->update([
                'current_sort_order' => $this->session->current_sort_order - 1,
            ]);
        }

        $this->reloadSession();
    }

    public function resetAfterCrisis(QuizSessionService $quizSessionService): void
    {
        if ($this->slug === null) {
            return;
        }

        $this->dispatch('quiz-clear-stored-session', slug: $this->slug);

        $quiz = $quizSessionService->findActiveQuizBySlug($this->slug);
        $session = $quizSessionService->start($quiz);

        $this->redirectRoute('quiz.session', ['uuid' => $session->uuid], navigate: true);
    }

    public function toggleSound(): void
    {
        $this->soundEnabled = ! $this->soundEnabled;
    }

    public function getCanGoBackProperty(): bool
    {
        return $this->session !== null && $this->session->current_sort_order > 1 && ! $this->showSafety && ! $this->isCrisis;
    }

    public function getIsRebootProtocolProperty(): bool
    {
        return app(RebootProtocolFlow::class)->isRebootQuiz($this->slug);
    }

    public function getShowSafetyProperty(): bool
    {
        if ($this->session === null || ! $this->isRebootProtocol) {
            return false;
        }

        return app(RebootProtocolFlow::class)->isSafetyPending($this->session);
    }

    public function getIsCrisisProperty(): bool
    {
        if ($this->session === null || ! $this->isRebootProtocol) {
            return false;
        }

        return app(RebootProtocolFlow::class)->isCrisis($this->session);
    }

    /**
     * @return list<string>
     */
    public function getQuizLocaleProperty(): string
    {
        return LocaleConfig::resolve($this->session?->locale ?? app()->getLocale());
    }

    public function render(): View
    {
        if ($this->returningSession !== null) {
            $locale = LocaleConfig::resolve((string) request()->route('locale', app()->getLocale()));
            $resultData = QuizResultViewData::fromSession($this->returningSession, $locale);
            $report = $resultData['report'];

            return view('livewire.quiz.returning', [
                'quizName' => $this->returningSession->quiz->getTranslation('name', $locale, true),
                'typeCode' => (string) ($report['type_code'] ?? '—'),
                'title' => (string) ($report['title'] ?? ''),
                'summary' => (string) ($resultData['content']['tagline'] ?? ($report['summary'] ?? '')),
                'completedAt' => $this->returningSession->completed_at,
                'palette' => $resultData['palette'],
            ]);
        }

        if ($this->session === null) {
            $quiz = $this->slug !== null
                ? Quiz::query()->where('slug', $this->slug)->where('is_active', true)->first()
                : null;

            return view('livewire.quiz.starting', [
                'slug' => $this->slug,
                'quiz' => $quiz,
            ]);
        }

        $question = $this->resolveCurrentQuestion();

        return view('livewire.quiz.take', [
            'question' => $question,
            'options' => $question?->options ?? collect(),
            'isLikert' => $question?->type === QuestionType::Likert,
            'isMultipleChoice' => $question?->type === QuestionType::MultipleChoice,
            'requiresContinue' => $question?->type === QuestionType::MultipleChoice,
            'multiSelection' => $this->multiSelection,
            'singleSelection' => $this->singleSelection,
            'progress' => $this->progressPercent,
            'likertLabels' => $this->likertLabels,
            'totalQuestions' => $this->session->quiz->questions->count(),
            'currentNumber' => min($this->session->current_sort_order, $this->session->quiz->questions->count()),
            'estimatedMinutes' => $this->session->quiz->estimated_minutes,
            'canGoBack' => $this->canGoBack,
            'isRebootProtocol' => $this->isRebootProtocol,
            'showSafety' => $this->showSafety,
            'isCrisis' => $this->isCrisis,
            'quizLocale' => $this->quizLocale,
        ]);
    }
}
