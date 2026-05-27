<?php

namespace App\Livewire\Quiz;

use App\Enums\QuestionType;
use App\Enums\SessionStatus;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizResponse;
use App\Models\QuizSession;
use App\Services\Quiz\QuizSessionClaimService;
use App\Services\Quiz\QuizSessionService;
use App\Services\Quiz\RebootProtocol\RebootProtocolFlow;
use App\Support\LocaleConfig;
use App\Support\QuizResultViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.quiz')]
class Take extends Component
{
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

    public function selectAnswer(string $value, QuizSessionService $quizSessionService, RebootProtocolFlow $flow): void
    {
        if ($this->session === null) {
            return;
        }

        $question = $this->currentQuestion;

        if ($question === null || $question->type === QuestionType::MultipleChoice) {
            return;
        }

        $quizSessionService->saveAnswer($this->session, $question, $value);
        $this->reloadSession();

        if ($flow->isRebootQuiz($this->slug) && $flow->shouldPromptSafety($this->session, $question)) {
            $flow->markSafetyPending($this->session);
            $this->reloadSession();

            return;
        }

        $this->advanceOrComplete($quizSessionService);
    }

    public function pickSingleChoice(string $value): void
    {
        if ($this->session === null || $this->isMultipleChoice) {
            return;
        }

        $this->singleSelection = $value;
    }

