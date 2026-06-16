<?php

namespace App\Livewire\Community;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\CommunityEngine\Enums\PostStatus;
use Modules\CommunityEngine\Enums\ReactionType;
use Modules\CommunityEngine\Models\CommunityPost;
use Modules\CommunityEngine\Services\CommunityCommentService;
use Modules\CommunityEngine\Services\CommunityPostService;

#[Layout('layouts.app')]
class ShowPost extends Component
{
    public CommunityPost $post;

    public string $locale = 'en';

    public function mount(CommunityPost $post, CommunityPostService $posts): void
    {
        abort_unless($post->status === PostStatus::Approved && ! $post->trashed(), 404);

        $this->locale = session('locale', 'en');
        $this->post = $posts->findForDisplay($post->id, Auth::id());
        $posts->incrementView($this->post);
    }

    public function toggleReaction(int $postId, string $reactionType): void
    {
        abort_unless(Auth::check(), 403);

        $post = CommunityPost::approved()->findOrFail($postId);
        $type = ReactionType::from($reactionType);

        app(CommunityPostService::class)->toggleReaction($post, Auth::user(), $type);

        $this->post = app(CommunityPostService::class)->findForDisplay($postId, Auth::id());
    }

    public function deletePost(): void
    {
        abort_unless(Auth::check(), 403);

        $deleted = app(CommunityPostService::class)->delete($this->post, Auth::user());

        abort_unless($deleted, 403);

        session()->flash('community_status', __('community.post_deleted'));

        $this->redirect(route('community.feed', ['locale' => $this->locale]), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.community.show-post', [
            'reactionGroups' => ReactionType::forUiGrouped(),
            'commentsPerPage' => CommunityCommentService::POST_PAGE_LIMIT,
        ]);
    }
}
