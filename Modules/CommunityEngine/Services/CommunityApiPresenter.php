<?php

namespace Modules\CommunityEngine\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Modules\CommunityEngine\Enums\ReactionType;
use Modules\CommunityEngine\Models\CommunityComment;
use Modules\CommunityEngine\Models\CommunityPost;

final class CommunityApiPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function labels(string $locale): array
    {
        return [
            'title' => __('community.title', locale: $locale),
            'subtitle' => __('community.subtitle', locale: $locale),
            'new_post' => __('community.new_post', locale: $locale),
            'share_with_community' => __('community.share_with_community', locale: $locale),
            'whats_on_your_mind' => __('community.whats_on_your_mind', locale: $locale),
            'post_anonymously' => __('community.post_anonymously', locale: $locale),
            'you_reacted' => __('community.you_reacted', locale: $locale),
            'reactions_positive' => __('community.reactions_positive', locale: $locale),
            'reactions_empathetic' => __('community.reactions_empathetic', locale: $locale),
            'reactions' => __('community.reactions', locale: $locale),
            'comment' => __('community.comment', locale: $locale),
            'reply' => __('community.reply', locale: $locale),
            'cancel' => __('community.cancel', locale: $locale),
            'delete' => __('community.delete', locale: $locale),
            'confirm_delete_post' => __('community.confirm_delete_post', locale: $locale),
            'post_deleted' => __('community.post_deleted', locale: $locale),
            'write_a_comment' => __('community.write_a_comment', locale: $locale),
            'write_a_reply' => __('community.write_a_reply', locale: $locale),
            'post_comment' => __('community.post_comment', locale: $locale),
            'anonymous' => __('community.anonymous', locale: $locale),
            'no_comments_yet' => __('community.no_comments_yet', locale: $locale),
            'view_all_comments' => __('community.view_all_comments', locale: $locale),
            'load_more_comments' => __('community.load_more_comments', locale: $locale),
            'loading_comments' => __('community.loading_comments', locale: $locale),
            'back_to_feed' => __('community.back_to_feed', locale: $locale),
            'empty_feed' => __('community.empty_feed', locale: $locale),
            'be_first' => __('community.be_first', locale: $locale),
            'sort_latest' => __('community.sort_latest', locale: $locale),
            'sort_liked' => __('community.sort_liked', locale: $locale),
            'sort_discussed' => __('community.sort_discussed', locale: $locale),
            'sort_mine' => __('community.sort_mine', locale: $locale),
        ];
    }

    /**
     * @return array<string, list<array{type: string, emoji: string, label: string, tone: string}>>
     */
    public function reactionGroups(): array
    {
        return ReactionType::forUiGrouped();
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function sortOptions(string $locale): array
    {
        return [
            ['key' => 'latest', 'label' => __('community.sort_latest', locale: $locale)],
            ['key' => 'liked', 'label' => __('community.sort_liked', locale: $locale)],
            ['key' => 'discussed', 'label' => __('community.sort_discussed', locale: $locale)],
            ['key' => 'mine', 'label' => __('community.sort_mine', locale: $locale)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentPost(
        CommunityPost $post,
        string $locale,
        ?int $viewerId,
        ?array $commentsPreview = null,
    ): array {
        $payload = [
            'id' => $post->id,
            'content' => $post->content,
            'display_name' => $post->displayName(),
            'is_anonymous' => $post->is_anonymous,
            'status' => $post->status->value,
            'likes_count' => $post->likes_count,
            'comments_count' => $post->comments_count,
            'comments_count_label' => trans_choice(
                'community.comment_count',
                $post->comments_count,
                ['count' => $post->comments_count],
                $locale,
            ),
            'views_count' => $post->views_count,
            'created_at' => $post->created_at?->toIso8601String(),
            'created_at_human' => $post->created_at?->diffForHumans(),
            'viewer_reaction' => $this->presentViewerReaction($post->viewer_reaction ?? null),
            'is_owned' => $viewerId !== null && $post->isOwnedBy($viewerId),
            'can_delete' => $viewerId !== null && $this->canDeletePost($post, $viewerId),
            'can_react' => $viewerId !== null,
        ];

        if ($commentsPreview !== null) {
            $payload['comments_preview'] = $commentsPreview;
        }

        return $payload;
    }

    /**
     * @param  array{comments: Collection<int, CommunityComment>, has_more: bool, total: int}  $result
     * @return array{data: list<array<string, mixed>>, has_more: bool, total: int}
     */
    public function presentCommentsResult(array $result, ?int $viewerId): array
    {
        return [
            'data' => $result['comments']
                ->map(fn (CommunityComment $comment): array => $this->presentComment($comment, $viewerId))
                ->values()
                ->all(),
            'has_more' => $result['has_more'],
            'total' => $result['total'],
        ];
    }

    /**
     * @param  SupportCollection<int, CommunityPost>|array<CommunityPost>  $posts
     * @return array<int, array{data: list<array<string, mixed>>, has_more: bool, total: int}>
     */
    public function presentCommentsPreviewsForPosts(SupportCollection|array $posts, ?int $viewerId): array
    {
        $comments = app(CommunityCommentService::class);
        $previews = [];

        foreach ($posts as $post) {
            $result = $comments->forPost(
                $post,
                $viewerId,
                limit: CommunityCommentService::FEED_PREVIEW_LIMIT,
            );

            $previews[$post->id] = $this->presentCommentsResult($result, $viewerId);
        }

        return $previews;
    }

    /**
     * @return array<string, mixed>
     */
    public function presentComment(CommunityComment $comment, ?int $viewerId): array
    {
        return [
            'id' => $comment->id,
            'post_id' => $comment->post_id,
            'parent_id' => $comment->parent_id,
            'content' => $comment->content,
            'display_name' => $comment->displayName(),
            'is_anonymous' => $comment->is_anonymous,
            'likes_count' => $comment->likes_count,
            'created_at' => $comment->created_at?->toIso8601String(),
            'created_at_human' => $comment->created_at?->diffForHumans(),
            'is_recent' => $comment->isRecent(),
            'viewer_reaction' => $this->presentViewerReaction($comment->viewer_reaction ?? null),
            'is_owned' => $viewerId !== null && $comment->isOwnedBy($viewerId),
            'can_delete' => $viewerId !== null && $comment->isOwnedBy($viewerId),
            'can_reply' => $viewerId !== null,
            'can_react' => $viewerId !== null,
            'replies' => $comment->relationLoaded('replies')
                ? $comment->replies
                    ->map(fn (CommunityComment $reply): array => $this->presentComment($reply, $viewerId))
                    ->values()
                    ->all()
                : [],
        ];
    }

    /**
     * @return array{type: string, emoji: string, label: string, tone: string}|null
     */
    public function presentViewerReaction(?string $reactionValue): ?array
    {
        if ($reactionValue === null) {
            return null;
        }

        $reaction = ReactionType::tryFrom($reactionValue);

        if ($reaction === null) {
            return null;
        }

        return [
            'type' => $reaction->value,
            'emoji' => $reaction->emoji(),
            'label' => $reaction->label(),
            'tone' => $reaction->tone(),
        ];
    }

    private function canDeletePost(CommunityPost $post, int $viewerId): bool
    {
        if ($post->isOwnedBy($viewerId)) {
            return true;
        }

        $viewer = User::query()->find($viewerId);

        return $viewer !== null && $viewer->hasRole('admin');
    }
}
