<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_renders_in_english_by_default(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('See your relationship patterns clearly.', false);
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
