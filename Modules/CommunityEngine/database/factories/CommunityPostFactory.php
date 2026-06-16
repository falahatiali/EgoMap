<?php

namespace Modules\CommunityEngine\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CommunityEngine\Enums\PostStatus;
use Modules\CommunityEngine\Models\CommunityPost;

/**
 * @extends Factory<CommunityPost>
 */
class CommunityPostFactory extends Factory
{
    protected $model = CommunityPost::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'is_anonymous' => $this->faker->boolean(20),
            'status' => PostStatus::Approved,
            'likes_count' => 0,
            'comments_count' => 0,
            'views_count' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => PostStatus::Pending]);
    }

    public function anonymous(): static
    {
        return $this->state(['is_anonymous' => true]);
    }
}
