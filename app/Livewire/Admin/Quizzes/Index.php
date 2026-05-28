<?php

namespace App\Livewire\Admin\Quizzes;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use App\Models\Quiz;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithAdminPage;
    use WithPagination;

    public string $search = '';

    public string $activeFilter = '';

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminQuizzesManage->value), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingActiveFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $quizzes = Quiz::query()
            ->withCount(['questions', 'sessions'])
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('slug', 'like', $term)
                        ->orWhere('name->en', 'like', $term);
                });
            })
            ->when($this->activeFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->activeFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('slug')
            ->paginate(20);

        return $this->adminView('livewire.admin.quizzes.index', [
            'quizzes' => $quizzes,
        ], 'quizzes');
    }
}
