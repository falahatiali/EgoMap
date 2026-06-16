<div>
    @include('partials.admin.page-head', [
        'title' => 'Community Posts',
        'subtitle' => 'Moderate and manage community posts.',
        'backRoute' => null,
    ])

    @if (session('admin_status'))
        <div class="alert alert-success mb-4">{{ session('admin_status') }}</div>
    @endif

    {{-- Filters --}}
    <div class="eg-admin-card mb-3">
        <div class="d-flex gap-3 align-items-center flex-wrap">
            <input wire:model.live="search" type="text" placeholder="Search content…"
                class="eg-admin-input" style="max-width: 280px;">
            <select wire:model.live="filterStatus" class="eg-admin-input" style="max-width: 160px;">
                <option value="all">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ ucfirst($status->value) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="eg-admin-card">
        <div class="table-responsive">
            <table class="table eg-admin-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Author</th>
                        <th>Content</th>
                        <th>Status</th>
                        <th>Reactions</th>
                        <th>Comments</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        <tr>
                            <td class="text-muted small">{{ $post->id }}</td>
                            <td>
                                <span class="small">{{ $post->is_anonymous ? '🎭 Anonymous' : ($post->author?->name ?? '—') }}</span>
                                @if (! $post->is_anonymous && $post->author)
                                    <br><span class="text-muted" style="font-size: .7rem;">{{ $post->author->email }}</span>
                                @endif
                            </td>
                            <td style="max-width: 320px;">
                                <span class="small">{{ Str::limit($post->content, 120) }}</span>
                                @if ($post->deleted_at)
                                    <span class="badge bg-secondary ms-1">deleted</span>
                                @endif
                            </td>
                            <td>
                                <span @class([
                                    'badge',
                                    'bg-success' => $post->status->value === 'approved',
                                    'bg-warning text-dark' => $post->status->value === 'pending',
                                    'bg-danger' => $post->status->value === 'rejected',
                                    'bg-secondary' => $post->status->value === 'reported',
                                ])>{{ $post->status->value }}</span>
                            </td>
                            <td class="small">{{ $post->likes_count }}</td>
                            <td class="small">{{ $post->comments_count }}</td>
                            <td class="small text-muted">{{ $post->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    @if ($post->status->value !== 'approved')
                                        <button wire:click="approve({{ $post->id }})"
                                            wire:confirm="Approve this post?"
                                            class="eg-admin-btn eg-admin-btn--sm eg-admin-btn--success">
                                            ✓ Approve
                                        </button>
                                    @endif
                                    @if ($post->status->value !== 'rejected')
                                        <button wire:click="reject({{ $post->id }})"
                                            wire:confirm="Reject this post?"
                                            class="eg-admin-btn eg-admin-btn--sm eg-admin-btn--warning">
                                            Reject
                                        </button>
                                    @endif
                                    <button wire:click="forceDelete({{ $post->id }})"
                                        wire:confirm="Permanently delete? This cannot be undone."
                                        class="eg-admin-btn eg-admin-btn--sm eg-admin-btn--danger">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No posts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $posts->links() }}
        </div>
    </div>
</div>
