<?php

namespace Tests\Feature\Quiz;

use App\Livewire\Quiz\Take;
use App\Models\Quiz;
use App\Services\Quiz\QuizSessionService;
use Database\Seeders\MbtiQuizSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuizLocalePersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MbtiQuizSeeder::class);
    }

    public function test_final_answer_redirects_to_farsi_result_when_session_is_farsi(): void
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);

        $this->withSession(['locale' => 'fa']);

        $session = $service->start($quiz);
        $this->assertSame('fa', $session->locale);

        $questions = $quiz->questions()->with('options')->orderBy('sort_order')->get();
        $last = $questions->last();

        foreach ($questions->slice(0, -1) as $question) {
            $service->saveAnswer($session, $question, (string) $question->options->first()->value);
            $session->refresh();
        }

        Livewire::test(Take::class, ['uuid' => $session->uuid])
            ->call('selectAnswer', (string) $last->options->first()->value)
            ->assertRedirect(route('quiz.result', ['locale' => 'fa', 'uuid' => $session->uuid]));
    }

    public function test_result_page_redirects_to_session_locale_when_url_is_english(): void
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);

        $this->withSession(['locale' => 'fa']);
        $session = $service->start($quiz);

        foreach ($quiz->questions()->with('options')->orderBy('sort_order')->get() as $question) {
            $service->saveAnswer($session, $question, (string) $question->options->first()->value);
            $session->refresh();
        }

        $service->complete($session->fresh());

        $this->get(route('quiz.result', ['locale' => 'en', 'uuid' => $session->uuid]))
            ->assertRedirect(route('quiz.result', ['locale' => 'fa', 'uuid' => $session->uuid]));
    }

    public function test_farsi_result_page_renders_farsi_copy(): void
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);

        $this->withSession(['locale' => 'fa']);
        $session = $service->start($quiz);

        foreach ($quiz->questions()->with('options')->orderBy('sort_order')->get() as $question) {
            $service->saveAnswer($session, $question, (string) $question->options->first()->value);
            $session->refresh();
        }

        $service->complete($session->fresh());

        $this->get(route('quiz.result', ['locale' => 'fa', 'uuid' => $session->uuid]))
            ->assertOk()
            ->assertSee(__('quiz.strengths_title', locale: 'fa'), false);
    }
}
