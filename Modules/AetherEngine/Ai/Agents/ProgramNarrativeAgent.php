<?php

namespace Modules\AetherEngine\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Modules\AetherEngine\Support\AetherProgramAgentInstructions;

class ProgramNarrativeAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return AetherProgramAgentInstructions::get();
    }

    public function schema(JsonSchema $schema): array
    {
        $exercise = $schema->object([
            'name' => $schema->string()->required(),
            'sets' => $schema->integer()->required(),
            'reps' => $schema->string()->required(),
            'rest_seconds' => $schema->integer()->required(),
            'notes' => $schema->string()->required(),
        ]);

        $workout = $schema->object([
            'day' => $schema->integer()->required(),
            'name' => $schema->string()->required(),
            'warmup' => $schema->array()->items($schema->string())->required(),
            'exercises' => $schema->array()->items($exercise)->required(),
            'cooldown' => $schema->array()->items($schema->string())->required(),
            'motivation_text' => $schema->string()->required(),
        ]);

        $mealPlanDay = $schema->object([
            'day' => $schema->integer()->required(),
            'breakfast' => $schema->string()->required(),
            'lunch' => $schema->string()->required(),
            'dinner' => $schema->string()->required(),
            'snack' => $schema->string()->required(),
        ]);

        $nutritionWeek = $schema->object([
            'daily_calories' => $schema->integer()->required(),
            'macros' => $schema->object([
                'protein_g' => $schema->integer()->required(),
                'fat_g' => $schema->integer()->required(),
                'carbs_g' => $schema->integer()->required(),
            ])->required(),
            'meal_plan' => $schema->array()->items($mealPlanDay)->required(),
            'shopping_list' => $schema->array()->items($schema->string())->required(),
            'nutrition_tip' => $schema->string()->required(),
        ]);

        $week = $schema->object([
            'week_number' => $schema->integer()->required(),
            'focus' => $schema->string()->required(),
            'workouts' => $schema->array()->items($workout)->required(),
            'nutrition_week' => $nutritionWeek,
            'mindset_focus' => $schema->string()->required(),
            'habit_stack' => $schema->string()->required(),
        ]);

        return [
            'program_id' => $schema->string()->required(),
            'title' => $schema->string()->required(),
            'weeks' => $schema->array()->items($week)->required(),
            'recovery_strategy' => $schema->string()->required(),
            'supplement_advice' => $schema->string()->required(),
            'disclaimer' => $schema->string()->required(),
        ];
    }
}
