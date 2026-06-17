<?php

namespace Tests\Feature\Api;

use App\Enums\IdeaStatus;
use App\Models\User;
use App\Models\UserIdea;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IdeaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_create_mature_and_harvest_idea(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/v1/ideas', [
            'seed_text' => 'Improve my handwriting with daily practice.',
            'source' => 'ai_suggestion',
        ])->assertCreated();

        $ideaId = $create->json('idea.id');

        $this->postJson("/api/v1/ideas/{$ideaId}/mature")
            ->assertOk()
            ->assertJsonPath('idea.status', IdeaStatus::Mature->value);

        $this->postJson("/api/v1/ideas/{$ideaId}/harvest", [
            'goal_cadence' => 'monthly',
        ])
            ->assertOk()
            ->assertJsonPath('idea.status', IdeaStatus::Harvested->value)
            ->assertJsonPath('idea.progress', 0);

        $this->patchJson("/api/v1/ideas/{$ideaId}/progress", [
            'progress' => 35,
        ])
            ->assertOk()
            ->assertJsonPath('idea.progress', 35);
    }

    public function test_garden_groups_ideas_by_status(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        UserIdea::factory()->create(['user_id' => $user->id, 'status' => IdeaStatus::Raw]);
        UserIdea::factory()->create(['user_id' => $user->id, 'status' => IdeaStatus::Mature]);

        $this->getJson('/api/v1/ideas')
            ->assertOk()
            ->assertJsonCount(1, 'garden.raw')
            ->assertJsonCount(1, 'garden.mature');
    }
}
