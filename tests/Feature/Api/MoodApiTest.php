<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MoodApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config(['mood.ai_sage_enabled' => false]);
    }

    public function test_authenticated_user_can_log_mood_and_receive_fallback_wisdom(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/mood', [
            'emotion' => 'sadness',
            'intensity' => 7,
        ])->assertCreated();

        $response->assertJsonStructure([
            'entry' => [
                'id',
                'emotion',
                'emotion_label',
                'intensity',
                'ai_response' => ['empathy', 'challenge', 'reframe', 'idea_seed'],
            ],
        ]);

        $this->assertDatabaseHas('mood_entries', [
            'emotion' => 'sadness',
            'intensity' => 7,
        ]);
    }

    public function test_mood_dashboard_returns_today_entry_and_heatmap(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/mood', [
            'emotion' => 'energy',
            'intensity' => 8,
        ])->assertCreated();

        $this->getJson('/api/v1/mood')
            ->assertOk()
            ->assertJsonPath('today.emotion', 'energy')
            ->assertJsonStructure(['heatmap', 'emotions', 'labels']);
    }

    public function test_guest_cannot_access_mood_api(): void
    {
        $this->getJson('/api/v1/mood')->assertUnauthorized();
        $this->postJson('/api/v1/mood', ['emotion' => 'calm', 'intensity' => 5])->assertUnauthorized();
    }
}
