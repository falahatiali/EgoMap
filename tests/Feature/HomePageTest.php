<?php

namespace Tests\Feature;

use Database\Seeders\MbtiQuizSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MbtiQuizSeeder::class);
    }

    public function test_home_page_renders_in_english_by_default(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('She left? Stop chasing a ghost. Build your empire.', false);
        $response->assertSee('Diagnose. Detox. Deliver.', false);
        $response->assertSee('MBTI Personality Type', false);
        $response->assertSee('70 questions', false);
        $response->assertSee(route('onboarding'), false);
        $response->assertSee('Start relationship debug (free)', false);
        $response->assertSee('The Civilian', false);
        $response->assertSee('The Sovereign', false);
        $response->assertSee('dir="ltr"', false);
        $response->assertSee('id="eg-i18n"', false);
    }

    public function test_locale_can_be_switched_to_persian_via_redirect(): void
    {
        $response = $this->get(route('locale.switch', 'fa'));

        $response->assertRedirect();
        $this->assertSame('fa', session('locale'));

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('رفت؟ دیگر دنبال خاطره‌اش ندو. امپراتوری خودت را بساز.', false);
        $response->assertSee('تشخیص. سم‌زدایی. ساختن.', false);
        $response->assertSee('dir="rtl"', false);
    }

    public function test_locale_can_be_switched_via_json_without_redirect(): void
    {
        $response = $this->getJson(route('locale.switch', 'fa'));

        $response->assertNoContent();
        $this->assertSame('fa', session('locale'));
    }

    public function test_invalid_locale_returns_not_found(): void
    {
        $response = $this->get('/locale/de');

        $response->assertNotFound();
    }
}
