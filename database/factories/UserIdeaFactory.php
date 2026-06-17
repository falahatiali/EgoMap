<?php

namespace Database\Factories;

use App\Enums\IdeaStatus;
use App\Models\User;
use App\Models\UserIdea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserIdea>
 */
class UserIdeaFactory extends Factory
{
    protected $model = UserIdea::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'seed_text' => fake()->sentence(6),
            'source' => 'manual',
            'status' => IdeaStatus::Raw,
            'progress' => 0,
        ];
    }
}
