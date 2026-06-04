<?php

namespace Tests\Feature\Missions;

use App\Livewire\Missions\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\MissionEngine\Enums\MissionActivityEvent;
use Modules\MissionEngine\Models\MissionSupplementIntake;
use Modules\MissionEngine\Models\MissionSupplementProduct;
use Modules\MissionEngine\Services\MissionSupplementLogService;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\TestCase;

class MissionSupplementLoggingTest extends TestCase
{
    use InteractsWithMissionEnrollment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMissionEngine();
    }

    public function test_mount_seeds_default_supplement_products(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment]);

        $this->assertGreaterThanOrEqual(2, MissionSupplementProduct::query()
            ->where('enrollment_id', $enrollment->id)
            ->count());
    }

    public function test_add_product_increments_sort_order(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $service = app(MissionSupplementLogService::class);

        $first = $service->addProduct($enrollment, ['name' => 'Creatine']);
        $second = $service->addProduct($enrollment, ['name' => 'Beta-alanine', 'brand' => 'Optimum']);

        $this->assertGreaterThan($first->sort_order, $second->sort_order);
        $this->assertSame('Optimum', $second->brand);
    }

    public function test_log_intake_persists_and_logs_activity(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $product = app(MissionSupplementLogService::class)->addProduct($enrollment, [
            'name' => 'Whey',
            'brand' => 'MyProtein',
            'default_amount' => '2',
        ]);

        $intake = app(MissionSupplementLogService::class)->logIntake($enrollment, $user, [
            'intake_date' => now()->toDateString(),
            'supplement_product_id' => $product->id,
            'product_name' => 'Whey',
            'brand' => 'MyProtein',
            'amount' => 2,
            'unit' => 'scoop',
            'notes' => 'Post workout',
        ]);

        $this->assertInstanceOf(MissionSupplementIntake::class, $intake);
        $this->assertDatabaseHas('mission_supplement_intakes', [
            'enrollment_id' => $enrollment->id,
            'product_name' => 'Whey',
        ]);
        $this->assertDatabaseHas('mission_activity_logs', [
            'enrollment_id' => $enrollment->id,
            'event_type' => MissionActivityEvent::SupplementLogged->value,
        ]);
    }

    public function test_livewire_adds_product_and_logs_intake(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('activeTab', 'supplements')
            ->set('newSupplementName', 'Vitamin D')
            ->set('newSupplementBrand', 'HealthCo')
            ->call('addSupplementProduct')
            ->assertHasNoErrors()
            ->set('intakeProductName', 'Vitamin D')
            ->set('intakeBrand', 'HealthCo')
            ->set('intakeAmount', '1')
            ->set('intakeUnit', 'capsule')
            ->call('logSupplementIntake')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mission_supplement_intakes', [
            'enrollment_id' => $enrollment->id,
            'product_name' => 'Vitamin D',
        ]);
    }

    public function test_select_supplement_product_prefills_intake_form(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $product = app(MissionSupplementLogService::class)->addProduct($enrollment, [
            'name' => 'Creatine',
            'brand' => 'Bulk',
            'default_unit' => 'g',
            'default_amount' => '5',
        ]);

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->call('selectSupplementProduct', $product->id)
            ->assertSet('intakeProductName', 'Creatine')
            ->assertSet('intakeBrand', 'Bulk')
            ->assertSet('intakeUnit', 'g')
            ->assertSet('intakeAmount', '5');
    }
}
