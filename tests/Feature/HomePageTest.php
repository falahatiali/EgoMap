<?php

namespace Tests\Feature;

use App\Livewire\Home\Protocol;
use App\Models\User;
use Database\Seeders\MbtiQuizSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MbtiQuizSeeder::class);
    }

    public function test_home_page_renders_clarity_landing(): void
    {
        $response = $this->get(route('home', ['locale' => 'en']));

        $response->assertOk();
        $response->assertSee('The relationship ended.', false);
        $response->assertSee('Your rebuild starts now.', false);
        $response->assertSee('For men after the breakup', false);
        $response->assertSee('You do not need another motivational quote', false);
        $response->assertSee('Open Emergency Mode', false);
        $response->assertSee('Stops the urge. Saves the dignity.', false);
        $response->assertSee('Where you stand right now', false);
        $response->assertSee('Unstable but aware', false);
        $response->assertSee('About to text her?', false);
        $response->assertSee('wait 20 minutes with you', false);
        $response->assertSee('Start Step 1', false);
        $response->assertSee('How it works', false);
        $response->assertSee('Right now, your only job is not to make it worse', false);
        $response->assertSee('rh-page', false);
        $response->assertDontSee('id="pain"', false);
    }

    public function test_home_nav_has_minimal_links_and_ctas(): void
    {
        $response = $this->get(route('home', ['locale' => 'en']));

        preg_match('/<header class="rh-nav.*?<\/header>/s', $response->getContent(), $matches);
        $navHtml = $matches[0] ?? '';

        $this->assertStringContainsString('rh-nav', $navHtml);
        $this->assertStringContainsString('How it works', $navHtml);
        $this->assertStringContainsString('Login', $navHtml);
        $this->assertStringContainsString('eg-lang-switch--nav', $navHtml);
        $this->assertStringContainsString('data-locale-switch="fa"', $navHtml);
        $this->assertStringContainsString(route('login', ['locale' => 'en']), $navHtml);
        $this->assertStringContainsString(route('onboarding', ['locale' => 'en']), $navHtml);
    }

    public function test_locale_middleware_sets_route_defaults_for_views(): void
    {
        URL::defaults([]);

        $response = $this->get('/fa');

        $response->assertOk();
        $response->assertSee(route('login', ['locale' => 'fa']), false);
    }

    public function test_home_nav_shows_profile_link_for_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'Mina Karimi']);

        $response = $this->actingAs($user)->get(route('home', ['locale' => 'fa']));

        $response->assertOk();
        $response->assertSee(route('profile', ['locale' => 'fa']), false);
        $response->assertSee('rh-nav__profile-name', false);
        $response->assertSee('Mina Karimi', false);
        $response->assertDontSee(__('landing.nav_login'), false);
    }

    public function test_start_step1_redirects_to_reboot_protocol_quiz(): void
    {
        Livewire::test(Protocol::class)
            ->assertSet('screen', 'landing')
            ->call('startCheckIn')
            ->assertRedirect(route('quiz.start', [
                'slug' => 'reboot-protocol',
                'locale' => 'en',
            ]));
    }

    public function test_onboarding_route_opens_triage_directly(): void
    {
        $this->get(route('onboarding', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('How long were you together?', false);
    }

    public function test_locale_can_be_switched_to_persian_via_redirect(): void
    {
        $response = $this->get(route('locale.switch', 'fa'), [
            'HTTP_REFERER' => url('/en'),
        ]);

        $response->assertRedirect('/fa');
        $this->assertSame('fa', session('locale'));

        $response = $this->get('/fa');

        $response->assertOk();
        $response->assertSee('رابطه تمام شد', false);
        $response->assertSee('شروع قدم ۱', false);
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
