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
        $this->get(route('onboarding', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('How long were you together?', false);
    }

    public function test_guest_nav_is_minimal_on_home(): void
    {
        $response = $this->get(route('home', ['locale' => 'en']));

        $response->assertOk();

        preg_match('/<header class="rh-nav.*?<\/header>/s', $response->getContent(), $matches);
        $navHtml = $matches[0] ?? '';

        $this->assertStringNotContainsString(route('no-contact'), $navHtml);
    }

    public function test_triage_draft_survives_session_after_mid_flow(): void
    {
        Livewire::test(Triage::class)
            ->call('selectRelationshipDuration', 'one_to_three_years')
            ->call('selectBreakupDuration', 'weeks')
            ->assertSet('step', 3);

        Livewire::test(Triage::class)
            ->assertSet('step', 3)
            ->assertSet('relationshipDuration', 'one_to_three_years')
            ->assertSet('breakupDuration', 'weeks');
    }

    public function test_stalking_flow_shows_action_plan_with_activate_cta(): void
    {
        Livewire::test(Triage::class)
            ->call('selectRelationshipDuration', 'one_to_three_years')
            ->call('selectBreakupDuration', 'weeks')
            ->call('selectInitiator', 'them')
            ->call('selectStruggle', PrimaryStruggle::Stalking->value)
            ->assertSet('step', 5)
            ->call('finishDiagnosis')
            ->assertSee('Your priority', false)
            ->assertSee('Activate no-contact timer', false)
            ->assertSee(route('no-contact'), false);

        $this->assertSame(RecoveryPhase::Detox, app(RecoveryJourneyService::class)->currentPhase());
    }

    public function test_authenticated_triage_persists_on_user(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Triage::class)
            ->call('selectRelationshipDuration', 'six_to_twelve')
            ->call('selectBreakupDuration', 'days')
            ->call('selectInitiator', 'them')
            ->call('selectStruggle', PrimaryStruggle::GetBack->value);

        $user->refresh();

        $this->assertNotNull($user->recovery_triage_completed_at);
        $this->assertSame(RecoveryPhase::Detox->value, $user->recovery_phase);
        $this->assertSame(PrimaryStruggle::GetBack->value, $user->primary_struggle);
        $this->assertSame('six_to_twelve', $user->relationship_duration);
        $this->assertSame('them', $user->breakup_initiator);
    }

    public function test_profile_hides_tests_when_user_is_in_detox_phase(): void
    {
        $user = User::factory()->recoveryDetox()->create();

        Livewire::actingAs($user)
            ->test(Show::class)
            ->assertSee('No-contact command center', false)
            ->assertDontSee('eg-profile-tests-section', false);
    }

    public function test_no_contact_link_appears_after_activation(): void
    {
        $this->get(route('no-contact', ['locale' => 'en']))->assertOk();

        $this->get(route('home', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('nav.no_contact'), false);
    }
}
