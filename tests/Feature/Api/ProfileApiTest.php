<?php

namespace Tests\Feature\Api;

use App\Enums\SessionStatus;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Quiz\QuizSessionService;
use App\Support\RebootProtocolQuiz;
use Database\Seeders\RebootProtocolQuizSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(RebootProtocolQuizSeeder::class);
    }

    public function test_profile_requires_authentication(): void
    {
        $this->getJson('/api/v1/profile')
            ->assertUnauthorized();
    }

    public function test_profile_returns_user_info_and_completed_tests(): void
    {
        $user = User::factory()->create([
            'name' => 'Alex Recovery',
            'email' => 'alex@example.com',
        ]);
        Sanctum::actingAs($user);

        $quiz = Quiz::query()->where('slug', RebootProtocolQuiz::SLUG)->firstOrFail();
        $session = app(QuizSessionService::class)->start($quiz);
        $session->update([
            'user_id' => $user->id,
            'guest_token' => null,
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/profile');

        $response->assertOk()
            ->assertJsonPath('user.email', 'alex@example.com')
            ->assertJsonPath('user.name', 'Alex Recovery')
            ->assertJsonPath('stats.total', 1)
            ->assertJsonPath('stats.completed', 1)
            ->assertJsonPath('stats.in_progress', 0)
            ->assertJsonPath('tests.0.session_uuid', $session->uuid)
            ->assertJsonPath('tests.0.is_in_progress', false)
            ->assertJsonStructure([
                'user' => ['member_since_label', 'email_verified'],
                'labels' => ['my_tests_title', 'take_new_test'],
                'tests' => [
                    ['quiz_name', 'quiz_slug', 'status_label', 'palette'],
                ],
            ]);
    }

    public function test_profile_returns_completed_reboot_test_with_result_summary(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $quiz = Quiz::query()->where('slug', RebootProtocolQuiz::SLUG)->firstOrFail();
        $session = app(QuizSessionService::class)->start($quiz);
        $session->update([
            'user_id' => $user->id,
            'guest_token' => null,
        ]);

        app(QuizSessionService::class)->complete($session->fresh(['responses.question']));

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('tests.0.session_uuid', $session->uuid)
            ->assertJsonPath('tests.0.is_reboot_protocol', true)
            ->assertJsonStructure([
                'tests' => [
                    ['result_title', 'tagline', 'palette'],
                ],
            ]);
    }

    public function test_profile_filters_in_progress_tests(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $quiz = Quiz::query()->where('slug', RebootProtocolQuiz::SLUG)->firstOrFail();
        $completed = app(QuizSessionService::class)->start($quiz);
        $completed->update([
            'user_id' => $user->id,
            'guest_token' => null,
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        $inProgress = app(QuizSessionService::class)->start($quiz);
        $inProgress->update([
            'user_id' => $user->id,
            'guest_token' => null,
            'status' => SessionStatus::InProgress,
            'current_sort_order' => 3,
        ]);

        $this->getJson('/api/v1/profile?tests_filter=in_progress')
            ->assertOk()
            ->assertJsonPath('stats.total', 2)
            ->assertJsonPath('tests_filter', 'in_progress')
            ->assertJsonCount(1, 'tests')
            ->assertJsonPath('tests.0.session_uuid', $inProgress->uuid)
            ->assertJsonPath('tests.0.is_in_progress', true);
    }
}
