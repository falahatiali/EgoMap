<?php

namespace Database\Factories;

use App\Models\QuestionOption;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionOption>
 */
class QuestionOptionFactory extends Factory
{
    protected $model = QuestionOption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $labelEn = fake()->words(3, true);

        return [
            'uuid' => (string) Str::uuid(),
            'question_id' => null,
            'sort_order' => fake()->numberBetween(1, 20),
            'label' => [
                'en' => $labelEn,
                'fa' => 'گزینه: '.$labelEn,
            ],
            'value' => strtoupper(fake()->unique()->lexify('OPT_????')),
            'scores' => null,
            'meta' => null,
        ];
    }
}
