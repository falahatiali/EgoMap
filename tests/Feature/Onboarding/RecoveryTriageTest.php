<?php

namespace Tests\Feature\Onboarding;

use App\Enums\PrimaryStruggle;
use App\Enums\RecoveryPhase;
use App\Livewire\Onboarding\Triage;
use App\Livewire\Profile\Show;
use App\Models\User;
use App\Services\Recovery\RecoveryJourneyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecoveryTriageTest extends TestCase
{
    use RefreshDatabase;

    public function test_triage_page_renders_first_question(): void
    {
        $this->get(route('onboarding'))
            ->assertOk()
            ->assertSee('How long since the breakup?', false);
    }

    public function test_stalking_recommends_no_contact_timer(): void
    {
        Livewire::test(Triage::class)
            ->call('selectDuration', 'weeks')
            ->call('selectStruggle', PrimaryStruggle::Stalking->value)
            ->assertSee('Start the no-contact protocol', false)
            ->assertSee(route('no-contact'), false);

        $phase = app(RecoveryJourneyService::class)->currentPhase();

        $this->assertSame(RecoveryPhase::Detox, $phase);
    }

    public function test_worthless_recommends_quiz_debug(): void
    {
        Livewire::test(Triage::class)
            ->call('selectDuration', 'months')
            ->call('selectStruggle', PrimaryStruggle::Worthless->value)
            ->assertSee('Run relationship debug', false)
            ->assertSee(route('quiz.start', 'mbti-personality'), false);

        $this->assertSame(RecoveryPhase::Diagnose, app(RecoveryJourneyService::class)->currentPhase());
    }

    public function test_authenticated_triage_persists_on_user(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Triage::class)
            ->call('selectDuration', 'days')
            ->call('selectStruggle', PrimaryStruggle::GetBack->value);

        $user->refresh();

        $this->assertNotNull($user->recovery_triage_completed_at);
        $this->assertSame(RecoveryPhase::Detox->value, $user->recovery_phase);
        $this->assertSame(PrimaryStruggle::GetBack->value, $user->primary_struggle);
    }

    public function test_profile_hides_tests_when_user_is_in_detox_phase(): void
    {
        $user = User::factory()->recoveryDetox()->create();

        Livewire::actingAs($user)
            ->test(Show::class)
            ->assertSee('No-contact command center', false)
            ->assertDontSee('eg-profile-tests-section', false);
    }
}
