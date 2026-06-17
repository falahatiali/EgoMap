<?php

namespace Tests\Feature\Nav;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavCompactLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_header_uses_compact_pill_navigation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('pricing', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('eg-nav-pill', false)
            ->assertSee(__('nav.virtue_forge'), false)
            ->assertSee(__('nav.community'), false)
            ->assertSee('eg-nav-user-menu', false)
            ->assertDontSee('Virtue Forge', false);
    }
}
