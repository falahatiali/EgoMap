<?php

namespace Modules\CommunityEngine\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Modules\CommunityEngine\Enums\ReactionType;
use Modules\CommunityEngine\Models\CommunityComment;
use Modules\CommunityEngine\Models\CommunityCommentReaction;
use Modules\CommunityEngine\Models\CommunityPost;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Services\GamificationEngine;

class CommunityCommentService
{
    private const DAILY_COMMENT_REWARD_CAP = 5;

    public function __construct(
        private readonly CommunityModerationService $moderation,
        private readonly GamificationEngine $gamification,
    ) {}

    /**
     * Load up to 3 levels of comments for a post.
     * Level 1 (top-level) → level 2 (replies) → level 3 (replies-to-replies).
     *
     * @return Collection<int, CommunityComment>
     */
    public function forPost(CommunityPost $post): Collection
    {
        return CommunityComment::query()
            ->with([
                'author:id,name',
                'replies' => fn ($q) => $q->with([
                    'author:id,name',
                    'replies' => fn ($q2) => $q2->with('author:id,name')->limit(5),
                ])->limit(10),
            ])
            ->where('post_id', $post->id)
            ->whereNull('parent_id')
            ->whereNull('deleted_at')
            ->latest()
            ->limit(20)
            ->get();
    }

    /**
     * @return array{comment: ?CommunityComment, rejected: bool, message: string}
     */
    public function create(
        User $user,
        CommunityPost $post,
        string $content,
        bool $isAnonymous = false,
        ?int $parentId = null,
    ): array {
        $content = trim($content);

        if (config('community.auto_moderate', true)) {
            $modResult = $this->moderation->check($content);

            if (! $modResult['is_safe']) {
                return [
                    'comment' => null,
                    'rejected' => true,
                    'message' => $modResult['suggested_message'] ?: 'Your comment contains sensitive content.',
                ];
            }
        }

        $comment = CommunityComment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'parent_id' => $parentId,
            'content' => $content,
            'is_anonymous' => $isAnonymous,
        ]);

        $post->increment('comments_count');

        $this->rewardCommentPosted($user, $comment);

        return [
            'comment' => $comment->load('author:id,name'),
            'rejected' => false,
            'message' => 'Comment posted.',
        ];
    }

    public function delete(CommunityComment $comment, User $actor): bool
    {
        if (! $comment->isOwnedBy($actor->id) && ! $actor->hasRole('admin')) {
            return false;
        }

        $comment->post?->decrement('comments_count');
        $comment->delete();

        return true;
    }

    /**
     * Toggle a like reaction on a comment. Returns the new reaction or null (removed).
     */
    public function toggleReaction(CommunityComment $comment, User $user, ReactionType $type): ?ReactionType
    {
        $existing = CommunityCommentReaction::query()
            ->where('user_id', $user->id)
            ->where('comment_id', $comment->id)
            ->first();

        if ($existing) {
            if ($existing->reaction_type === $type) {
                $existing->delete();
                $comment->decrement('likes_count');

                return null;
            }

            $existing->update(['reaction_type' => $type]);

            return $type;
        }

        CommunityCommentReaction::create([
            'user_id' => $user->id,
            'comment_id' => $comment->id,
            'reaction_type' => $type,
            'created_at' => now(),
        ]);

        $comment->increment('likes_count');

        return $type;
    }

    private function rewardCommentPosted(User $user, CommunityComment $comment): void
    {
        $todayCount = CommunityComment::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->count();

        if ($todayCount <= self::DAILY_COMMENT_REWARD_CAP) {
            $this->gamification->dispatch(
                GamificationEvent::CommunityCommentPosted->value,
                ['user_id' => $user->id, 'metadata' => ['comment_id' => $comment->id]],
            );
        }

        $totalComments = CommunityComment::query()
            ->where('user_id', $user->id)
            ->count();

        if ($totalComments === 50) {
            $this->gamification->dispatch(
                GamificationEvent::CommunityEmpathyChampion->value,
                ['user_id' => $user->id],
            );
        }
    }
}
