<?php

namespace App\Livewire\Quiz\Concerns;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuizResponse;
use App\Services\Quiz\QuizSessionService;
use App\Services\Quiz\RebootProtocol\RebootProtocolFlow;

trait AnswersQuizQuestions
{
    public function selectAnswer(string $value, QuizSessionService $quizSessionService, RebootProtocolFlow $flow): void
    {
        if ($this->session === null) {
            return;
        }

        $question = $this->resolveCurrentQuestion();

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

    public function toggleMultiChoice(string $value): void
    {
        if ($this->session === null) {
            return;
        }

        $question = $this->resolveCurrentQuestion();

        if ($question === null || $question->type !== QuestionType::MultipleChoice) {
            return;
        }

        $value = (string) $value;
        $this->multiSelection = array_values(array_unique(array_map('strval', $this->multiSelection)));

        if (in_array($value, $this->multiSelection, true)) {
            $this->multiSelection = array_values(array_filter(
                $this->multiSelection,
                fn (string $item): bool => $item !== $value,
            ));
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

        $question = $this->resolveCurrentQuestion();

        if ($question === null || $question->type !== QuestionType::MultipleChoice) {
            return;
        }

        $selections = array_values(array_unique(array_map('strval', $this->multiSelection)));

        if ($selections === []) {
            return;
        }

        $max = (int) ($question->config['max_selections'] ?? 0);

        if ($max > 0) {
            $selections = array_slice($selections, 0, $max);
        }

        $this->multiSelection = $selections;
        $this->singleSelection = null;

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

        $question = $this->resolveCurrentQuestion();

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

    protected function resolveCurrentQuestion(): ?Question
    {
        if ($this->session === null) {
            return null;
        }

        $sortOrder = (int) $this->session->current_sort_order;

        return Question::query()
            ->where('quiz_id', $this->session->quiz_id)
            ->where('sort_order', $sortOrder)
            ->with('options')
            ->first();
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
     * @return list<string>
     */
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

    protected function advanceOrComplete(QuizSessionService $quizSessionService): void
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

    protected function reloadSession(): void
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

    protected function syncSelections(): void
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

        $question = $this->resolveCurrentQuestion();

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

        $question = $this->resolveCurrentQuestion();

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
}
