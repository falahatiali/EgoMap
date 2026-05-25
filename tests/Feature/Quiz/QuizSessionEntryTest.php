<?php

namespace Tests\Feature\Quiz;

use App\Enums\SessionStatus;
use App\Livewire\Quiz\Take;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\Quiz\QuizSessionService;
use Database\Seeders\MbtiQuizSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuizSessionEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MbtiQuizSeeder::class);
    }

    public function test_has_meaningful_progress_when_session_has_responses(): void
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $service->saveAnswer($session, $quiz->questions()->orderBy('sort_order')->firstOrFail(), 'A');

        $this->assertTrue($service->hasMeaningfulProgress($session->fresh()));
    }

    public function test_has_no_meaningful_progress_for_empty_session_at_question_one(): void
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $session = app(QuizSessionService::class)->start($quiz);

        $this->assertFalse(app(QuizSessionService::class)->hasMeaningfulProgress($session));
    }

    public function test_resolve_authenticated_entry_returns_resume_for_active_session(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['user_id' => $user->id]);
        $service->saveAnswer($session, $quiz->questions()->orderBy('sort_order')->firstOrFail(), 'A');

        $entry = $service->resolveAuthenticatedEntry($quiz, $user);

        $this->assertSame('resume', $entry['action']);
        $this->assertSame($session->uuid, $entry['session']->uuid);
    }

    public function test_resolve_authenticated_entry_returns_show_previous_for_completed_quiz(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['user_id' => $user->id]);
        $service->complete($session->fresh());

        $entry = $service->resolveAuthenticatedEntry($quiz, $user);

        $this->assertSame('show_previous', $entry['action']);
        $this->assertSame($session->uuid, $entry['session']->uuid);
    }

    public function test_resolve_authenticated_entry_returns_start_fresh_for_new_user(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();

        $entry = app(QuizSessionService::class)->resolveAuthenticatedEntry($quiz, $user);

        $this->assertSame('start_fresh', $entry['action']);
        $this->assertNull($entry['session']);
    }

    public function test_abandon_stale_empty_sessions_marks_empty_in_progress_as_abandoned(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();

        $stale = QuizSession::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'status' => SessionStatus::InProgress,
            'current_sort_order' => 1,
        ]);

        $abandoned = app(QuizSessionService::class)->abandonStaleEmptySessionsForUser($user, $quiz->id);

        $this->assertSame(1, $abandoned);
        $this->assertSame(SessionStatus::Abandoned, $stale->fresh()->status);
    }

    public function test_authenticated_mount_redirects_to_fresh_session_when_no_prior_attempts(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Take::class, ['slug' => 'mbti-personality'])
            ->assertRedirect(route('quiz.session', [
                'uuid' => QuizSession::query()->where('user_id', $user->id)->first()->uuid,
            ]));

        $this->assertSame(1, QuizSession::query()->where('user_id', $user->id)->count());
    }

    public function test_completed_session_uuid_redirects_to_result_page(): void
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $service->complete($session->fresh());

        Livewire::test(Take::class, ['uuid' => $session->uuid])
            ->assertRedirect(route('quiz.result', ['uuid' => $session->uuid]));
    }

    public function test_view_previous_result_link_points_to_result_route(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['user_id' => $user->id]);
        $service->complete($session->fresh());

        Livewire::actingAs($user)
            ->test(Take::class, ['slug' => 'mbti-personality'])
            ->assertSee(route('quiz.result', ['uuid' => $session->uuid]), false);
    }
}
