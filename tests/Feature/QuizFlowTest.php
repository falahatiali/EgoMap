<?php

namespace Tests\Feature;

use App\Livewire\Quiz\Result;
use App\Livewire\Quiz\Take;
use App\Mail\QuizFullReportMail;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Services\Quiz\QuizSessionService;
use Database\Seeders\MbtiQuizSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class QuizFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MbtiQuizSeeder::class);
    }

    public function test_mbti_quiz_has_seventy_questions(): void
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();

        $this->assertSame(70, $quiz->questions()->where('is_active', true)->count());
        $this->assertSame(15, $quiz->estimated_minutes);
    }

    public function test_stale_local_storage_uuid_starts_fresh_session(): void
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();

        Livewire::test(Take::class, ['slug' => 'mbti-personality'])
            ->call('beginOrResume', '00000000-0000-0000-0000-000000000000')
            ->assertRedirect(route('quiz.session', ['uuid' => QuizSession::query()->latest('id')->first()->uuid]));

        $this->assertSame(1, QuizSession::query()->count());
    }

    public function test_resume_in_progress_session(): void
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $service->saveAnswer($session, $quiz->questions()->orderBy('sort_order')->firstOrFail(), 'A');

        Livewire::test(Take::class, ['slug' => 'mbti-personality'])
            ->call('beginOrResume', $session->uuid)
            ->assertRedirect(route('quiz.session', ['uuid' => $session->uuid]));

        $this->assertSame(1, QuizSession::query()->count());
        $this->assertSame(2, $session->fresh()->current_sort_order);
    }

    public function test_invalid_session_url_redirects_home(): void
    {
        $response = $this->get(route('quiz.session', ['uuid' => '00000000-0000-0000-0000-000000000000']));

        $response->assertRedirect(route('home'));
    }

    public function test_user_can_complete_mbti_quiz_and_see_result(): void
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);

        foreach ($quiz->questions()->with('options')->orderBy('sort_order')->get() as $question) {
            $service->saveAnswer($session, $question, $question->options->first()->value);
            $session->refresh();
        }

        $service->complete($session->fresh());
        $session->refresh()->load('result');

        $this->assertNotNull($session->result);
        $this->assertNotEmpty($session->result->free_report['type_code'] ?? null);
        $this->assertSame(4, strlen($session->result->free_report['type_code']));

        $response = $this->get(route('quiz.result', ['uuid' => $session->uuid]));

        $response->assertOk();
        $response->assertSee($session->result->free_report['type_code'], false);
        $response->assertSee(__('quiz.strengths_title'), false);
    }

    public function test_livewire_advances_after_binary_answer(): void
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $session = app(QuizSessionService::class)->start($quiz);
        $first = $quiz->questions()->with('options')->orderBy('sort_order')->firstOrFail();

        Livewire::test(Take::class, ['uuid' => $session->uuid])
            ->call('selectAnswer', $first->options->first()->value)
            ->assertSet('session.current_sort_order', 2)
            ->call('goBack')
            ->assertSet('session.current_sort_order', 1);
    }

    public function test_user_can_request_full_report_by_email(): void
    {
        Mail::fake();

        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $session = app(QuizSessionService::class)->start($quiz);

        foreach ($quiz->questions()->with('options')->orderBy('sort_order')->get() as $question) {
            app(QuizSessionService::class)->saveAnswer($session, $question, $question->options->first()->value);
        }

        app(QuizSessionService::class)->complete($session->fresh());

        Livewire::test(Result::class, ['uuid' => $session->uuid])
            ->set('email', 'user@example.com')
            ->call('sendFullReport')
            ->assertSet('emailSent', true);

        Mail::assertSent(QuizFullReportMail::class, function (QuizFullReportMail $mail) use ($session): bool {
            return $mail->session->uuid === $session->uuid;
        });

        $this->assertNotNull($session->fresh()->email_report_sent_at);
    }
}
