<?php

namespace Tests\Feature\Community;

use App\Livewire\Community\CommentSection;
use App\Livewire\Community\Feed;
use App\Livewire\Community\ShowPost;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CommunityEngine\Enums\PostStatus;
use Modules\CommunityEngine\Models\CommunityComment;
use Modules\CommunityEngine\Models\CommunityPost;
use Modules\CommunityEngine\Services\CommunityCommentService;
use Modules\GamificationEngine\Database\Seeders\GamificationEngineDatabaseSeeder;
use Livewire\Livewire;
use Tests\TestCase;

class CommunityPostPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(GamificationEngineDatabaseSeeder::class);

        config(['community.auto_moderate' => false]);
    }

    public function test_guest_can_view_approved_post_page(): void
    {
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved]);

        $this->get(route('community.show', ['locale' => 'en', 'post' => $post->id]))
            ->assertOk()
            ->assertSee($post->content);
    }

    public function test_show_post_livewire_component_loads(): void
    {
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved]);

        Livewire::test(ShowPost::class, ['post' => $post])
            ->assertSee($post->content);
    }

    public function test_pending_post_page_returns_not_found(): void
    {
        $post = CommunityPost::factory()->create(['status' => PostStatus::Pending]);

        $this->get(route('community.show', ['locale' => 'en', 'post' => $post->id]))
            ->assertNotFound();
    }

    public function test_comment_service_limits_feed_preview_to_three_top_level_comments(): void
    {
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved]);
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            CommunityComment::factory()->create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'content' => "Comment {$i}",
            ]);
        }

        $service = app(CommunityCommentService::class);

        $preview = $service->forPost($post, limit: CommunityCommentService::FEED_PREVIEW_LIMIT);
        $full = $service->forPost($post, limit: CommunityCommentService::POST_PAGE_LIMIT);

        $this->assertCount(3, $preview['comments']);
        $this->assertTrue($preview['has_more']);
        $this->assertSame(5, $preview['total']);

        $this->assertCount(5, $full['comments']);
        $this->assertFalse($full['has_more']);
    }

    public function test_comment_service_load_more_increases_visible_batch(): void
    {
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved]);
        $user = User::factory()->create();

        for ($i = 0; $i < 75; $i++) {
            CommunityComment::factory()->create([
                'post_id' => $post->id,
                'user_id' => $user->id,
            ]);
        }

        $service = app(CommunityCommentService::class);

        $firstBatch = $service->forPost($post, limit: 50);
        $secondBatch = $service->forPost($post, limit: 100);

        $this->assertCount(50, $firstBatch['comments']);
        $this->assertTrue($firstBatch['has_more']);

        $this->assertCount(75, $secondBatch['comments']);
        $this->assertFalse($secondBatch['has_more']);
    }

    public function test_post_page_increments_view_count(): void
    {
        $post = CommunityPost::factory()->create([
            'status' => PostStatus::Approved,
            'views_count' => 0,
        ]);

        $this->get(route('community.show', ['locale' => 'en', 'post' => $post->id]))
            ->assertOk();

        $this->assertSame(1, $post->fresh()->views_count);
    }

    public function test_comment_section_load_more_increases_visible_limit(): void
    {
        $post = CommunityPost::factory()->create(['status' => PostStatus::Approved]);

        Livewire::test(CommentSection::class, [
            'postId' => $post->id,
            'preview' => false,
            'perPage' => 50,
        ])
            ->assertSet('visibleLimit', 50)
            ->call('loadMore')
            ->assertSet('visibleLimit', 100);
    }

    public function test_owner_can_delete_post_from_feed(): void
    {
        $user = User::factory()->create();
        $post = CommunityPost::factory()->create([
            'user_id' => $user->id,
            'status' => PostStatus::Approved,
        ]);

        Livewire::actingAs($user)
            ->test(Feed::class)
            ->call('deletePost', $post->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('community_posts', ['id' => $post->id]);
    }

    public function test_owner_can_delete_post_from_show_page(): void
    {
        $user = User::factory()->create();
        $post = CommunityPost::factory()->create([
            'user_id' => $user->id,
            'status' => PostStatus::Approved,
        ]);

        Livewire::actingAs($user)
            ->test(ShowPost::class, ['post' => $post])
            ->call('deletePost')
            ->assertRedirect(route('community.feed', ['locale' => 'en']));

        $this->assertSoftDeleted('community_posts', ['id' => $post->id]);
    }
}
