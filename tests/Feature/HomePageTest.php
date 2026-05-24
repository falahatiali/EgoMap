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
        $response->assertSee('See your relationship patterns clearly.', false);
        $response->assertSee('MBTI Personality Type', false);
        $response->assertSee('70 questions', false);
        $response->assertSee(route('quiz.start', 'mbti-personality'), false);
        $response->assertSee('Start test', false);
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
        $response->assertSee('الگوهای رابطه‌ات را شفاف ببین.', false);
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
