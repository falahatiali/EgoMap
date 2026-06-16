<?php

namespace App\Livewire\Community;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Modules\CommunityEngine\Enums\ReactionType;
use Modules\CommunityEngine\Models\CommunityComment;
use Modules\CommunityEngine\Models\CommunityPost;
use Modules\CommunityEngine\Services\CommunityCommentService;

class CommentSection extends Component
{
    public int $postId;

    public bool $preview = false;

    public int $perPage = 50;

    public int $visibleLimit = 50;

    public string $newComment = '';

    public bool $isAnonymous = false;

    /** @var int|null ID of the comment being replied to */
    public ?int $replyingTo = null;

    public string $replyContent = '';

    public function mount(int $postId, bool $preview = false, int $perPage = 50): void
    {
        $this->postId = $postId;
        $this->preview = $preview;
        $this->perPage = $perPage;
        $this->visibleLimit = $preview
            ? CommunityCommentService::FEED_PREVIEW_LIMIT
            : $perPage;
    }

    public function loadMore(): void
    {
        if ($this->preview) {
            return;
        }

        $this->visibleLimit += $this->perPage;
    }

    public function submitComment(): void
    {
        abort_unless(Auth::check(), 403);

        $this->validate([
            'newComment' => ['required', 'string', 'min:2', 'max:500'],
            'isAnonymous' => ['boolean'],
        ]);

        $post = CommunityPost::approved()->findOrFail($this->postId);

        $result = app(CommunityCommentService::class)->create(
            user: Auth::user(),
            post: $post,
            content: $this->newComment,
            isAnonymous: $this->isAnonymous,
        );

        if ($result['rejected']) {
            $this->addError('newComment', $result['message']);

            return;
        }

        $this->reset('newComment', 'isAnonymous');
    }

    public function startReply(int $commentId): void
    {
        $this->replyingTo = $commentId;
        $this->replyContent = '';
    }

    public function cancelReply(): void
    {
        $this->replyingTo = null;
        $this->replyContent = '';
    }

    public function submitReply(): void
    {
        abort_unless(Auth::check(), 403);

        $this->validate([
            'replyContent' => ['required', 'string', 'min:2', 'max:500'],
        ]);

        $post = CommunityPost::approved()->findOrFail($this->postId);

        $result = app(CommunityCommentService::class)->create(
            user: Auth::user(),
            post: $post,
            content: $this->replyContent,
            parentId: $this->replyingTo,
        );

        if ($result['rejected']) {
            $this->addError('replyContent', $result['message']);

            return;
        }

        $this->cancelReply();
    }

    public function deleteComment(int $commentId): void
    {
        $comment = CommunityComment::findOrFail($commentId);
        app(CommunityCommentService::class)->delete($comment, Auth::user());
    }

    public function toggleCommentReaction(int $commentId, string $reactionType): void
    {
        abort_unless(Auth::check(), 403);

        $comment = CommunityComment::findOrFail($commentId);
        $type = ReactionType::from($reactionType);

        app(CommunityCommentService::class)->toggleReaction($comment, Auth::user(), $type);
    }

    public function render(): View
    {
        $post = CommunityPost::approved()->findOrFail($this->postId);

        $result = app(CommunityCommentService::class)->forPost(
            $post,
            Auth::id(),
            limit: $this->visibleLimit,
        );

        return view('livewire.community.comment-section', [
            'comments' => $result['comments'],
            'hasMore' => $result['has_more'],
            'commentsCount' => $post->comments_count,
            'reactionTypes' => ReactionType::forUi(),
        ]);
    }
}
