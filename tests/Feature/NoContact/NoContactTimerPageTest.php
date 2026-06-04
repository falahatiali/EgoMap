<?php

namespace Tests\Feature\NoContact;

use App\Livewire\NoContact\Show;
use App\Models\User;
use App\Services\NoContact\NoContactTimerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Modules\GamificationEngine\Database\Seeders\GamificationEngineDatabaseSeeder;
use Tests\TestCase;

class NoContactTimerPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_contact_page_renders_setup_mode(): void
    {
        $response = $this->get(route('no-contact', ['locale' => 'en']));

        $response->assertOk();
        $response->assertSee('Ghost Mode', false);
        $response->assertSee('Activate Ghost Mode', false);
        $response->assertSee('90 days', false);
    }

    public function test_user_can_start_protocol_via_livewire(): void
    {
        Carbon::setTestNow('2026-05-25 12:00:00');

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Show::class)
            ->set('selectedDays', 30)
            ->call('startProtocol')
            ->assertRedirect(route('no-contact', ['locale' => 'en']));

        $this->get(route('no-contact', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('id="eg-ghost-mode-timer"', false)
            ->assertSee('Time remaining', false)
            ->assertSee('Day 1 of 30', false)
            ->assertSee('data-ghost-part="seconds"', false);

        $this->assertDatabaseHas('no_contact_protocols', [
            'user_id' => $user->id,
            'duration_days' => 30,
            'status' => 'active',
        ]);
    }

    public function test_farsi_locale_renders_persian_digits_and_ltr_clock(): void
    {
        Carbon::setTestNow('2026-05-25 12:00:00');

        $user = User::factory()->create();
        $this->withSession(['locale' => 'fa']);
        app()->setLocale('fa');

        Livewire::actingAs($user)
            ->test(Show::class)
            ->set('selectedDays', 90)
            ->call('startProtocol')
            ->assertRedirect(route('no-contact', ['locale' => 'fa']));

        $this->get(route('no-contact', ['locale' => 'fa']))
            ->assertOk()
            ->assertSee('زمان باقی‌مانده', false)
            ->assertSee('ساعت', false)
            ->assertSee('دقیقه', false)
            ->assertSee('dir="rtl"', false)
            ->assertSee('dir="ltr"', false)
            ->assertSee('data-digit-locale="fa"', false)
            ->assertSee('حالت شبح', false)
            ->assertSee('۰', false);
    }

    public function test_slip_triage_records_trigger_and_applies_penalty(): void
    {
        Carbon::setTestNow('2026-05-25 12:00:00');

        $this->seed(GamificationEngineDatabaseSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        app(NoContactTimerService::class)->start(60);

        Carbon::setTestNow('2026-06-10 12:00:00');

        Livewire::test(Show::class)
            ->call('beginSlipTriage')
            ->assertSet('showSlipForm', true)
            ->set('slipTrigger', 'felt_weak')
            ->call('recordSlip')
            ->assertSet('showSlipForm', false)
            ->assertSet('rewardToasts', fn (array $toasts): bool => count($toasts) > 0);

        $protocol = app(NoContactTimerService::class)->findActiveProtocol();

        $this->assertNotNull($protocol);
        $this->assertSame(1, $protocol->slip_count);
        $this->assertTrue($protocol->streak_started_at->equalTo(Carbon::parse('2026-05-30 12:00:00')));

        $this->assertDatabaseHas('ghost_mode_events', [
            'no_contact_protocol_id' => $protocol->id,
            'type' => 'slip',
            'trigger' => 'felt_weak',
        ]);
    }

    public function test_active_page_shows_ghost_mode_ai_sections(): void
    {
        Carbon::setTestNow('2026-05-25 12:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        app(NoContactTimerService::class)->start(30);

        $this->get(route('no-contact', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('Panic Button', false)
            ->assertSee('Data Blackhole', false)
            ->assertSee('Truth Flashlight', false)
            ->assertSee('Integrity Shield', false);
    }
}
