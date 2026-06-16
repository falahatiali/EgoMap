<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\CommunityEngine\Enums\PostStatus;
use Modules\CommunityEngine\Enums\ReactionType;
use Modules\CommunityEngine\Models\CommunityComment;
use Modules\CommunityEngine\Models\CommunityPost;
use Modules\CommunityEngine\Services\CommunityModerationService;
use Modules\CommunityEngine\Services\CommunityPostService;
use Modules\GamificationEngine\Database\Seeders\GamificationEngineDatabaseSeeder;
use Tests\TestCase;

class CommunityApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(GamificationEngineDatabaseSeeder::class);

        // Disable AI moderation by default so tests don't require external calls
        config(['community.auto_moderate' => false]);
    }

    // ── Feed ────────────────────────────────────────────────────────────────

    public function test_guest_can_read_community_feed(): void
    {
        CommunityPost::factory()->count(3)->create(['status' => PostStatus::Approved]);

        $this->getJson('/api/v1/community/posts')
            ->assertOk()
            ->assertJsonStructure([
                'locale',
                'labels' => ['title', 'subtitle', 'you_reacted', 'view_all_comments'],
                'sort_options',
                'reaction_groups' => ['positive', 'empathetic'],
                'data' => [
                    '*' => [
                        'id',
                        'content',
                        'display_name',
                        'comments_preview' => ['data', 'has_more', 'total'],
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'total', 'per_page'],
                'reaction_types',
                'preview_limit',
                'comments_per_page',
            ]);
    }

    public function test_feed_can_skip_comment_previews(): void
    {
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved]);
        CommunityComment::factory()->count(4)->create(['post_id' => $post->id]);

        $response = $this->getJson('/api/v1/community/posts?include_preview=0')->assertOk();

        $this->assertArrayNotHasKey('comments_preview', $response->json('data.0'));
    }

    public function test_feed_includes_three_comment_previews_by_default(): void
    {
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved]);
        CommunityComment::factory()->count(5)->create(['post_id' => $post->id]);

        $response = $this->getJson('/api/v1/community/posts')->assertOk();

        $this->assertCount(3, $response->json('data.0.comments_preview.data'));
        $this->assertTrue($response->json('data.0.comments_preview.has_more'));
    }

    public function test_bearer_token_on_public_feed_enables_viewer_context(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved]);

        $this->withToken($token)
            ->getJson('/api/v1/community/posts')
            ->assertOk()
            ->assertJsonPath('data.0.can_react', true)
            ->assertJsonPath('data.0.id', $post->id);
    }

    public function test_bearer_token_on_post_detail_enables_viewer_context(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved]);

        $this->withToken($token)
            ->getJson('/api/v1/community/posts/'.$post->id)
            ->assertOk()
            ->assertJsonPath('post.can_react', true)
            ->assertJsonPath('post.can_delete', false);
    }

    public function test_guest_can_view_single_post(): void
    {
        $post = CommunityPost::factory()->create([
            'status' => PostStatus::Approved,
            'content' => 'Mobile post detail content',
        ]);

        $this->getJson('/api/v1/community/posts/'.$post->id)
            ->assertOk()
            ->assertJsonPath('post.content', 'Mobile post detail content')
            ->assertJsonStructure([
                'labels',
                'reaction_groups',
                'post' => ['id', 'display_name', 'viewer_reaction', 'can_delete', 'comments_count_label'],
            ]);

        $this->assertSame(1, $post->fresh()->views_count);
    }

    public function test_pending_post_detail_returns_not_found(): void
    {
        $post = CommunityPost::factory()->create(['status' => PostStatus::Pending]);

        $this->getJson('/api/v1/community/posts/'.$post->id)->assertNotFound();
    }

    public function test_feed_only_shows_approved_posts(): void
    {
        CommunityPost::factory()->create(['status' => PostStatus::Approved]);
        CommunityPost::factory()->create(['status' => PostStatus::Pending]);
        CommunityPost::factory()->create(['status' => PostStatus::Rejected]);

        $response = $this->getJson('/api/v1/community/posts')->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_feed_sorts_by_most_liked(): void
    {
        CommunityPost::factory()->create(['status' => PostStatus::Approved, 'likes_count' => 1]);
        CommunityPost::factory()->create(['status' => PostStatus::Approved, 'likes_count' => 50]);

        $response = $this->getJson('/api/v1/community/posts?sort=liked')->assertOk();

        $this->assertEquals(50, $response->json('data.0.likes_count'));
    }

    // ── Create Post ─────────────────────────────────────────────────────────

    public function test_guest_cannot_create_post(): void
    {
        $this->postJson('/api/v1/community/posts', ['content' => 'Hello world'])
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_post(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/community/posts', [
            'content' => 'This is my first community post.',
        ])
            ->assertCreated()
            ->assertJsonPath('post.status', PostStatus::Approved->value)
            ->assertJsonPath('post.display_name', $user->name);
    }

    public function test_post_validates_content_length(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/community/posts', ['content' => 'Hi'])
            ->assertUnprocessable();

        $this->postJson('/api/v1/community/posts', ['content' => str_repeat('a', 1001)])
            ->assertUnprocessable();
    }

    public function test_anonymous_post_hides_author_name(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/community/posts', [
            'content' => 'Posting anonymously today.',
            'is_anonymous' => true,
        ])->assertCreated();

        $postId = $response->json('post.id');
        $post = CommunityPost::find($postId);

        $this->assertTrue($post->is_anonymous);
        $this->assertEquals('Anonymous', $post->displayName());
    }

    public function test_ai_moderation_rejects_unsafe_content(): void
    {
        config(['community.auto_moderate' => true]);

        // Mock the moderation service to return an unsafe result
        $this->app->instance(
            CommunityModerationService::class,
            new class extends CommunityModerationService
            {
                public function __construct() {}

                public function check(string $content): array
                {
                    return [
                        'is_safe' => false,
                        'flags' => ['toxic'],
                        'reason' => 'Contains hate speech.',
                        'suggested_message' => 'Your post contains sensitive content. Please review it.',
                    ];
                }
            },
        );
        $this->app->forgetInstance(CommunityPostService::class);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/community/posts', [
            'content' => 'Some flagged content here.',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('rejected', true);
    }

    // ── Delete Post ──────────────────────────────────────────────────────────

    public function test_owner_can_delete_own_post(): void
    {
        $user = User::factory()->create();
        $post = CommunityPost::factory()->create(['user_id' => $user->id, 'status' => PostStatus::Approved]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/community/posts/'.$post->id)->assertOk();

        $this->assertSoftDeleted('community_posts', ['id' => $post->id]);
    }

    public function test_other_user_cannot_delete_another_users_post(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post = CommunityPost::factory()->create(['user_id' => $owner->id, 'status' => PostStatus::Approved]);

        Sanctum::actingAs($other);

        $this->deleteJson('/api/v1/community/posts/'.$post->id)->assertForbidden();
    }

    // ── Reactions ────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_react_to_post(): void
    {
        $user = User::factory()->create();
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/community/posts/'.$post->id.'/react', [
            'reaction_type' => ReactionType::Fire->value,
        ])
            ->assertOk()
            ->assertJsonPath('reaction.type', ReactionType::Fire->value)
            ->assertJsonPath('post.viewer_reaction.type', ReactionType::Fire->value);

        $this->assertEquals(1, $post->fresh()->likes_count);
    }

    public function test_removing_same_reaction_decrements_count(): void
    {
        $user = User::factory()->create();
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved, 'likes_count' => 1]);

        Sanctum::actingAs($user);

        // React once
        $this->postJson('/api/v1/community/posts/'.$post->id.'/react', [
            'reaction_type' => ReactionType::Like->value,
        ])->assertOk();

        // React again with the same type → remove
        $this->postJson('/api/v1/community/posts/'.$post->id.'/react', [
            'reaction_type' => ReactionType::Like->value,
        ])
            ->assertOk()
            ->assertJsonPath('reaction', null);
    }

    // ── Comments ─────────────────────────────────────────────────────────────

    public function test_guest_can_read_comments(): void
    {
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved]);
        CommunityComment::factory()->count(2)->create(['post_id' => $post->id]);

        $this->getJson('/api/v1/community/posts/'.$post->id.'/comments')
            ->assertOk()
            ->assertJsonStructure([
                'labels',
                'data' => [
                    '*' => ['id', 'content', 'display_name', 'replies', 'viewer_reaction'],
                ],
                'meta' => ['has_more', 'total', 'limit', 'offset', 'next_offset'],
            ]);
    }

    public function test_comments_support_limit_and_offset_pagination(): void
    {
        $user = User::factory()->create();
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved]);

        for ($i = 0; $i < 5; $i++) {
            CommunityComment::factory()->create([
                'post_id' => $post->id,
                'user_id' => $user->id,
            ]);
        }

        $firstPage = $this->getJson('/api/v1/community/posts/'.$post->id.'/comments?limit=3&offset=0')
            ->assertOk();

        $this->assertCount(3, $firstPage->json('data'));
        $this->assertTrue($firstPage->json('meta.has_more'));
        $this->assertSame(3, $firstPage->json('meta.next_offset'));

        $secondPage = $this->getJson('/api/v1/community/posts/'.$post->id.'/comments?limit=3&offset=3')
            ->assertOk();

        $this->assertCount(2, $secondPage->json('data'));
        $this->assertFalse($secondPage->json('meta.has_more'));
        $this->assertNull($secondPage->json('meta.next_offset'));
    }

    public function test_authenticated_user_can_post_comment(): void
    {
        $user = User::factory()->create();
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/community/posts/'.$post->id.'/comments', [
            'content' => 'This is a meaningful comment.',
        ])
            ->assertCreated()
            ->assertJsonPath('comment.content', 'This is a meaningful comment.')
            ->assertJsonPath('comment.display_name', $user->name);

        $this->assertEquals(1, $post->fresh()->comments_count);
    }

    public function test_comment_can_reply_to_parent(): void
    {
        $user = User::factory()->create();
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved]);
        $parent = CommunityComment::factory()->create(['post_id' => $post->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/community/posts/'.$post->id.'/comments', [
            'content' => 'Replying to you.',
            'parent_id' => $parent->id,
        ])
            ->assertCreated()
            ->assertJsonPath('comment.parent_id', $parent->id);
    }

    public function test_owner_can_delete_comment(): void
    {
        $user = User::factory()->create();
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved, 'comments_count' => 1]);
        $comment = CommunityComment::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/community/comments/'.$comment->id)->assertOk();

        $this->assertSoftDeleted('community_comments', ['id' => $comment->id]);
    }
}
