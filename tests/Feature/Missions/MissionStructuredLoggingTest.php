<?php

namespace Tests\Feature\Missions;

use App\Livewire\Missions\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\MissionEngine\Enums\MealType;
use Modules\MissionEngine\Models\MissionNutritionDay;
use Modules\MissionEngine\Models\MissionWorkoutSession;
use Modules\MissionEngine\Services\MissionNutritionLogService;
use Modules\MissionEngine\Services\MissionWorkoutLogService;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\TestCase;

class MissionStructuredLoggingTest extends TestCase
{
    use InteractsWithMissionEnrollment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMissionEngine();
    }

    public function test_workout_session_stores_exercises_and_sets(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        $session = app(MissionWorkoutLogService::class)->saveSession($enrollment, $user, [
            'session_date' => now()->toDateString(),
            'day_key' => 'sat',
            'focus' => 'legs',
            'duration_minutes' => 90,
            'exercises' => [
                [
                    'name' => 'Squat',
                    'sets' => [
                        ['reps' => 10, 'weight' => 80.0],
                        ['reps' => 10, 'weight' => 85.0],
                        ['reps' => 8, 'weight' => 90.0],
                    ],
                ],
                [
                    'name' => 'Leg press',
                    'sets' => [
                        ['reps' => 12, 'weight' => 120.0],
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseHas('mission_workout_sessions', [
            'enrollment_id' => $enrollment->id,
            'focus' => 'legs',
        ]);

        $session->load('exercises.sets');

        $this->assertCount(2, $session->exercises);
        $this->assertCount(3, $session->exercises->firstWhere('name', 'Squat')?->sets);
    }

    public function test_nutrition_day_stores_meals_and_items_with_calories(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        $day = app(MissionNutritionLogService::class)->saveDay($enrollment, $user, [
            'log_date' => now()->toDateString(),
            'meals' => [
                [
                    'meal_type' => MealType::Breakfast->value,
                    'items' => [
                        ['name' => 'Boiled eggs', 'quantity' => 2, 'unit' => 'pcs', 'calories' => 140],
                        ['name' => 'Avocado', 'quantity' => 0.5, 'unit' => 'pcs', 'calories' => 120],
                    ],
                ],
            ],
        ]);

        $this->assertInstanceOf(MissionNutritionDay::class, $day);
        $this->assertSame(260, $day->total_calories);

        $day->load('meals.items');
        $this->assertCount(1, $day->meals);
        $this->assertCount(2, $day->meals->first()->items);
    }

    public function test_livewire_can_save_detailed_workout_session(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('workoutFocus', 'legs')
            ->set('workoutExercises', [
                [
                    'name' => 'Squat',
                    'notes' => '',
                    'sets' => [
                        ['reps' => '10', 'weight' => '80', 'notes' => ''],
                        ['reps' => '10', 'weight' => '85', 'notes' => ''],
                    ],
                ],
            ])
            ->call('saveWorkoutSession')
            ->assertHasNoErrors();

        $this->assertSame(1, MissionWorkoutSession::query()->where('enrollment_id', $enrollment->id)->count());
    }

    public function test_livewire_can_add_equipment_items(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('activeTab', 'equipment')
            ->call('addEquipmentPreset', 'belt')
            ->set('newEquipmentName', 'Custom wraps')
            ->set('newEquipmentCategory', 'straps')
            ->set('newEquipmentStatus', 'owned')
            ->call('addEquipmentItem')
            ->assertHasNoErrors();

        $enrollment->refresh();

        $items = $enrollment->field_values['equipment_items'] ?? [];
        $this->assertCount(2, $items);
        $this->assertSame('Custom wraps', $items[1]['name']);
    }
}
