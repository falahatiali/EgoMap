<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizSession>
 */
class QuizSessionFactory extends Factory
{
    protected $model = QuizSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'user_id' => User::factory(),
            'guest_token' => null,
            'locale' => 'en',
            'status' => SessionStatus::InProgress,
            'current_sort_order' => 1,
            'started_at' => now(),
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
