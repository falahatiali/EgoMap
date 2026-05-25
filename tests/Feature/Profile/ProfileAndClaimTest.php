<?php

namespace Tests\Feature\Profile;

use App\Enums\SessionStatus;
use App\Livewire\Auth\Login as LoginComponent;
use App\Livewire\Profile\Show;
use App\Livewire\Profile\TestShow;
use App\Livewire\Quiz\Take;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\Quiz\QuizSessionClaimService;
use App\Services\Quiz\QuizSessionService;
use Database\Seeders\MbtiQuizSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileAndClaimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(MbtiQuizSeeder::class);
    }

    public function test_guest_session_is_claimed_on_login(): void
    {
        Event::fake([LoginEvent::class]);

        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz, 'guest-token-abc');

        session(['guest_quiz_uuids' => [$session->uuid]]);

        $user = User::factory()->create(['email' => 'claimer@example.com']);

        app(QuizSessionClaimService::class)->claimForUser($user);

        $session->refresh();

        $this->assertSame($user->id, $session->user_id);
    }

    public function test_guest_session_claimed_by_matching_email(): void
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $session = app(QuizSessionService::class)->start($quiz, 'guest-token-xyz');
        $session->update(['email' => 'match@example.com', 'status' => SessionStatus::Completed]);

        $user = User::factory()->create(['email' => 'match@example.com']);

        app(QuizSessionClaimService::class)->claimForUser($user);

        $this->assertSame($user->id, $session->fresh()->user_id);
    }

    public function test_completed_local_storage_starts_fresh_session_for_retake(): void
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $completed = app(QuizSessionService::class)->start($quiz);
        $completed->update(['status' => SessionStatus::Completed, 'completed_at' => now()]);

        Livewire::test(Take::class, ['slug' => 'mbti-personality'])
            ->call('beginOrResume', $completed->uuid)
            ->assertRedirect(route('quiz.session', ['uuid' => QuizSession::query()->latest('id')->first()->uuid]));

        $this->assertSame(2, QuizSession::query()->count());
    }

    public function test_authenticated_user_with_completed_session_sees_previous_result_gate(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['user_id' => $user->id]);
        $service->complete($session->fresh());

        Livewire::actingAs($user)
            ->test(Take::class, ['slug' => 'mbti-personality'])
            ->assertSet('returningSession.uuid', $session->uuid)
            ->assertSee(__('quiz.returning_eyebrow'))
            ->assertSee(__('quiz.retake_test'));

        $this->assertSame(1, QuizSession::query()->count());
    }

    public function test_empty_in_progress_session_does_not_block_completed_result_gate(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);

        $completed = $service->start($quiz);
        $completed->update(['user_id' => $user->id]);
        $service->complete($completed->fresh());

        QuizSession::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'status' => SessionStatus::InProgress,
            'current_sort_order' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(Take::class, ['slug' => 'mbti-personality'])
            ->assertSet('returningSession.uuid', $completed->uuid)
            ->assertSee(__('quiz.view_previous_result'));

        $this->assertSame(
            SessionStatus::Abandoned,
            QuizSession::query()->whereKeyNot($completed->id)->first()->status,
        );
    }

    public function test_authenticated_user_can_start_retake(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['user_id' => $user->id]);
        $service->complete($session->fresh());

        Livewire::actingAs($user)
            ->test(Take::class, ['slug' => 'mbti-personality'])
            ->call('startRetake')
            ->assertRedirect(route('quiz.session', ['uuid' => QuizSession::query()->latest('id')->first()->uuid]));

        $this->assertSame(2, QuizSession::query()->count());
        $this->assertSame(SessionStatus::InProgress, QuizSession::query()->latest('id')->first()->status);
    }

    public function test_authenticated_user_resumes_in_progress_from_database(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['user_id' => $user->id]);
        $service->saveAnswer($session, $quiz->questions()->orderBy('sort_order')->firstOrFail(), 'A');

        Livewire::actingAs($user)
            ->test(Take::class, ['slug' => 'mbti-personality'])
            ->assertRedirect(route('quiz.session', ['uuid' => $session->uuid]));

        $this->assertSame(1, QuizSession::query()->count());
    }

    public function test_user_can_have_multiple_completed_sessions_for_same_quiz(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();

        QuizSession::factory()->count(2)->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        $this->assertSame(2, $user->quizSessions()->where('quiz_id', $quiz->id)->where('status', SessionStatus::Completed)->count());
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();

        QuizSession::factory()->completed()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        Livewire::actingAs($user)
            ->test(Show::class)
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee(__('profile.my_tests_title'));
    }

    public function test_profile_lists_completed_session_with_type_code_and_quiz_name(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['user_id' => $user->id, 'email' => $user->email]);

        foreach ($quiz->questions()->with('options')->orderBy('sort_order')->get() as $question) {
            $service->saveAnswer($session, $question, $question->options->first()->value);
        }

        $service->complete($session->fresh());
        $typeCode = (string) $session->fresh()->result->free_report['type_code'];

        Livewire::actingAs($user)
            ->test(Show::class)
            ->assertOk()
            ->assertSee($typeCode, false)
            ->assertSee($quiz->getTranslation('name', 'en', true), false)
            ->assertSee(__('profile.status_completed'), false);
    }

    public function test_user_can_view_completed_test_detail(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['user_id' => $user->id]);
        $service->complete($session->fresh());

        Livewire::actingAs($user)
            ->test(TestShow::class, ['uuid' => $session->uuid])
            ->assertOk()
            ->assertSee(__('profile.back_to_profile'));
    }

    public function test_login_redirects_to_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'profile@example.com',
            'password' => 'password123',
        ]);

        Livewire::test(LoginComponent::class)
            ->set('email', 'profile@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('profile'));
    }

    public function test_guest_cannot_view_profile(): void
    {
        $this->get(route('profile'))->assertRedirect(route('login'));
    }
}
