<?php

namespace Tests\Feature\Quiz;

use App\Enums\QuestionType;
use App\Livewire\Quiz\Take;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Services\Quiz\QuizSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MultipleChoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_choice_can_save_multiple_answers_and_advance(): void
    {
        $quiz = Quiz::factory()->create([
            'slug' => 'multi-choice-001',
        ]);

        $q1 = Question::factory()->create([
            'quiz_id' => $quiz->id,
            'type' => QuestionType::MultipleChoice,
            'sort_order' => 1,
        ]);

        $q2 = Question::factory()->create([
            'quiz_id' => $quiz->id,
            'type' => QuestionType::SingleChoice,
            'sort_order' => 2,
        ]);

        $a = QuestionOption::factory()->create([
            'question_id' => $q1->id,
            'sort_order' => 1,
            'value' => 'A',
            'label' => ['en' => 'A', 'fa' => 'A'],
        ]);

        $b = QuestionOption::factory()->create([
            'question_id' => $q1->id,
            'sort_order' => 2,
            'value' => 'B',
            'label' => ['en' => 'B', 'fa' => 'B'],
        ]);

        QuestionOption::factory()->create([
            'question_id' => $q2->id,
            'sort_order' => 1,
            'value' => 'X',
            'label' => ['en' => 'X', 'fa' => 'X'],
        ]);

        $session = app(QuizSessionService::class)->start($quiz);

        Livewire::test(Take::class, ['uuid' => $session->uuid])
            ->assertSet('session.current_sort_order', 1)
            ->call('toggleMultiChoice', $a->value)
            ->call('toggleMultiChoice', $b->value)
            ->call('submitMultiChoice')
            ->assertSet('session.current_sort_order', 2);

        $session->refresh()->load('responses');
        $response = $session->responses->firstWhere('question_id', $q1->id);

        $this->assertNotNull($response);
        $this->assertSame(['A', 'B'], $response->value['value']);
    }

    public function test_submit_multi_choice_renders_next_question_after_one_submit(): void
    {
        $quiz = Quiz::factory()->create([
            'slug' => 'multi-choice-005',
        ]);

        $q1 = Question::factory()->create([
            'quiz_id' => $quiz->id,
            'type' => QuestionType::MultipleChoice,
            'sort_order' => 1,
            'text' => ['en' => 'First multi question', 'fa' => 'First multi question FA'],
        ]);

        $q2 = Question::factory()->create([
            'quiz_id' => $quiz->id,
            'type' => QuestionType::MultipleChoice,
            'sort_order' => 2,
            'text' => ['en' => 'Second multi question', 'fa' => 'Second multi question FA'],
        ]);

        QuestionOption::factory()->create([
            'question_id' => $q1->id,
            'sort_order' => 1,
            'value' => 'A',
            'label' => ['en' => 'A', 'fa' => 'A'],
        ]);

        QuestionOption::factory()->create([
            'question_id' => $q1->id,
            'sort_order' => 2,
            'value' => 'B',
            'label' => ['en' => 'B', 'fa' => 'B'],
        ]);

        QuestionOption::factory()->create([
            'question_id' => $q2->id,
            'sort_order' => 1,
            'value' => 'X',
            'label' => ['en' => 'X', 'fa' => 'X'],
        ]);

        $session = app(QuizSessionService::class)->start($quiz);

        $component = Livewire::test(Take::class, ['uuid' => $session->uuid])
            ->assertSee('First multi question')
            ->call('toggleMultiChoice', 'A')
            ->call('toggleMultiChoice', 'B')
            ->call('submitMultiChoice')
            ->assertSet('session.current_sort_order', 2);

        $component
            ->assertSee('Second multi question')
            ->assertDontSee('First multi question');
    }

    public function test_toggle_multi_choice_caps_at_max(): void
    {
        $quiz = Quiz::factory()->create([
            'slug' => 'multi-choice-004',
        ]);

        $q1 = Question::factory()->create([
            'quiz_id' => $quiz->id,
            'type' => QuestionType::MultipleChoice,
            'sort_order' => 1,
            'config' => ['max_selections' => 2],
        ]);

        QuestionOption::factory()->create([
            'question_id' => $q1->id,
            'sort_order' => 1,
            'value' => 'A',
            'label' => ['en' => 'A', 'fa' => 'A'],
        ]);

        QuestionOption::factory()->create([
            'question_id' => $q1->id,
            'sort_order' => 2,
            'value' => 'B',
            'label' => ['en' => 'B', 'fa' => 'B'],
        ]);

        QuestionOption::factory()->create([
            'question_id' => $q1->id,
            'sort_order' => 3,
            'value' => 'C',
            'label' => ['en' => 'C', 'fa' => 'C'],
        ]);

        $session = app(QuizSessionService::class)->start($quiz);

        Livewire::test(Take::class, ['uuid' => $session->uuid])
            ->call('toggleMultiChoice', 'A')
            ->call('toggleMultiChoice', 'B')
            ->call('toggleMultiChoice', 'C')
            ->assertSet('multiSelection', ['A', 'B']);
    }

    public function test_multiple_choice_can_be_skipped_with_no_selected_options(): void
    {
        $quiz = Quiz::factory()->create([
            'slug' => 'multi-choice-002',
        ]);

        $q1 = Question::factory()->create([
            'quiz_id' => $quiz->id,
            'type' => QuestionType::MultipleChoice,
            'sort_order' => 1,
        ]);

        $q2 = Question::factory()->create([
            'quiz_id' => $quiz->id,
            'type' => QuestionType::SingleChoice,
            'sort_order' => 2,
        ]);

        QuestionOption::factory()->create([
            'question_id' => $q1->id,
            'sort_order' => 1,
            'value' => 'A',
            'label' => ['en' => 'A', 'fa' => 'A'],
        ]);

        QuestionOption::factory()->create([
            'question_id' => $q2->id,
            'sort_order' => 1,
            'value' => 'X',
            'label' => ['en' => 'X', 'fa' => 'X'],
        ]);

        $session = app(QuizSessionService::class)->start($quiz);

        Livewire::test(Take::class, ['uuid' => $session->uuid])
            ->assertSet('session.current_sort_order', 1)
            ->call('skipQuestion')
            ->assertSet('session.current_sort_order', 2);

        $session->refresh()->load('responses');
        $response = $session->responses->firstWhere('question_id', $q1->id);

        $this->assertNotNull($response);
        $this->assertSame([], $response->value['value']);
        $this->assertTrue((bool) ($response->value['skipped'] ?? false));
    }
}
