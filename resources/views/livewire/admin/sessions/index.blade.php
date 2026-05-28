<div class="eg-admin-page eg-admin-page--wide">
    @include('partials.admin.page-head', [
        'title' => __('admin.sessions.title'),
        'subtitle' => __('admin.sessions.subtitle'),
    ])

    <div class="eg-admin-toolbar eg-admin-toolbar--3">
        <input
            type="search"
            class="eg-admin-input"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('admin.sessions.search_placeholder') }}"
        >
        <select class="eg-admin-select" wire:model.live="statusFilter">
            <option value="">{{ __('admin.filters.all_statuses') }}</option>
            @foreach ($statusOptions as $status)
                <option value="{{ $status->value }}">{{ __('admin.session_status.'.$status->value) }}</option>
            @endforeach
        </select>
        <select class="eg-admin-select" wire:model.live="quizFilter">
            <option value="">{{ __('admin.filters.all_quizzes') }}</option>
            @foreach ($quizOptions as $quiz)
                <option value="{{ $quiz->id }}">{{ $quiz->slug }}</option>
            @endforeach
        </select>
    </div>

    <div class="eg-admin-panel">
        <div class="eg-admin-table-wrap">
            <table class="eg-admin-table">
                <thead>
                    <tr>
                        <th>{{ __('admin.sessions.uuid') }}</th>
                        <th>{{ __('admin.table.quiz') }}</th>
                        <th>{{ __('admin.table.account') }}</th>
                        <th>{{ __('admin.table.status') }}</th>
                        <th>{{ __('admin.sessions.updated') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sessions as $session)
                        <tr wire:key="session-{{ $session->id }}">
                            <td class="eg-admin-table-mono">{{ Str::limit($session->uuid, 14) }}</td>
                            <td>{{ $session->quiz?->getTranslation('name', 'en', true) ?? '—' }}</td>
                            <td class="eg-admin-table-muted">{{ $session->user?->email ?? $session->email ?? __('admin.anonymous') }}</td>
                            <td>
                                <span @class([
                                    'eg-admin-status',
                                    'eg-admin-status--completed' => $session->status->value === 'completed',
                                    'eg-admin-status--progress' => $session->status->value === 'in_progress',
                                    'eg-admin-status--muted' => $session->status->value === 'abandoned',
                                ])>
                                    {{ __('admin.session_status.'.$session->status->value) }}
                                </span>
                            </td>
                            <td class="eg-admin-table-mono">{{ $session->updated_at?->format('M j, Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.sessions.show', $session) }}" class="eg-admin-btn eg-admin-btn--sm">
                                    {{ __('admin.actions.view') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="eg-admin-table-empty">{{ __('admin.table.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sessions->hasPages())
            <div class="eg-admin-pagination">{{ $sessions->links() }}</div>
        @endif
    </div>
</div>
