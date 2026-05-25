<?php

namespace Tests\Feature\Profile;

use App\Enums\SessionStatus;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\Profile\UserQuizHistoryService;
use App\Services\Quiz\QuizSessionService;
use Database\Seeders\MbtiQuizSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserQuizHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MbtiQuizSeeder::class);
    }

    public function test_it_finds_sessions_by_user_id_and_email(): void
    {
        $user = User::factory()->create(['email' => 'history@example.com']);
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();

        QuizSession::factory()->completed()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'email' => $user->email,
        ]);

        QuizSession::factory()->create([
            'user_id' => null,
            'quiz_id' => $quiz->id,
            'email' => $user->email,
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        $records = app(UserQuizHistoryService::class)->recordsForUser($user);

        $this->assertCount(2, $records);
        $this->assertTrue($records->every(fn (array $record): bool => $record['session']->user_id === $user->id));
    }

    public function test_it_excludes_abandoned_sessions(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();

        QuizSession::factory()->completed()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        QuizSession::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'status' => SessionStatus::Abandoned,
        ]);

        $records = app(UserQuizHistoryService::class)->recordsForUser($user);

        $this->assertCount(1, $records);
    }

    public function test_record_includes_type_code_and_detail_url_for_completed_session(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['user_id' => $user->id]);

        foreach ($quiz->questions()->with('options')->orderBy('sort_order')->get() as $question) {
            $service->saveAnswer($session, $question, $question->options->first()->value);
        }

        $service->complete($session->fresh());
        $session->refresh();

        $record = app(UserQuizHistoryService::class)->recordsForUser($user)->first();

        $this->assertNotNull($record);
        $this->assertFalse($record['is_in_progress']);
        $this->assertNotEmpty($record['type_code']);
        $this->assertSame(4, strlen($record['type_code']));
        $this->assertStringContainsString($session->uuid, $record['detail_url']);
        $this->assertSame(route('profile.test.show', ['uuid' => $session->uuid]), $record['detail_url']);
    }

    public function test_record_for_in_progress_session_links_to_quiz_session(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['user_id' => $user->id]);
        $service->saveAnswer($session, $quiz->questions()->orderBy('sort_order')->firstOrFail(), 'A');

        $record = app(UserQuizHistoryService::class)->recordsForUser($user)->first();

        $this->assertTrue($record['is_in_progress']);
        $this->assertSame(route('quiz.session', ['uuid' => $session->uuid]), $record['detail_url']);
        $this->assertGreaterThan(0, $record['progress_percent']);
    }
}
