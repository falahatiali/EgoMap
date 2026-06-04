<?php

namespace Tests\Feature\Missions;

use App\Livewire\Missions\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\MissionEngine\Enums\CaloriesStatus;
use Modules\MissionEngine\Enums\MealType;
use Modules\MissionEngine\Enums\MissionActivityEvent;
use Modules\MissionEngine\Models\MissionNutritionDay;
use Modules\MissionEngine\Services\MissionNutritionLogService;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\TestCase;

class MissionNutritionLoggingTest extends TestCase
{
    use InteractsWithMissionEnrollment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMissionEngine();
    }

    public function test_save_day_sums_calories_across_meals(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        $day = app(MissionNutritionLogService::class)->saveDay($enrollment, $user, [
            'log_date' => now()->toDateString(),
            'meals' => [
                [
                    'meal_type' => MealType::Breakfast->value,
                    'items' => [
                        ['name' => 'Eggs', 'calories' => 140],
                        ['name' => 'Avocado', 'calories' => 120],
                    ],
                ],
                [
                    'meal_type' => MealType::Lunch->value,
                    'items' => [
                        ['name' => 'Rice', 'calories' => 400],
                    ],
                ],
            ],
        ]);

        $this->assertSame(660, $day->total_calories);
        $this->assertSame(CaloriesStatus::Unknown, $day->calories_status);
    }

    public function test_save_day_upserts_same_date(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $service = app(MissionNutritionLogService::class);
        $date = now()->toDateString();

        $service->saveDay($enrollment, $user, [
            'log_date' => $date,
            'meals' => [
                ['meal_type' => MealType::Breakfast->value, 'items' => [['name' => 'Toast', 'calories' => 200]]],
            ],
        ]);

        $service->saveDay($enrollment, $user, [
            'log_date' => $date,
            'day_notes' => 'Light day',
            'meals' => [
                ['meal_type' => MealType::Dinner->value, 'items' => [['name' => 'Salad', 'calories' => 150]]],
            ],
        ]);

        $this->assertSame(1, MissionNutritionDay::query()->where('enrollment_id', $enrollment->id)->count());

        $day = MissionNutritionDay::query()->where('enrollment_id', $enrollment->id)->first();
        $this->assertSame('Light day', $day->day_notes);
        $this->assertSame(150, $day->total_calories);
    }

    public function test_save_day_creates_nutrition_activity_log(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        app(MissionNutritionLogService::class)->saveDay($enrollment, $user, [
            'log_date' => now()->toDateString(),
            'meals' => [
                ['meal_type' => MealType::Breakfast->value, 'items' => [['name' => 'Oats', 'calories' => 300]]],
            ],
        ]);

        $this->assertDatabaseHas('mission_activity_logs', [
            'enrollment_id' => $enrollment->id,
            'event_type' => MissionActivityEvent::NutritionLogged->value,
        ]);
    }

    public function test_livewire_saves_multi_meal_nutrition_day(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('activeTab', 'nutrition')
            ->set('nutritionMeals', [
                [
                    'meal_type' => MealType::Breakfast->value,
                    'meal_time' => '08:00',
                    'notes' => '',
                    'items' => [
                        ['name' => 'Eggs', 'quantity' => '2', 'unit' => 'count', 'calories' => '', 'protein_g' => ''],
                    ],
                ],
            ])
            ->call('saveNutritionDay')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mission_nutrition_days', [
            'enrollment_id' => $enrollment->id,
        ]);
    }

    public function test_livewire_rejects_nutrition_without_food_items(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('nutritionMeals', [
                [
                    'meal_type' => MealType::Lunch->value,
                    'meal_time' => '',
                    'notes' => '',
                    'items' => [
                        ['name' => '', 'quantity' => '', 'unit' => '', 'calories' => '', 'protein_g' => ''],
                    ],
                ],
            ])
            ->call('saveNutritionDay')
            ->assertHasErrors('nutritionMeals');
    }
}
