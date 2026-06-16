<?php

namespace App\Livewire\Admin\Community\Posts;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\CommunityEngine\Enums\PostStatus;
use Modules\CommunityEngine\Models\CommunityPost;

class Index extends Component
{
    use WithAdminPage;
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $filterStatus = 'all';

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminUsersManage->value), 403);
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function approve(int $postId): void
    {
        CommunityPost::withTrashed()->findOrFail($postId)->update(['status' => PostStatus::Approved]);
        session()->flash('admin_status', 'Post approved.');
    }

    public function reject(int $postId): void
    {
        CommunityPost::withTrashed()->findOrFail($postId)->update(['status' => PostStatus::Rejected]);
        session()->flash('admin_status', 'Post rejected.');
    }

    public function forceDelete(int $postId): void
    {
        CommunityPost::withTrashed()->findOrFail($postId)->forceDelete();
        session()->flash('admin_status', 'Post permanently deleted.');
    }

    public function render(): View
    {
        $query = CommunityPost::query()
            ->withTrashed()
            ->with('author:id,name,email')
            ->latest();

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $q->where('content', 'like', '%'.$this->search.'%');
            });
        }

        return $this->adminView('livewire.admin.community.posts.index', [
            'posts' => $query->paginate(20),
            'statuses' => PostStatus::cases(),
        ], 'community');
    }
}
