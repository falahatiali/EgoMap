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
            ->assertSee('eg-nav-drawer', false)
            ->assertSee('egNavDrawer', false)
            ->assertSee(__('nav.virtue_forge'), false)
            ->assertSee(__('nav.community'), false)
            ->assertSee('eg-nav-user-menu', false)
            ->assertDontSee('Virtue Forge', false);
    }

    public function test_mobile_drawer_includes_account_actions(): void
    {
        $user = User::factory()->create(['name' => 'Jordan Lee']);

        $this->actingAs($user)
            ->get(route('pricing', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('eg-nav-drawer__user', false)
            ->assertSee('Jordan Lee', false)
            ->assertSee(__('auth.logout'), false);
    }

    public function test_profile_page_does_not_render_duplicate_page_nav_strip(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('eg-nav__links', false)
            ->assertDontSee('eg-page-nav', false);
    }
}
