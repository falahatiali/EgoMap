<?php

namespace App\Livewire\Quiz;

use App\Enums\QuestionType;
use App\Enums\SessionStatus;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuizSession;
use App\Services\Quiz\QuizSessionClaimService;
use App\Services\Quiz\QuizSessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.quiz')]
class Take extends Component
{
    public ?string $slug = null;

    public string $uuid = '';

    public ?QuizSession $session = null;

    public bool $starting = false;

    public bool $soundEnabled = true;

    public function mount(?string $slug = null, ?string $uuid = null): void
    {
        $quizSessionService = app(QuizSessionService::class);

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

        if ($slug !== null) {
            $this->slug = $slug;

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

    public function selectAnswer(string $value, QuizSessionService $quizSessionService): void
    {
        if ($this->session === null) {
            return;
        }

        $question = $this->currentQuestion;

        if ($question === null) {
            return;
        }

        $quizSessionService->saveAnswer($this->session, $question, $value);
        $this->session->refresh()->load(['quiz.questions' => fn ($q) => $q->with('options')]);

        $lastSort = (int) $this->session->quiz->questions->max('sort_order');

        if ($this->session->current_sort_order > $lastSort) {
            $quizSessionService->complete($this->session);
            $this->redirectRoute('quiz.result', ['uuid' => $this->session->uuid], navigate: true);
        }
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

        $this->session->refresh()->load(['quiz.questions' => fn ($q) => $q->with('options')]);
    }

    public function getCanGoBackProperty(): bool
    {
        return $this->session !== null && $this->session->current_sort_order > 1;
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

    /**
     * @return list<string>
     */
    public function getLikertLabelsProperty(): array
    {
        if ($this->session === null) {
            return [];
        }

        $locale = $this->session->locale;

        return $this->session->quiz->settings['likert_labels'][$locale]
            ?? $this->session->quiz->settings['likert_labels']['en']
            ?? ['1', '2', '3', '4', '5'];
    }

    public function render(): View
    {
        if ($this->session === null) {
            return view('livewire.quiz.starting', [
                'slug' => $this->slug,
            ]);
        }

        return view('livewire.quiz.take', [
            'question' => $this->currentQuestion,
            'options' => $this->currentOptions,
            'isLikert' => $this->isLikert,
            'progress' => $this->progressPercent,
            'likertLabels' => $this->likertLabels,
            'totalQuestions' => $this->session->quiz->questions->count(),
            'currentNumber' => min($this->session->current_sort_order, $this->session->quiz->questions->count()),
            'estimatedMinutes' => $this->session->quiz->estimated_minutes,
            'canGoBack' => $this->canGoBack,
        ]);
    }
}
