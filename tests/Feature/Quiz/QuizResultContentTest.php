<?php

namespace Tests\Feature\Quiz;

use App\Livewire\Quiz\Result;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Services\Pdf\Definitions\QuizResultPdfDefinitionFactory;
use App\Services\Quiz\QuizSessionService;
use Database\Seeders\MbtiQuizSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuizResultContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MbtiQuizSeeder::class);
    }

    public function test_result_page_shows_enriched_content_from_mbti_catalog(): void
    {
        $session = $this->completeMbtiSessionForUser();

        Livewire::test(Result::class, ['uuid' => $session->uuid])
            ->assertOk()
            ->assertSee(__('quiz.narrative_title'), false)
            ->assertSee(__('quiz.communication_title'), false)
            ->assertSee(__('quiz.strengths_title'), false)
            ->assertSee(__('quiz.famous_title'), false);
    }

    public function test_result_page_shows_dimension_axis_descriptions(): void
    {
        $session = $this->completeMbtiSessionForUser();

        $response = $this->get(route('quiz.result', ['uuid' => $session->uuid]));

        $response->assertOk();
        $response->assertSee('Extraversion', false);
        $response->assertSee('Introversion', false);
    }

    public function test_pdf_definition_includes_narrative_and_featured_people(): void
    {
        $session = $this->completeMbtiSessionForUser();

        $document = QuizResultPdfDefinitionFactory::fromSession($session, 'en');
        $types = collect($document->sections)->pluck('type')->all();

        $this->assertNotEmpty($document->sections);
        $this->assertContains('overview', $types);
        $this->assertContains('dimension_bars', $types);

        $hasFeaturedNotes = collect($document->sections)->contains(function (array $section): bool {
            return $section['type'] === 'note_grid'
                && ($section['title'] ?? '') === __('quiz.famous_title', locale: 'en');
        });

        $this->assertTrue($hasFeaturedNotes);
    }

    private function completeMbtiSessionForUser(): QuizSession
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);

        foreach ($quiz->questions()->with('options')->orderBy('sort_order')->get() as $question) {
            $service->saveAnswer($session, $question, $question->options->first()->value);
        }

        $service->complete($session->fresh());

        return $session->fresh(['result', 'quiz']);
    }
}
