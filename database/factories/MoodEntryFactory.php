<?php

namespace Database\Factories;

use App\Enums\MoodEmotion;
use App\Models\MoodEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoodEntry>
 */
class MoodEntryFactory extends Factory
{
    protected $model = MoodEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'emotion' => fake()->randomElement(MoodEmotion::cases()),
            'intensity' => fake()->numberBetween(3, 9),
            'ai_response' => [
                'empathy' => 'It makes sense you feel this way today.',
                'challenge' => 'Spend five minutes on one small creative act.',
                'reframe' => 'What would progress look like if it were gentle?',
                'idea_seed' => 'Start a five-minute daily creative ritual.',
            ],
        ];
    }
}
