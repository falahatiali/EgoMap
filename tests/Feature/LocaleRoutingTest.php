<?php

namespace Tests\Feature;

use Database\Seeders\MbtiQuizSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MbtiQuizSeeder::class);
    }

    public function test_root_shows_language_gate_without_session_locale(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Choose your language', false)
            ->assertSee('Continue in English', false);
    }

    public function test_root_redirects_when_session_locale_is_set(): void
    {
        $this->withSession(['locale' => 'fa'])
            ->get('/')
            ->assertRedirect('/fa');
    }

    public function test_english_home_is_fully_english(): void
    {
        $response = $this->get('/en');

        $response->assertOk();
        $response->assertSee('The relationship ended.', false);
        $response->assertSee('You do not need another motivational quote', false);
        $response->assertDontSee('تعقیب را قطع کن', false);
    }

    public function test_persian_home_is_fully_persian(): void
    {
        $response = $this->get('/fa');

        $response->assertOk();
        $response->assertSee('رابطه تمام شد', false);
        $response->assertSee('به انگیزشی دیگر نیاز نداری', false);
        $response->assertSee('برای مردی که بعد جدایی اینجاست', false);
    }

    public function test_onboarding_lives_under_locale_prefix(): void
    {
        $this->get('/fa/start')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('چند وقت با هم بودید؟', false);
    }
}
