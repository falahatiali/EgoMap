<?php

namespace Database\Factories;

use App\Enums\QuizType;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titleEn = fake()->sentence(3);

        return [
            'slug' => Str::slug($titleEn).'-'.fake()->unique()->numerify('###'),
            'type' => QuizType::Likert,
            'name' => [
                'en' => $titleEn,
                'fa' => 'تست '.$titleEn,
            ],
            'description' => [
                'en' => fake()->paragraph(),
                'fa' => fake()->paragraph(),
            ],
            'is_active' => true,
            'settings' => [
                'likert_min' => 1,
                'likert_max' => 5,
            ],
            'scoring_config' => [
                'engine' => 'weighted_sum',
            ],
            'estimated_minutes' => 5,
            'version' => 1,
        ];
    }
}