    public function submitSingleChoice(QuizSessionService $quizSessionService, RebootProtocolFlow $flow): void
    {
        if ($this->session === null || $this->singleSelection === null || $this->singleSelection === '') {
            return;
        }

        $question = $this->currentQuestion;

        if ($question === null || $question->type !== QuestionType::SingleChoice) {
            return;
        }

        $quizSessionService->saveAnswer($this->session, $question, $this->singleSelection);
        $this->reloadSession();

        if ($flow->isRebootQuiz($this->slug) && $flow->shouldPromptSafety($this->session, $question)) {
            $flow->markSafetyPending($this->session);
            $this->reloadSession();

            return;
        }

        $this->advanceOrComplete($quizSessionService);
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

    private function advanceOrComplete(QuizSessionService $quizSessionService): void
    {
        if ($this->session === null) {
            return;
        }

        $lastSort = (int) $this->session->quiz->questions->max('sort_order');

        if ($this->session->current_sort_order > $lastSort) {
            $quizSessionService->complete($this->session);
            $this->redirectRoute('quiz.result', ['uuid' => $this->session->uuid], navigate: true);
        }
    }

    public function toggleMultiChoice(string $value): void
    {
        if ($this->session === null) {
            return;
        }

        $question = $this->currentQuestion;

        if ($question === null || $question->type !== QuestionType::MultipleChoice) {
            return;
        }

        $value = (string) $value;
        $this->multiSelection = array_values(array_unique(array_map('strval', $this->multiSelection)));

        if (in_array($value, $this->multiSelection, true)) {
            $this->multiSelection = array_values(array_filter($this->multiSelection, fn ($item) => $item !== $value));
        } else {
            $maxSelections = (int) ($question->config['max_selections'] ?? 0);

            if ($maxSelections > 0 && count($this->multiSelection) >= $maxSelections) {
                return;
            }

            $this->multiSelection[] = $value;
        }

    }

    public function submitMultiChoice(QuizSessionService $quizSessionService, RebootProtocolFlow $flow): void
    {
        if ($this->session === null) {
            return;
        }

        $question = $this->currentQuestion;

        if ($question === null || $question->type !== QuestionType::MultipleChoice) {
            return;
        }

        $selections = array_values(array_unique(array_map('strval', $this->multiSelection)));

        if ($selections === []) {
            return;
        }

        $this->multiSelection = $selections;

        $quizSessionService->saveAnswer($this->session, $question, [
            'value' => $selections,
        ]);

        $this->reloadSession();

        if ($flow->isRebootQuiz($this->slug) && $flow->shouldPromptSafety($this->session, $question)) {
            $flow->markSafetyPending($this->session);
            $this->reloadSession();

            return;
        }

        $this->advanceOrComplete($quizSessionService);
    }

    public function skipQuestion(QuizSessionService $quizSessionService): void
    {
        if ($this->session === null) {
            return;
        }

        $question = $this->currentQuestion;

        if ($question === null) {
            return;
        }

        $quizSessionService->saveAnswer($this->session, $question, [
            'value' => [],
            'skipped' => true,
        ]);

        $this->reloadSession();

        $this->advanceOrComplete($quizSessionService);
    }

    public function toggleSound(): void
    {
        $this->soundEnabled = ! $this->soundEnabled;
    }

    public function goBack(): void
    {
        if ($this->session === null || $this->session->current_sort_order <= 1) {
            return;
        }

        $this->session->update([
            'current_sort_order' => $this->session->current_sort_order - 1,
        ]);

        $this->reloadSession();
    }

    public function getRequiresContinueProperty(): bool
    {
        return $this->isMultipleChoice;
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

    public function getCurrentQuestionProperty(): ?Question
    {
        if ($this->session === null) {
            return null;
        }

        $question = $this->session->quiz->questions
            ->firstWhere('sort_order', $this->session->current_sort_order);

        return $question?->loadMissing('options');
    }

    public function getProgressPercentProperty(): int
    {
        if ($this->session === null) {
            return 0;
        }

        $total = max($this->session->quiz->questions->count(), 1);
        $current = min($this->session->current_sort_order, $total);

        return (int) round(($current / $total) * 100);
    }

    /**
     * @return Collection<int, QuestionOption>
     */
    public function getCurrentOptionsProperty(): Collection
    {
        return $this->currentQuestion?->options ?? collect();
    }

    public function getIsLikertProperty(): bool
    {
        return $this->currentQuestion?->type === QuestionType::Likert;
    }

    public function getIsMultipleChoiceProperty(): bool
    {
        return $this->currentQuestion?->type === QuestionType::MultipleChoice;
    }

    private function reloadSession(): void
    {
        if ($this->session === null) {
            return;
        }

        $this->session = $this->session->fresh([
            'quiz.questions' => fn ($query) => $query->with('options'),
            'responses',
        ]);

        $this->syncSelections();
    }

    private function syncSelections(): void
    {
        $this->syncMultiSelection();
        $this->syncSingleSelection();
    }

    private function syncMultiSelection(): void
    {
        if ($this->session === null) {
            $this->multiSelection = [];

            return;
        }

        $question = $this->currentQuestion;

        if ($question === null || $question->type !== QuestionType::MultipleChoice) {
            $this->multiSelection = [];

            return;
        }

        $response = $this->session->responses
            ?->firstWhere('question_id', $question->id);

        if (! $response instanceof QuizResponse) {
            $this->multiSelection = [];

            return;
        }

        $raw = $response->value['value'] ?? [];

        if (! is_array($raw)) {
            $this->multiSelection = [];

            return;
        }

        $this->multiSelection = array_values(array_filter(array_map('strval', $raw), fn ($item) => $item !== ''));
    }

    private function syncSingleSelection(): void
    {
        if ($this->session === null) {
            $this->singleSelection = null;

            return;
        }

        $question = $this->currentQuestion;

        if ($question === null || $question->type !== QuestionType::SingleChoice) {
            $this->singleSelection = null;

            return;
        }

        $response = $this->session->responses
            ?->firstWhere('question_id', $question->id);

        if (! $response instanceof QuizResponse) {
            $this->singleSelection = null;

            return;
        }

        $raw = $response->value['value'] ?? null;

        if (is_array($raw) || $raw === null || $raw === '') {
            $this->singleSelection = null;

            return;
        }

        $this->singleSelection = (string) $raw;
    }

    /**
     * @return list<string>
     */
    public function getQuizLocaleProperty(): string
    {
        return LocaleConfig::resolve($this->session?->locale ?? app()->getLocale());
    }

    public function getLikertLabelsProperty(): array
    {
        if ($this->session === null) {
            return [];
        }

        $locale = $this->quizLocale;

        return $this->session->quiz->settings['likert_labels'][$locale]
            ?? $this->session->quiz->settings['likert_labels']['en']
            ?? ['1', '2', '3', '4', '5'];
    }

    public function render(): View
    {
        if ($this->returningSession !== null) {
            $resultData = QuizResultViewData::fromSession($this->returningSession);
            $report = $resultData['report'];

            return view('livewire.quiz.returning', [
                'quizName' => $this->returningSession->quiz->getTranslation('name', app()->getLocale(), true),
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

        return view('livewire.quiz.take', [
            'question' => $this->currentQuestion,
            'options' => $this->currentOptions,
            'isLikert' => $this->isLikert,
            'isMultipleChoice' => $this->isMultipleChoice,
            'requiresContinue' => $this->requiresContinue,
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
