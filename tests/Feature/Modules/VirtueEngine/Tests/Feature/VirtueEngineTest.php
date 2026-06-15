<?php

namespace Tests\Feature\Modules\VirtueEngine\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\GamificationEngine\Database\Seeders\GamificationEngineDatabaseSeeder;
use Modules\GamificationEngine\Models\GamificationWallet;
use Modules\VirtueEngine\Database\Seeders\VirtueEngineDatabaseSeeder;
use Modules\VirtueEngine\Models\VirtueHabit;
use Modules\VirtueEngine\Models\VirtueRoutine;
use Modules\VirtueEngine\Services\VirtueAIService;
use Tests\TestCase;

class VirtueEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GamificationEngineDatabaseSeeder::class);
        $this->seed(VirtueEngineDatabaseSeeder::class);

        $this->user = User::factory()->create();

        GamificationWallet::query()->create(['user_id' => $this->user->id]);
    }

    // ─── Habit endpoints ─────────────────────────────────────────────────────

    public function test_authenticated_user_can_list_predefined_habits(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/virtue/habits')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'slug', 'name', 'category', 'category_label', 'category_icon', 'ai_steps']],
            ]);
    }

    public function test_listing_habits_requires_authentication(): void
    {
        $this->getJson('/api/v1/virtue/habits')->assertUnauthorized();
    }

    public function test_predefined_habits_are_seeded_correctly(): void
    {
        $this->assertDatabaseCount('virtue_habits', 10);
        $this->assertDatabaseHas('virtue_habits', ['slug' => 'sarcasm-and-taunts', 'is_predefined' => true]);
    }

    // ─── Routine endpoints ───────────────────────────────────────────────────

    public function test_user_can_start_a_new_routine(): void
    {
        $habit = VirtueHabit::query()->first();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/virtue/routines', [
                'virtue_habit_id' => $habit->id,
                'personal_note' => 'I want to be kinder in conversations.',
                'goal_type' => 'days_count',
                'goal_target' => 21,
            ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id', 'uuid', 'status', 'goal_type', 'goal_target',
                    'current_streak', 'best_streak', 'progress_percent', 'habit',
                ],
            ])
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.goal_target', 21);
    }

    public function test_starting_routine_validates_required_fields(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/virtue/routines', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['virtue_habit_id']);
    }

    public function test_starting_routine_requires_valid_habit_id(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/virtue/routines', ['virtue_habit_id' => 9999])
            ->assertUnprocessable();
    }

    public function test_user_can_list_their_routines(): void
    {
        $habit = VirtueHabit::query()->first();
        VirtueRoutine::query()->create([
            'user_id' => $this->user->id,
            'virtue_habit_id' => $habit->id,
            'goal_type' => 'days_count',
            'goal_target' => 21,
            'status' => 'active',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/virtue/routines')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_cannot_see_other_users_routines(): void
    {
        $otherUser = User::factory()->create();
        $habit = VirtueHabit::query()->first();

        VirtueRoutine::query()->create([
            'user_id' => $otherUser->id,
            'virtue_habit_id' => $habit->id,
            'goal_type' => 'days_count',
            'goal_target' => 21,
            'status' => 'active',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/virtue/routines')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ─── Progress endpoints ───────────────────────────────────────────────────

    public function test_user_can_log_a_success(): void
    {
        $this->mockAiEncouragement();
        $habit = VirtueHabit::query()->first();

        $routine = VirtueRoutine::query()->create([
            'user_id' => $this->user->id,
            'virtue_habit_id' => $habit->id,
            'goal_type' => 'days_count',
            'goal_target' => 21,
            'status' => 'active',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/virtue/routines/{$routine->id}/success", [
                'situation' => 'Had a disagreement but stayed calm and direct.',
                'emotional_state' => 'proud',
            ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'success_log' => ['id', 'situation', 'points_earned', 'logged_at'],
                    'routine' => ['id', 'current_streak', 'total_successes'],
                    'gamification',
                    'routine_completed',
                ],
            ]);

        $this->assertDatabaseCount('virtue_success_logs', 1);
        $this->assertEquals(1, $routine->fresh()->total_successes);
        $this->assertEquals(1, $routine->fresh()->current_streak);
    }

    public function test_logging_success_updates_streak(): void
    {
        $this->mockAiEncouragement();
        $habit = VirtueHabit::query()->first();

        $routine = VirtueRoutine::query()->create([
            'user_id' => $this->user->id,
            'virtue_habit_id' => $habit->id,
            'goal_type' => 'days_count',
            'goal_target' => 21,
            'status' => 'active',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/virtue/routines/{$routine->id}/success", []);

        $this->assertEquals(1, $routine->fresh()->current_streak);
        $this->assertEquals(1, $routine->fresh()->best_streak);
    }

    public function test_routine_is_auto_completed_when_goal_reached(): void
    {
        $this->mockAiEncouragement();
        $habit = VirtueHabit::query()->first();

        $routine = VirtueRoutine::query()->create([
            'user_id' => $this->user->id,
            'virtue_habit_id' => $habit->id,
            'goal_type' => 'success_count',
            'goal_target' => 1,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/virtue/routines/{$routine->id}/success", []);

        $response->assertOk()
            ->assertJsonPath('data.routine_completed', true)
            ->assertJsonPath('data.routine.status', 'completed');
    }

    public function test_user_can_log_a_slip(): void
    {
        $this->mockAiSlipResponse();
        $habit = VirtueHabit::query()->first();

        $routine = VirtueRoutine::query()->create([
            'user_id' => $this->user->id,
            'virtue_habit_id' => $habit->id,
            'goal_type' => 'days_count',
            'goal_target' => 21,
            'status' => 'active',
            'current_streak' => 5,
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/virtue/routines/{$routine->id}/slip", [
                'what_happened' => 'Snapped at a colleague with a sarcastic comment.',
            ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'slip_log' => ['id', 'what_happened', 'logged_at'],
                    'routine' => ['id', 'current_streak', 'total_slips'],
                    'gamification',
                    'ai_response',
                    'punishment_suggestions',
                ],
            ]);

        $this->assertDatabaseCount('virtue_slip_logs', 1);
        $this->assertEquals(0, $routine->fresh()->current_streak);
        $this->assertEquals(1, $routine->fresh()->total_slips);
    }

    public function test_user_can_manually_complete_routine(): void
    {
        $habit = VirtueHabit::query()->first();

        $routine = VirtueRoutine::query()->create([
            'user_id' => $this->user->id,
            'virtue_habit_id' => $habit->id,
            'goal_type' => 'days_count',
            'goal_target' => 21,
            'status' => 'active',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/virtue/routines/{$routine->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.routine.status', 'completed');

        $this->assertDatabaseHas('virtue_routines', [
            'id' => $routine->id,
            'status' => 'completed',
        ]);
    }

    public function test_user_cannot_access_another_users_routine(): void
    {
        $otherUser = User::factory()->create();
        $habit = VirtueHabit::query()->first();

        $routine = VirtueRoutine::query()->create([
            'user_id' => $otherUser->id,
            'virtue_habit_id' => $habit->id,
            'goal_type' => 'days_count',
            'goal_target' => 21,
            'status' => 'active',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/virtue/routines/{$routine->id}")
            ->assertNotFound();
    }

    public function test_progress_endpoint_returns_full_details(): void
    {
        $habit = VirtueHabit::query()->first();

        $routine = VirtueRoutine::query()->create([
            'user_id' => $this->user->id,
            'virtue_habit_id' => $habit->id,
            'goal_type' => 'days_count',
            'goal_target' => 21,
            'status' => 'active',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/virtue/routines/{$routine->id}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'habit', 'recent_successes', 'progress_percent', 'current_streak'],
            ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function mockAiEncouragement(): void
    {
        $this->mock(VirtueAIService::class, function ($mock): void {
            $mock->shouldReceive('encourageSuccess')->andReturn('Great job staying direct today!');
        });
    }

    private function mockAiSlipResponse(): void
    {
        $this->mock(VirtueAIService::class, function ($mock): void {
            $mock->shouldReceive('generateSlipResponse')->andReturn([
                'acknowledgement' => 'Slipping happens. You noticed it — that matters.',
                'micro_task' => 'Write one honest apology message right now.',
                'motivation_close' => 'One slip does not erase your progress.',
                'points_deducted_message' => 'You lost 8 points, but gained awareness.',
            ]);
        });
    }
}
