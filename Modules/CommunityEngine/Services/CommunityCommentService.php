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

    public const FEED_PREVIEW_LIMIT = 3;

    public const POST_PAGE_LIMIT = 50;

    public function __construct(
        private readonly CommunityModerationService $moderation,
        private readonly GamificationEngine $gamification,
    ) {}

    /**
     * Load threaded comments for a post (top-level paginated, up to 3 reply levels).
     *
     * @return array{comments: Collection<int, CommunityComment>, has_more: bool, total: int}
     */
    public function forPost(
        CommunityPost $post,
        ?int $viewerId = null,
        int $limit = 20,
        int $offset = 0,
    ): array {
        $baseQuery = CommunityComment::query()
            ->where('post_id', $post->id)
            ->whereNull('parent_id')
            ->whereNull('deleted_at');

        $total = (clone $baseQuery)->count();

        $comments = (clone $baseQuery)
            ->with([
                'author:id,name',
                'replies' => fn ($q) => $q->with([
                    'author:id,name',
                    'replies' => fn ($q2) => $q2->with('author:id,name')->limit(5),
                ])->limit(10),
            ])
            ->latest()
            ->offset($offset)
            ->limit($limit)
            ->get();

        if ($viewerId) {
            $this->attachViewerReactions($comments, $viewerId);
        }

        return [
            'comments' => $comments,
            'has_more' => ($offset + $limit) < $total,
            'total' => $total,
        ];
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

    /**
     * @param  Collection<int, CommunityComment>  $comments
     */
    private function attachViewerReactions(Collection $comments, int $viewerId): void
    {
        $commentIds = $this->collectCommentIds($comments);

        if ($commentIds === []) {
            return;
        }

        $reactions = CommunityCommentReaction::query()
            ->where('user_id', $viewerId)
            ->whereIn('comment_id', $commentIds)
            ->get()
            ->keyBy('comment_id');

        $this->applyViewerReactionsToTree($comments, $reactions);
    }

    /**
     * @param  Collection<int, CommunityComment>  $comments
     * @return list<int>
     */
    private function collectCommentIds(Collection $comments): array
    {
        $ids = [];

        foreach ($comments as $comment) {
            $ids[] = $comment->id;

            foreach ($comment->replies as $reply) {
                $ids[] = $reply->id;

                foreach ($reply->replies as $deepReply) {
                    $ids[] = $deepReply->id;
                }
            }
        }

        return $ids;
    }

    /**
     * @param  Collection<int, CommunityComment>  $comments
     * @param  Collection<int, CommunityCommentReaction>  $reactions
     */
    private function applyViewerReactionsToTree(Collection $comments, Collection $reactions): void
    {
        foreach ($comments as $comment) {
            $comment->setAttribute(
                'viewer_reaction',
                $reactions->get($comment->id)?->reaction_type?->value,
            );

            foreach ($comment->replies as $reply) {
                $reply->setAttribute(
                    'viewer_reaction',
                    $reactions->get($reply->id)?->reaction_type?->value,
                );

                foreach ($reply->replies as $deepReply) {
                    $deepReply->setAttribute(
                        'viewer_reaction',
                        $reactions->get($deepReply->id)?->reaction_type?->value,
                    );
                }
            }
        }
    }
}
