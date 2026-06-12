<?php

namespace Tests\Feature\Api;

use App\Enums\QuestionType;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Quiz\ApiQuizGuestTokenService;
use App\Support\RebootProtocolQuiz;
use Database\Seeders\RebootProtocolQuizSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuizApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(RebootProtocolQuizSeeder::class);
    }

    public function test_quiz_meta_endpoint_returns_reboot_protocol(): void
    {
        $response = $this->getJson('/api/v1/quizzes/'.RebootProtocolQuiz::SLUG, [
            'Accept-Language' => 'en',
        ]);

        $response->assertOk()
            ->assertJsonPath('quiz.slug', RebootProtocolQuiz::SLUG)
            ->assertJsonStructure([
                'quiz' => ['name', 'description', 'question_count', 'welcome'],
            ]);

        $this->assertSame(10, $response->json('quiz.question_count'));
    }

    public function test_guest_can_start_session_and_receive_guest_token(): void
    {
        $response = $this->postJson('/api/v1/quizzes/'.RebootProtocolQuiz::SLUG.'/sessions');

        $response->assertCreated()
            ->assertJsonPath('screen', 'question')
            ->assertJsonPath('session.status', 'in_progress')
            ->assertJsonStructure([
                'guest_token',
                'question' => ['text', 'options'],
                'progress' => ['current', 'total', 'percent'],
            ]);

        $uuid = $response->json('session.uuid');
        $guestToken = $response->json('guest_token');

        $this->getJson("/api/v1/quiz-sessions/{$uuid}", [
            ApiQuizGuestTokenService::HEADER => $guestToken,
        ])->assertOk()
            ->assertJsonPath('screen', 'question');
    }

    public function test_guest_cannot_access_session_without_token(): void
    {
        $start = $this->postJson('/api/v1/quizzes/'.RebootProtocolQuiz::SLUG.'/sessions');
        $uuid = $start->json('session.uuid');

        $this->getJson("/api/v1/quiz-sessions/{$uuid}")
            ->assertForbidden();
    }

    public function test_guest_can_answer_through_completion(): void
    {
        $start = $this->postJson('/api/v1/quizzes/'.RebootProtocolQuiz::SLUG.'/sessions');
        $uuid = $start->json('session.uuid');
        $guestToken = $start->json('guest_token');

        $quiz = Quiz::query()->where('slug', RebootProtocolQuiz::SLUG)->firstOrFail();
        $questions = $quiz->questions()->where('is_active', true)->orderBy('sort_order')->with('options')->get();

        foreach ($questions as $question) {
            $headers = [ApiQuizGuestTokenService::HEADER => $guestToken];
            $firstOption = (string) $question->options()->orderBy('sort_order')->value('value');
            $payload = $question->type === QuestionType::MultipleChoice
                ? ['value' => [$firstOption]]
                : ['value' => $firstOption];

            $response = $this->postJson("/api/v1/quiz-sessions/{$uuid}/answers", $payload, $headers);

            if ($response->json('screen') === 'safety') {
                $response = $this->postJson("/api/v1/quiz-sessions/{$uuid}/safety-answer", [
                    'value' => 1,
                ], $headers);
            }
        }

        $result = $this->getJson("/api/v1/quiz-sessions/{$uuid}/result", $headers)
            ->assertOk()
            ->assertJsonPath('screen', 'result')
            ->assertJsonStructure([
                'result' => [
                    'hero_label',
                    'archetype',
                    'tagline',
                    'stability_score',
                    'email' => ['title', 'submit'],
                    'account_cta' => ['title', 'button'],
                ],
            ]);

        $this->assertNotEmpty($result->json('result.archetype'));
    }

    public function test_authenticated_user_session_is_claimed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/quizzes/'.RebootProtocolQuiz::SLUG.'/sessions');

        $response->assertCreated()
            ->assertJsonMissing(['guest_token']);

        $this->assertDatabaseHas('quiz_sessions', [
            'uuid' => $response->json('session.uuid'),
            'user_id' => $user->id,
        ]);
    }

    public function test_bearer_token_authenticates_quiz_session_start(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this->postJson(
            '/api/v1/quizzes/'.RebootProtocolQuiz::SLUG.'/sessions',
            [],
            ['Authorization' => 'Bearer '.$token],
        );

        $response->assertCreated()
            ->assertJsonMissing(['guest_token']);

        $this->assertDatabaseHas('quiz_sessions', [
            'uuid' => $response->json('session.uuid'),
            'user_id' => $user->id,
        ]);
    }
}
