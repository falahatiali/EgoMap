<?php

namespace Tests\Feature\NoContact;

use App\Enums\NoContactStatus;
use App\Models\NoContactProtocol;
use App\Models\User;
use App\Services\NoContact\NoContactTimerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NoContactTimerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_creates_active_protocol_with_target_end(): void
    {
        Carbon::setTestNow('2026-05-25 12:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        $protocol = app(NoContactTimerService::class)->start(90);

        $this->assertSame(NoContactStatus::Active, $protocol->status);
        $this->assertSame(90, $protocol->duration_days);
        $this->assertSame($user->id, $protocol->user_id);
        $this->assertTrue($protocol->target_ends_at->equalTo(Carbon::parse('2026-08-23 12:00:00')));
    }

    public function test_display_state_survives_time_travel_simulating_refresh(): void
    {
        Carbon::setTestNow('2026-05-25 12:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(NoContactTimerService::class);
        $service->start(30);

        Carbon::setTestNow('2026-06-01 12:00:00');

        $state = $service->displayState();

        $this->assertSame('active', $state['mode']);
        $this->assertSame(23, $state['countdown']['days']);
        $this->assertGreaterThan(0, $state['remaining_seconds']);
    }

    public function test_slip_applies_penalty_days_instead_of_full_reset(): void
    {
        Carbon::setTestNow('2026-05-25 12:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(NoContactTimerService::class);
        $service->start(60);

        Carbon::setTestNow('2026-06-20 12:00:00');

        $reset = $service->recordSlip('felt_weak');

        $this->assertSame(1, $reset->slip_count);
        $this->assertTrue($reset->streak_started_at->equalTo(Carbon::parse('2026-05-30 12:00:00')));
        $this->assertTrue($reset->target_ends_at->equalTo(Carbon::parse('2026-07-29 12:00:00')));

        $state = $service->displayState();
        $this->assertLessThan(40, $state['progress_percent']);
    }

    public function test_guest_protocol_persists_via_guest_token_cookie(): void
    {
        Carbon::setTestNow('2026-05-25 12:00:00');

        $token = bin2hex(random_bytes(20));

        NoContactProtocol::factory()->forGuest($token)->create([
            'duration_days' => 30,
            'streak_started_at' => now(),
            'target_ends_at' => now()->addDays(30),
        ]);

        $response = $this->withUnencryptedCookie('egomap_guest', $token)
            ->get(route('no-contact', ['locale' => 'en']));

        $response->assertOk();
        $response->assertSee('Time remaining', false);
        $response->assertSee('30 days', false);
    }

    public function test_claim_transfers_guest_protocol_to_user_on_login(): void
    {
        $token = bin2hex(random_bytes(20));
        $protocol = NoContactProtocol::factory()->forGuest($token)->create();
        $user = User::factory()->create();

        $claimed = app(NoContactTimerService::class)->claimForUser($user, $token);

        $this->assertSame(1, $claimed);
        $this->assertSame($user->id, $protocol->fresh()->user_id);
    }

    public function test_protocol_marks_completed_when_target_passed(): void
    {
        Carbon::setTestNow('2026-05-25 12:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(NoContactTimerService::class);
        $service->start(7);

        Carbon::setTestNow('2026-06-05 12:00:00');

        $state = $service->displayState();

        $this->assertSame('completed', $state['mode']);
    }
}
