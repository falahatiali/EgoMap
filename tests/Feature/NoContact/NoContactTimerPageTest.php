<?php

namespace Tests\Feature\NoContact;

use App\Livewire\NoContact\Show;
use App\Models\User;
use App\Services\NoContact\NoContactTimerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
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
            ->assertDontSee('Activate Ghost Mode', false)
            ->assertSee('Time remaining', false)
            ->assertSee('30 days', false);

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
            ->assertSee('زمان باقی‌مانده', false)
            ->assertSee('ساعت', false)
            ->assertSee('دقیقه', false)
            ->assertSee('dir="rtl"', false)
            ->assertSee('dir="ltr"', false)
            ->assertSee('data-digit-locale="fa"', false)
            ->assertSee('حالت شبح', false)
            ->assertSee('۰', false);
    }

    public function test_slip_requires_confirmation_then_resets(): void
    {
        Carbon::setTestNow('2026-05-25 12:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        app(NoContactTimerService::class)->start(60);

        Carbon::setTestNow('2026-06-10 12:00:00');

        Livewire::test(Show::class)
            ->call('recordSlip')
            ->assertSet('confirmSlip', true)
            ->call('recordSlip')
            ->assertSet('confirmSlip', false);

        $protocol = app(NoContactTimerService::class)->findActiveProtocol();

        $this->assertNotNull($protocol);
        $this->assertSame(1, $protocol->slip_count);
        $this->assertTrue($protocol->streak_started_at->equalTo(Carbon::parse('2026-06-10 12:00:00')));
    }
}
