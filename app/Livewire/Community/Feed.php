<?php

namespace App\Livewire\Community;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\CommunityEngine\Enums\ReactionType;
use Modules\CommunityEngine\Models\CommunityPost;
use Modules\CommunityEngine\Services\CommunityPostService;

#[Layout('layouts.app')]
class Feed extends Component
{
    use WithPagination;

    #[Url(as: 'sort')]
    public string $sort = 'latest';

    public bool $showCreateModal = false;

    public string $locale = 'en';

    public function mount(): void
    {
        $this->locale = session('locale', 'en');
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        abort_unless(Auth::check(), 403);
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    #[On('post-created')]
    public function onPostCreated(): void
    {
        $this->showCreateModal = false;
        $this->resetPage();
        session()->flash('community_status', 'Your post is live! 🎉');
    }

    #[On('post-deleted')]
    public function onPostDeleted(): void
    {
        $this->resetPage();
    }

    public function toggleReaction(int $postId, string $reactionType): void
    {
        abort_unless(Auth::check(), 403);

        $post = CommunityPost::approved()->findOrFail($postId);
        $type = ReactionType::from($reactionType);

        app(CommunityPostService::class)->toggleReaction($post, Auth::user(), $type);
    }

    public function render(): View
    {
        $posts = app(CommunityPostService::class)->feed(
            sort: $this->sort,
            viewerId: Auth::id(),
            perPage: 10,
        );

        return view('livewire.community.feed', [
            'posts' => $posts,
            'reactionTypes' => ReactionType::forUi(),
            'sortOptions' => [
                'latest' => __('community.sort_latest'),
                'liked' => __('community.sort_liked'),
                'discussed' => __('community.sort_discussed'),
                'mine' => __('community.sort_mine'),
            ],
        ]);
    }
}
