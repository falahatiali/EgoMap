<?php

namespace Tests\Feature\Profile;

use App\Enums\SessionStatus;
use App\Livewire\Profile\Show;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\Quiz\QuizSessionService;
use Database\Seeders\MbtiQuizSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(MbtiQuizSeeder::class);
    }

    public function test_profile_http_route_lists_completed_test_for_authenticated_user(): void
    {
        $user = User::factory()->recoveryDiagnose()->create();
        $session = $this->completeSessionFor($user);

        $response = $this->actingAs($user)->get(route('profile'));

        $response->assertOk();
        $response->assertSee(__('profile.my_tests_title'), false);
        $response->assertSee($session->result->free_report['type_code'], false);
        $response->assertSee(__('profile.status_completed'), false);
    }

    public function test_profile_filter_completed_shows_only_completed_sessions(): void
    {
        $user = User::factory()->recoveryDiagnose()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $completed = $this->completeSessionFor($user);

        QuizSession::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'status' => SessionStatus::InProgress,
            'current_sort_order' => 2,
        ]);

        Livewire::actingAs($user)
            ->test(Show::class)
            ->set('filter', 'completed')
            ->assertSet('filter', 'completed')
            ->assertViewHas('totalCompleted', 1)
            ->assertViewHas('totalInProgress', 1)
            ->assertSee($completed->result->free_report['type_code'], false);
    }

    public function test_profile_filter_in_progress_shows_progress_label(): void
    {
        $user = User::factory()->recoveryDiagnose()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['user_id' => $user->id]);
        $service->saveAnswer($session, $quiz->questions()->orderBy('sort_order')->firstOrFail(), 'A');

        Livewire::actingAs($user)
            ->test(Show::class)
            ->set('filter', 'in_progress')
            ->assertSee(__('profile.status_in_progress'), false)
            ->assertSee('2', false)
            ->assertSee('70', false);
    }

    public function test_profile_shows_empty_state_when_user_has_no_sessions(): void
    {
        $user = User::factory()->recoveryDiagnose()->create();

        Livewire::actingAs($user)
            ->test(Show::class)
            ->assertSee(__('profile.no_tests_title'), false)
            ->assertViewHas('totalTests', 0);
    }

    public function test_profile_test_card_links_to_test_detail_for_completed_session(): void
    {
        $user = User::factory()->recoveryDiagnose()->create();
        $session = $this->completeSessionFor($user);

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee(route('profile.test.show', ['uuid' => $session->uuid]), false);
    }

    public function test_profile_claims_email_only_session_on_visit(): void
    {
        $user = User::factory()->create(['email' => 'claim-me@example.com']);
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();

        $orphan = QuizSession::factory()->completed()->create([
            'user_id' => null,
            'quiz_id' => $quiz->id,
            'email' => $user->email,
        ]);

        $this->actingAs($user)->get(route('profile'))->assertOk();

        $this->assertSame($user->id, $orphan->fresh()->user_id);
    }

    private function completeSessionFor(User $user): QuizSession
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['user_id' => $user->id, 'email' => $user->email]);

        foreach ($quiz->questions()->with('options')->orderBy('sort_order')->get() as $question) {
            $service->saveAnswer($session, $question, $question->options->first()->value);
        }

        $service->complete($session->fresh());

        return $session->fresh(['result', 'quiz']);
    }
}
