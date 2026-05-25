<?php

namespace Tests\Feature\Profile;

use App\Livewire\Profile\TestShow;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\Quiz\QuizSessionService;
use Database\Seeders\MbtiQuizSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTestDetailContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(MbtiQuizSeeder::class);
    }

    public function test_profile_test_detail_shows_full_result_sections(): void
    {
        $user = User::factory()->create();
        $session = $this->completeSessionFor($user);
        $typeCode = (string) $session->result->free_report['type_code'];

        Livewire::actingAs($user)
            ->test(TestShow::class, ['uuid' => $session->uuid])
            ->assertOk()
            ->assertSee(strtoupper($typeCode), false)
            ->assertSee(__('quiz.narrative_title'), false)
            ->assertSee(__('quiz.dimension_breakdown'), false)
            ->assertSee(__('quiz.strengths_title'), false)
            ->assertSee(__('quiz.growth_title'), false)
            ->assertSee(__('quiz.work_style_title'), false)
            ->assertSee(__('quiz.communication_title'), false)
            ->assertSee(__('quiz.famous_title'), false);
    }

    public function test_profile_test_detail_http_route_renders_for_owner(): void
    {
        $user = User::factory()->create();
        $session = $this->completeSessionFor($user);

        $this->actingAs($user)
            ->get(route('profile.test.show', ['uuid' => $session->uuid]))
            ->assertOk()
            ->assertSee(__('profile.retake'), false)
            ->assertSee(__('profile.open_full_result'), false)
            ->assertSee('eg-profile-result-stack', false);
    }

    public function test_profile_test_detail_forbids_other_users(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $session = $this->completeSessionFor($owner);

        $this->actingAs($intruder)
            ->get(route('profile.test.show', ['uuid' => $session->uuid]))
            ->assertForbidden();
    }

    public function test_profile_test_detail_allows_access_by_matching_email_before_claim(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['email' => $user->email, 'user_id' => null]);

        foreach ($quiz->questions()->with('options')->orderBy('sort_order')->get() as $question) {
            $service->saveAnswer($session, $question, $question->options->first()->value);
        }

        $service->complete($session->fresh());

        Livewire::actingAs($user)
            ->test(TestShow::class, ['uuid' => $session->uuid])
            ->assertOk();

        $this->assertSame($user->id, $session->fresh()->user_id);
    }

    public function test_in_progress_session_redirects_to_quiz_take(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['user_id' => $user->id]);
        $service->saveAnswer($session, $quiz->questions()->orderBy('sort_order')->firstOrFail(), 'A');

        Livewire::actingAs($user)
            ->test(TestShow::class, ['uuid' => $session->uuid])
            ->assertRedirect(route('quiz.session', ['uuid' => $session->uuid]));
    }

    private function completeSessionFor(User $user): QuizSession
    {
        $quiz = Quiz::query()->where('slug', 'mbti-personality')->firstOrFail();
        $service = app(QuizSessionService::class);
        $session = $service->start($quiz);
        $session->update(['user_id' => $user->id]);

        foreach ($quiz->questions()->with('options')->orderBy('sort_order')->get() as $question) {
            $service->saveAnswer($session, $question, $question->options->first()->value);
        }

        $service->complete($session->fresh());

        return $session->fresh(['result.outcomeProfile', 'quiz']);
    }
}
