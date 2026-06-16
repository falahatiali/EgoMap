<?php

namespace Modules\CommunityEngine\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CommunityEngine\Models\CommunityComment;
use Modules\CommunityEngine\Models\CommunityPost;

/**
 * @extends Factory<CommunityComment>
 */
class CommunityCommentFactory extends Factory
{
    protected $model = CommunityComment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => CommunityPost::factory(),
            'parent_id' => null,
            'content' => $this->faker->sentence(),
            'is_anonymous' => false,
            'likes_count' => 0,
        ];
    }
}
