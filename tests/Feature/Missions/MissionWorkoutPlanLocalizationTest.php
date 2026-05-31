<?php

namespace Tests\Feature\Missions;

use App\Livewire\Missions\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\TestCase;

class MissionWorkoutPlanLocalizationTest extends TestCase
{
    use InteractsWithMissionEnrollment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMissionEngine();
    }

    public function test_workout_plan_displays_english_on_english_locale(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        $enrollment->update([
            'field_values' => array_merge($enrollment->field_values ?? [], [
                'workout_plan' => [
                    [
                        'day' => 'sat',
                        'focus' => ['en' => 'Chest', 'fa' => 'سینه'],
                        'notes' => ['en' => '', 'fa' => ''],
                    ],
                    [
                        'day' => 'thu',
                        'focus' => ['en' => 'Legs', 'fa' => 'پا'],
                        'notes' => [
                            'en' => 'Heavy leg day',
                            'fa' => 'سنگین پا بزن و بعدش تا شنبه استراحت',
                        ],
                    ],
                ],
            ]),
        ]);

        $this->app->setLocale('en');

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment->fresh()])
            ->assertSet('workoutPlan.0.focus', 'Chest')
            ->assertSet('workoutPlan.1.focus', 'Legs')
            ->assertSet('workoutPlan.1.notes', 'Heavy leg day');
    }

    public function test_workout_plan_displays_persian_on_persian_locale(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        $enrollment->update([
            'field_values' => array_merge($enrollment->field_values ?? [], [
                'workout_plan' => [
                    [
                        'day' => 'sat',
                        'focus' => ['en' => 'Chest', 'fa' => 'سینه'],
                        'notes' => ['en' => '', 'fa' => ''],
                    ],
                ],
            ]),
        ]);

        $this->app->setLocale('fa');

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment->fresh()])
            ->assertSet('workoutPlan.0.focus', 'سینه');
    }

    public function test_legacy_persian_only_plan_row_shows_english_via_lexicon(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        $enrollment->update([
            'field_values' => array_merge($enrollment->field_values ?? [], [
                'workout_plan' => [
                    ['day' => 'sat', 'focus' => 'سینه', 'notes' => ''],
                    ['day' => 'sun', 'focus' => 'سرشونه', 'notes' => ''],
                ],
            ]),
        ]);

        $this->app->setLocale('en');

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment->fresh()])
            ->assertSet('workoutPlan.0.focus', 'Chest')
            ->assertSet('workoutPlan.1.focus', 'Shoulders');
    }
}
