<?php

namespace Tests\Feature\Missions;

use App\Livewire\Missions\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\MissionEngine\Enums\EquipmentCategory;
use Modules\MissionEngine\Enums\EquipmentStatus;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\TestCase;

class MissionEquipmentWorkspaceTest extends TestCase
{
    use InteractsWithMissionEnrollment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMissionEngine();
    }

    public function test_add_equipment_preset_does_not_duplicate_same_name(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        $component = Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->call('addEquipmentPreset', 'belt')
            ->call('addEquipmentPreset', 'belt');

        $enrollment->refresh();
        $this->assertCount(1, $enrollment->field_values['equipment_items'] ?? []);
    }

    public function test_remove_equipment_item(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        $component = Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('newEquipmentName', 'Custom belt')
            ->set('newEquipmentCategory', EquipmentCategory::Belt->value)
            ->set('newEquipmentStatus', EquipmentStatus::Owned->value)
            ->call('addEquipmentItem')
            ->assertHasNoErrors();

        $itemId = $component->get('equipmentItems.0.id');

        $component
            ->call('removeEquipmentItem', $itemId)
            ->assertHasNoErrors();

        $enrollment->refresh();

        $this->assertSame([], $enrollment->field_values['equipment_items']);
    }

    public function test_save_equipment_general_notes_only(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('equipmentNotes', 'Knee sleeves at gym locker')
            ->call('saveEquipment')
            ->assertHasNoErrors();

        $enrollment->refresh();

        $this->assertSame('Knee sleeves at gym locker', $enrollment->field_values['equipment_notes']);
    }

    public function test_rejects_invalid_equipment_category(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('newEquipmentName', 'Item')
            ->set('newEquipmentCategory', 'invalid')
            ->call('addEquipmentItem')
            ->assertHasErrors('newEquipmentCategory');
    }

    public function test_hydrates_legacy_equipment_items_without_ids(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        $enrollment->update([
            'field_values' => array_merge($enrollment->field_values ?? [], [
                'equipment_items' => [
                    [
                        'name' => 'Old straps',
                        'category' => 'straps',
                        'brand' => '',
                        'status' => 'owned',
                        'notes' => '',
                    ],
                ],
            ]),
        ]);

        $component = Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment->fresh()])
            ->assertSet('equipmentItems.0.name', 'Old straps');

        $this->assertNotEmpty($component->get('equipmentItems.0.id'));
    }
}
