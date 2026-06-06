<div class="eg-admin-page eg-admin-page--wide">
    @include('partials.admin.page-head', [
        'title' => __('admin.subscriptions.title'),
        'subtitle' => __('admin.subscriptions.subtitle'),
    ])

    <section class="eg-admin-stat-grid" aria-label="{{ __('admin.subscriptions.stats_label') }}">
        <article class="eg-admin-stat-card">
            <div class="eg-admin-stat-icon eg-admin-stat-icon--users">
                <i class="fa-solid fa-crown" aria-hidden="true"></i>
            </div>
            <p class="eg-admin-stat-label">{{ __('admin.subscriptions.active_count') }}</p>
            <p class="eg-admin-stat-value">{{ number_format($activeCount) }}</p>
        </article>
    </section>

    <div class="eg-admin-toolbar">
        <input
            type="search"
            class="eg-admin-input"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('admin.subscriptions.search_placeholder') }}"
        >
        <select class="eg-admin-select" wire:model.live="statusFilter">
            <option value="active">{{ __('admin.subscriptions.filter_active') }}</option>
            <option value="all">{{ __('admin.filters.all_statuses') }}</option>
        </select>
    </div>

    <div class="eg-admin-panel">
        <div class="eg-admin-table-wrap">
            <table class="eg-admin-table">
                <thead>
                    <tr>
                        <th>{{ __('admin.table.user') }}</th>
                        <th>{{ __('admin.subscriptions.plan') }}</th>
                        <th>{{ __('admin.table.status') }}</th>
                        <th>{{ __('admin.subscriptions.since') }}</th>
                        <th>{{ __('admin.subscriptions.renews') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subscriptions as $row)
                        <tr wire:key="subscription-{{ $row->subscription->id }}">
                            <td>
                                <span class="eg-admin-table-primary">{{ $row->user->name }}</span>
                                <span class="eg-admin-table-muted">{{ $row->user->email }}</span>
                            </td>
                            <td>
                                <span class="eg-admin-tag eg-admin-tag--plan">{{ $row->planLabel }}</span>
                            </td>
                            <td>
                                <span @class([
                                    'eg-admin-status',
                                    'eg-admin-status--completed' => in_array($row->statusKey, ['active', 'trialing'], true),
                                    'eg-admin-status--progress' => in_array($row->statusKey, ['past_due', 'unpaid', 'incomplete'], true),
                                    'eg-admin-status--muted' => ! in_array($row->statusKey, ['active', 'trialing', 'past_due', 'unpaid', 'incomplete'], true),
                                ])>
                                    {{ $row->statusLabel }}
                                </span>
                            </td>
                            <td class="eg-admin-table-mono">{{ $row->subscription->created_at?->format('M j, Y') }}</td>
                            <td class="eg-admin-table-mono">
                                @if ($row->subscription->trial_ends_at?->isFuture())
                                    {{ __('admin.subscriptions.trial_until', ['date' => $row->subscription->trial_ends_at->format('M j, Y')]) }}
                                @elseif ($row->subscription->ends_at?->isFuture())
                                    {{ __('admin.subscriptions.ends', ['date' => $row->subscription->ends_at->format('M j, Y')]) }}
                                @else
                                    {{ __('admin.subscriptions.ongoing') }}
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.users.edit', $row->user) }}" class="eg-admin-btn eg-admin-btn--sm">
                                    {{ __('admin.actions.view') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="eg-admin-table-empty">{{ __('admin.subscriptions.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($subscriptions->hasPages())
            <div class="eg-admin-pagination">{{ $subscriptions->links() }}</div>
        @endif
    </div>
</div>
