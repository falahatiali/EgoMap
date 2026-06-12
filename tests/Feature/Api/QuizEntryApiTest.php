<?php

namespace Tests\Feature\Api;

use App\Enums\SessionStatus;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Quiz\ApiQuizGuestTokenService;
use App\Services\Quiz\QuizSessionService;
use App\Support\RebootProtocolQuiz;
use Database\Seeders\RebootProtocolQuizSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuizEntryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(RebootProtocolQuizSeeder::class);
    }

    public function test_entry_returns_show_previous_for_completed_guest_session(): void
    {
        $start = $this->postJson('/api/v1/quizzes/'.RebootProtocolQuiz::SLUG.'/sessions');
        $uuid = $start->json('session.uuid');
        $guestToken = $start->json('guest_token');

        $session = app(QuizSessionService::class)->findByUuid($uuid);
        $session->update([
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->getJson(
            '/api/v1/quizzes/'.RebootProtocolQuiz::SLUG.'/entry?resume_uuid='.$uuid,
            [ApiQuizGuestTokenService::HEADER => $guestToken],
        );

        $response->assertOk()
            ->assertJsonPath('action', 'show_previous')
            ->assertJsonPath('session_uuid', $uuid)
            ->assertJsonStructure(['returning' => ['title', 'summary', 'view_result_label', 'retake_label']]);
    }

    public function test_entry_returns_show_previous_for_authenticated_completed_session(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $quiz = Quiz::query()->where('slug', RebootProtocolQuiz::SLUG)->firstOrFail();
        $session = app(QuizSessionService::class)->start($quiz);
        $session->update([
            'user_id' => $user->id,
            'guest_token' => null,
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        $this->getJson('/api/v1/quizzes/'.RebootProtocolQuiz::SLUG.'/entry')
            ->assertOk()
            ->assertJsonPath('action', 'show_previous')
            ->assertJsonPath('session_uuid', $session->uuid);
    }

    public function test_entry_returns_start_fresh_when_no_prior_session(): void
    {
        $this->getJson('/api/v1/quizzes/'.RebootProtocolQuiz::SLUG.'/entry')
            ->assertOk()
            ->assertJsonPath('action', 'start_fresh')
            ->assertJsonStructure(['guest_token']);
    }
}
