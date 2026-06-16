<?php

namespace Modules\CommunityEngine\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\CommunityEngine\Enums\PostStatus;
use Modules\CommunityEngine\Enums\ReactionType;
use Modules\CommunityEngine\Models\CommunityPost;
use Modules\CommunityEngine\Models\CommunityReaction;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Services\GamificationEngine;

class CommunityPostService
{
    private const DAILY_POST_REWARD_CAP = 3;

    public function __construct(
        private readonly CommunityModerationService $moderation,
        private readonly GamificationEngine $gamification,
    ) {}

    /**
     * Paginated feed — latest approved posts with reaction counts and viewer's reaction.
     *
     * @return LengthAwarePaginator<CommunityPost>
     */
    public function feed(string $sort = 'latest', ?int $viewerId = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = CommunityPost::query()
            ->with('author:id,name')
            ->approved()
            ->whereNull('deleted_at');

        $query = match ($sort) {
            'liked' => $query->orderByDesc('likes_count'),
            'discussed' => $query->orderByDesc('comments_count'),
            'mine' => $viewerId
                ? $query->where('user_id', $viewerId)->latest()
                : $query->latest(),
            default => $query->latest(),
        };

        $posts = $query->paginate($perPage);

        if ($viewerId) {
            $this->attachViewerReactions($posts->items(), $viewerId);
        }

        return $posts;
    }

    /**
     * Create and optionally moderate a post.
     *
     * @return array{post: ?CommunityPost, rejected: bool, message: string}
     */
    public function create(User $user, string $content, bool $isAnonymous = false): array
    {
        $content = trim($content);

        if (config('community.auto_moderate', true)) {
            $modResult = $this->moderation->check($content);

            if (! $modResult['is_safe']) {
                return [
                    'post' => null,
                    'rejected' => true,
                    'message' => $modResult['suggested_message'] ?: 'Your post contains sensitive content. Please review it.',
                ];
            }
        }

        $status = config('community.require_approval', false)
            ? PostStatus::Pending
            : PostStatus::Approved;

        $post = CommunityPost::create([
            'user_id' => $user->id,
            'content' => $content,
            'is_anonymous' => $isAnonymous,
            'status' => $status,
        ]);

        if ($status === PostStatus::Approved) {
            $this->rewardPostPublished($user, $post);
        }

        return [
            'post' => $post,
            'rejected' => false,
            'message' => $status === PostStatus::Pending
                ? 'Your post is under review and will appear shortly.'
                : 'Your post is live!',
        ];
    }

    public function delete(CommunityPost $post, User $actor): bool
    {
        if (! $post->isOwnedBy($actor->id) && ! $actor->hasRole('admin')) {
            return false;
        }

        $post->delete();

        return true;
    }

    /**
     * Toggle a reaction on a post. Returns the new reaction type or null (removed).
     */
    public function toggleReaction(CommunityPost $post, User $user, ReactionType $type): ?ReactionType
    {
        $existing = CommunityReaction::query()
            ->where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($existing) {
            if ($existing->reaction_type === $type) {
                // Same reaction: remove it.
                $existing->delete();
                $post->decrement('likes_count');

                return null;
            }

            // Different reaction: swap it (count stays the same).
            $existing->update(['reaction_type' => $type]);

            return $type;
        }

        // New reaction.
        CommunityReaction::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'reaction_type' => $type,
            'created_at' => now(),
        ]);

        $post->increment('likes_count');
        $this->rewardReactionGiven($user);

        return $type;
    }

    /**
     * Increment view counter (call once per unique session view).
     */
    public function incrementView(CommunityPost $post): void
    {
        $post->increment('views_count');
    }

    /**
     * @param  array<CommunityPost>  $posts
     */
    private function attachViewerReactions(array $posts, int $viewerId): void
    {
        if (empty($posts)) {
            return;
        }

        $postIds = array_column($posts, 'id');

        /** @var Collection<int, CommunityReaction> $reactions */
        $reactions = CommunityReaction::query()
            ->where('user_id', $viewerId)
            ->whereIn('post_id', $postIds)
            ->get()
            ->keyBy('post_id');

        foreach ($posts as $post) {
            $post->setAttribute('viewer_reaction', $reactions->get($post->id)?->reaction_type?->value);
        }
    }

    private function rewardPostPublished(User $user, CommunityPost $post): void
    {
        $todayCount = CommunityPost::query()
            ->where('user_id', $user->id)
            ->where('status', PostStatus::Approved)
            ->whereDate('created_at', today())
            ->count();

        if ($todayCount <= self::DAILY_POST_REWARD_CAP) {
            $this->gamification->dispatch(
                GamificationEvent::CommunityPostPublished->value,
                ['user_id' => $user->id, 'metadata' => ['post_id' => $post->id]],
            );
        }

        // First post badge
        $totalPosts = CommunityPost::query()
            ->where('user_id', $user->id)
            ->where('status', PostStatus::Approved)
            ->count();

        if ($totalPosts === 1) {
            $this->gamification->dispatch(
                GamificationEvent::CommunityFirstPost->value,
                ['user_id' => $user->id, 'metadata' => ['post_id' => $post->id]],
            );
        }

        if ($totalPosts === 10) {
            $this->gamification->dispatch(
                GamificationEvent::CommunityTenPosts->value,
                ['user_id' => $user->id, 'metadata' => ['post_id' => $post->id]],
            );
        }
    }

    private function rewardReactionGiven(User $user): void
    {
        $todayCount = CommunityReaction::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->count();

        if ($todayCount <= 10) {
            $this->gamification->dispatch(
                GamificationEvent::CommunityReactionGiven->value,
                ['user_id' => $user->id],
            );
        }
    }
}
