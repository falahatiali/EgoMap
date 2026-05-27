<?php

namespace Tests\Feature\Quiz;

use App\Enums\QuestionType;
use App\Livewire\Quiz\Take;
use App\Models\Quiz;
use App\Services\Pdf\Definitions\QuizResultPdfDefinitionFactory;
use App\Services\Pdf\PdfGeneratorService;
use App\Services\Quiz\QuizSessionService;
use App\Services\Quiz\RebootProtocol\RebootProtocolFlow;
use App\Services\Quiz\RebootProtocol\RebootProtocolReportBuilder;
use App\Services\Quiz\SessionAnswersResolver;
use App\Support\RebootProtocolQuiz;
use Database\Seeders\RebootProtocolQuizSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RebootProtocolAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RebootProtocolQuizSeeder::class);
    }

    public function test_seeder_creates_ten_active_questions_with_four_multi_select_max_two(): void
    {
        $quiz = Quiz::query()->where('slug', RebootProtocolQuiz::SLUG)->firstOrFail();

        $this->assertSame('reboot_protocol', $quiz->scoring_config['engine']);
        $this->assertCount(10, $quiz->questions()->where('is_active', true)->get());
        $this->assertTrue(
            $quiz->questions()
                ->where('is_active', true)
                ->where('config->key', 'time_since_event')
                ->exists(),
        );
        $this->assertSame(
            4,
            $quiz->questions()->where('type', QuestionType::MultipleChoice)->count(),
        );

        foreach ([3, 4, 6, 9] as $sortOrder) {
            $question = $quiz->questions->firstWhere('sort_order', $sortOrder);
            $this->assertSame(QuestionType::MultipleChoice, $question?->type);
            $this->assertSame(2, $question?->config['max_selections']);
        }
    }

    public function test_report_builder_uses_new_scoring_rules_and_disclaimer(): void
    {
        $report = app(RebootProtocolReportBuilder::class)->build([
            'situation_type' => 1,
            'time_since_event' => 1,
            'immediate_risk' => [1],
            'contact_after_breakup' => 1,
            'self_pattern' => [1],
            'first_need' => 5,
            'pain_focus' => [7],
        ]);

        $this->assertContains($report['type_code'], ['shock', 'withdrawal', 'instability']);
        $this->assertTrue($report['first_prescription']['emergency']);
        $this->assertSame('2.1', $report['analysis_version']);
        $this->assertSame(
            'Your answers suggest a pattern, not a final diagnosis.',
            $report['report_disclaimer']['en'],
        );
    }

    public function test_completing_session_generates_reboot_report(): void
    {
        $quiz = Quiz::query()
            ->where('slug', RebootProtocolQuiz::SLUG)
            ->with(['questions.options'])
            ->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);

        foreach ($quiz->questions as $question) {
            $firstOption = (string) $question->options()->orderBy('sort_order')->value('value');

            if ($question->type === QuestionType::MultipleChoice) {
                $service->saveAnswer($session, $question, ['value' => [$firstOption]]);
            } else {
                $service->saveAnswer($session, $question, $firstOption);
            }

            $session->refresh();
        }

        $result = $service->complete($session->fresh(['responses.question']));

        $this->assertSame('reboot_protocol', $result->free_report['template']);
        $this->assertArrayHasKey('stability_score', $result->free_report);
    }

    public function test_reboot_pdf_definition_skips_mbti_dimension_sections(): void
    {
        $quiz = Quiz::query()
            ->where('slug', RebootProtocolQuiz::SLUG)
            ->with(['questions.options'])
            ->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);

        foreach ($quiz->questions as $question) {
            $firstOption = (string) $question->options()->orderBy('sort_order')->value('value');

            if ($question->type === QuestionType::MultipleChoice) {
                $service->saveAnswer($session, $question, ['value' => [$firstOption]]);
            } else {
                $service->saveAnswer($session, $question, $firstOption);
            }

            $session->refresh();
        }

        $service->complete($session->fresh(['responses.question']));
        $session->refresh()->load(['result', 'quiz']);

        $document = QuizResultPdfDefinitionFactory::fromSession($session, 'fa');
        $types = collect($document->sections)->pluck('type')->all();

        $this->assertNotContains('type_letters', $types);
        $this->assertNotContains('dimension_bars', $types);
        $this->assertNotEmpty($document->sections);

        $groupSection = collect($document->sections)
            ->firstWhere('type', 'stat_row');

        $this->assertNotNull($groupSection);
        $groupValue = collect($groupSection['items'] ?? [])
            ->firstWhere('tone', 'group')['value'] ?? '';

        $this->assertSame(__('pdf.group_reboot', locale: 'fa'), $groupValue);
        $this->assertStringNotContainsString('pdf.group_', $groupValue);

        $overview = collect($document->sections)->firstWhere('type', 'overview');
        $this->assertNotNull($overview);
        $this->assertSame(__('pdf.overview_title_reboot', locale: 'fa'), $overview['title']);
        $this->assertDoesNotMatchRegularExpression('/\b(Your|the|and|is)\b/i', (string) ($overview['body'] ?? ''));
        $this->assertMatchesRegularExpression('/[\x{0600}-\x{06FF}]/u', (string) ($overview['body'] ?? ''));
    }

    public function test_reboot_farsi_pdf_generates_successfully(): void
    {
        $quiz = Quiz::query()
            ->where('slug', RebootProtocolQuiz::SLUG)
            ->with(['questions.options'])
            ->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);

        foreach ($quiz->questions as $question) {
            $firstOption = (string) $question->options()->orderBy('sort_order')->value('value');

            if ($question->type === QuestionType::MultipleChoice) {
                $service->saveAnswer($session, $question, ['value' => [$firstOption]]);
            } else {
                $service->saveAnswer($session, $question, $firstOption);
            }

            $session->refresh();
        }

        $service->complete($session->fresh(['responses.question']));
        $session->refresh()->load(['result.outcomeProfile', 'quiz']);

        $document = QuizResultPdfDefinitionFactory::fromSession($session, 'fa');
        $path = app(PdfGeneratorService::class)->generate($document);

        $this->assertFileExists($path);
        $this->assertGreaterThan(1000, filesize($path) ?: 0);
        $this->assertSame('%PDF', substr((string) file_get_contents($path), 0, 4));

        app(PdfGeneratorService::class)->delete($path);
    }

    public function test_emotional_collapse_triggers_safety_prompt(): void
    {
        $quiz = Quiz::query()->where('slug', RebootProtocolQuiz::SLUG)->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);

        $riskQuestion = $quiz->questions->firstWhere('sort_order', 4);
        $this->assertNotNull($riskQuestion);

        $service->saveAnswer($session, $riskQuestion, ['value' => ['6']]);
        $session->refresh();

        $flow = app(RebootProtocolFlow::class);
        $this->assertTrue($flow->shouldPromptSafety($session->fresh(['responses.question']), $riskQuestion));
    }

    public function test_session_answers_resolver_normalizes_multi_and_scalar(): void
    {
        $quiz = Quiz::query()->where('slug', RebootProtocolQuiz::SLUG)->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);

        $pain = $quiz->questions->firstWhere('sort_order', 3);
        $situation = $quiz->questions->firstWhere('sort_order', 1);

        $service->saveAnswer($session, $pain, ['value' => ['2', '7']]);
        $service->saveAnswer($session, $situation, '4');
        $session->refresh();

        $map = app(SessionAnswersResolver::class)->answersByQuestionKey($session->fresh(['responses.question']));

        $this->assertSame([2, 7], $map['pain_focus']);
        $this->assertSame(4, $map['situation_type']);
    }

    public function test_reboot_single_choice_advances_on_one_click(): void
    {
        $quiz = Quiz::query()
            ->where('slug', RebootProtocolQuiz::SLUG)
            ->with(['questions.options'])
            ->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);

        $firstQuestion = $quiz->questions->firstWhere('sort_order', 1);
        $this->assertNotNull($firstQuestion);
        $this->assertSame(QuestionType::SingleChoice, $firstQuestion->type);

        $optionValue = (string) $firstQuestion->options()->orderBy('sort_order')->value('value');

        Livewire::test(Take::class, ['uuid' => $session->uuid])
            ->call('selectAnswer', $optionValue)
            ->assertSet('session.current_sort_order', 2);
    }

    public function test_quiz_start_page_shows_welcome_for_reboot_protocol(): void
    {
        $this->get(route('quiz.start', ['slug' => RebootProtocolQuiz::SLUG, 'locale' => 'en']))
            ->assertOk()
            ->assertSee('Step 1 — Stabilization check-in', false);
    }

    public function test_missing_quiz_redirects_home_instead_of_404(): void
    {
        Quiz::query()->where('slug', RebootProtocolQuiz::SLUG)->delete();

        Livewire::test(Take::class, ['slug' => RebootProtocolQuiz::SLUG])
            ->assertRedirect(route('home', ['locale' => 'en']));
    }
}
