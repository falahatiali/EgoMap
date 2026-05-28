<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_renders_in_persian_after_locale_session_is_set(): void
    {
        $this->withSession(['locale' => 'fa']);
        app()->setLocale('fa');

        $this->get(route('onboarding', ['locale' => 'fa']))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('چند وقت با هم بودید؟', false);
    }

    public function test_no_contact_renders_in_persian_after_locale_session_is_set(): void
    {
        $this->withSession(['locale' => 'fa']);
        app()->setLocale('fa');

        $this->get('/fa/no-contact')
            ->assertOk()
            ->assertSee('حالت شبح', false);
    }

    public function test_locale_switch_endpoint_persists_session(): void
    {
        $response = $this->getJson(route('locale.switch', 'fa'));

        $response->assertNoContent();
        $this->assertSame('fa', session('locale'));
    }
}
