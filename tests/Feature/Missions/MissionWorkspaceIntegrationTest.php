<?php

namespace Tests\Feature\Missions;

use App\Livewire\Missions\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\MissionEngine\Enums\MealType;
use Modules\MissionEngine\Models\MissionDailyReport;
use Modules\MissionEngine\Models\MissionNutritionDay;
use Modules\MissionEngine\Models\MissionSupplementIntake;
use Modules\MissionEngine\Models\MissionWorkoutSession;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\TestCase;

class MissionWorkspaceIntegrationTest extends TestCase
{
    use InteractsWithMissionEnrollment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMissionEngine();
    }

    public function test_full_day_logging_across_all_workspace_tabs(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $date = now()->toDateString();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('logDate', $date)
            ->set('gymDays', ['sat'])
            ->set('preferredGymTime', '18:00')
            ->call('saveSchedule')
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
            ->set('nutritionMeals', [
                [
                    'meal_type' => MealType::Breakfast->value,
                    'meal_time' => '08:00',
                    'notes' => '',
                    'items' => [
                        ['name' => 'Eggs', 'quantity' => '2', 'unit' => 'count', 'calories' => '', 'protein_g' => ''],
                        ['name' => 'Avocado', 'quantity' => '1', 'unit' => 'count', 'calories' => '', 'protein_g' => ''],
                    ],
                ],
            ])
            ->call('saveNutritionDay')
            ->set('intakeProductName', 'Whey Protein')
            ->set('intakeAmount', '2')
            ->set('intakeUnit', 'scoop')
            ->call('logSupplementIntake')
            ->call('addEquipmentPreset', 'belt')
            ->set('reportWeight', 80.5)
            ->set('reportMood', 7)
            ->set('reportHighlights', 'Strong squat day')
            ->call('saveDailyReport')
            ->assertHasNoErrors();

        $enrollment->refresh();

        $this->assertSame(['sat'], $enrollment->field_values['gym_days']);
        $this->assertCount(1, $enrollment->field_values['equipment_items'] ?? []);

        $this->assertSame(1, MissionWorkoutSession::query()->where('enrollment_id', $enrollment->id)->count());
        $this->assertSame(1, MissionNutritionDay::query()->where('enrollment_id', $enrollment->id)->count());
        $this->assertGreaterThanOrEqual(1, MissionSupplementIntake::query()->where('enrollment_id', $enrollment->id)->count());
        $this->assertSame(1, MissionDailyReport::query()->where('enrollment_id', $enrollment->id)->count());

        $report = MissionDailyReport::query()->where('enrollment_id', $enrollment->id)->first();
        $this->assertTrue($report->trained_today);
        $this->assertTrue($report->nutrition_logged);
        $this->assertSame('Strong squat day', $report->highlights);
    }

    public function test_changing_log_date_loads_existing_workout_into_form(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $date = now()->subDay()->toDateString();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('logDate', $date)
            ->set('workoutFocus', 'back')
            ->set('workoutExercises', [
                [
                    'name' => 'Deadlift',
                    'notes' => '',
                    'sets' => [['reps' => '5', 'weight' => '120', 'notes' => '']],
                ],
            ])
            ->call('saveWorkoutSession');

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('logDate', now()->toDateString())
            ->assertSet('workoutFocus', '')
            ->set('logDate', $date)
            ->assertSet('workoutFocus', 'back')
            ->assertSet('workoutExercises.0.name', 'Deadlift');
    }
}
