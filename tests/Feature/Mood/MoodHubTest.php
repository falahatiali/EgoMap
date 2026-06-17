<?php

namespace Tests\Feature\Mood;

use App\Enums\IdeaStatus;
use App\Livewire\Mood\MoodHub;
use App\Models\User;
use App\Models\UserIdea;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MoodHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config(['mood.ai_sage_enabled' => false]);
    }

    public function test_guest_is_redirected_from_today_page(): void
    {
        $this->get(route('today', ['locale' => 'en']))
            ->assertRedirect(route('login', ['locale' => 'en']));
    }

    public function test_authenticated_user_can_log_mood_and_mature_idea(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(MoodHub::class)
            ->assertSee(__('mood.compass_title'))
            ->set('selectedEmotion', 'sadness')
            ->set('intensity', 7)
            ->call('logMood')
            ->assertSet('todayEntry.emotion', 'sadness');

        $this->assertDatabaseHas('mood_entries', [
            'user_id' => $user->id,
            'emotion' => 'sadness',
            'intensity' => 7,
        ]);

        Livewire::actingAs($user)
            ->test(MoodHub::class)
            ->set('manualSeed', 'Improve my handwriting.')
            ->call('addManualSeed')
            ->assertHasNoErrors();

        $idea = UserIdea::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($idea);
        $this->assertSame(IdeaStatus::Raw, $idea->status);

        Livewire::actingAs($user)
            ->test(MoodHub::class)
            ->call('matureIdea', $idea->id);

        $this->assertSame(IdeaStatus::Mature, $idea->fresh()->status);
    }
}
