<div class="eg-admin-page eg-admin-page--wide">
    @include('partials.admin.page-head', [
        'title' => __('admin.quizzes.title'),
        'subtitle' => __('admin.quizzes.subtitle'),
    ])

    <div class="eg-admin-toolbar">
        <input
            type="search"
            class="eg-admin-input"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('admin.quizzes.search_placeholder') }}"
        >
        <select class="eg-admin-select" wire:model.live="activeFilter">
            <option value="">{{ __('admin.filters.all_statuses') }}</option>
            <option value="active">{{ __('admin.quizzes.active') }}</option>
            <option value="inactive">{{ __('admin.quizzes.inactive') }}</option>
        </select>
    </div>

    <div class="eg-admin-panel">
        <div class="eg-admin-table-wrap">
            <table class="eg-admin-table">
                <thead>
                    <tr>
                        <th>{{ __('admin.quizzes.slug') }}</th>
                        <th>{{ __('admin.quizzes.name') }}</th>
                        <th>{{ __('admin.quizzes.type') }}</th>
                        <th>{{ __('admin.quizzes.questions') }}</th>
                        <th>{{ __('admin.quizzes.sessions') }}</th>
                        <th>{{ __('admin.table.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quizzes as $quiz)
                        <tr wire:key="quiz-{{ $quiz->id }}">
                            <td class="eg-admin-table-mono">{{ $quiz->slug }}</td>
                            <td>{{ $quiz->getTranslation('name', 'en', true) }}</td>
                            <td><span class="eg-admin-tag">{{ $quiz->type->value }}</span></td>
                            <td>{{ number_format($quiz->questions_count) }}</td>
                            <td>{{ number_format($quiz->sessions_count) }}</td>
                            <td>
                                @if ($quiz->is_active)
                                    <span class="eg-admin-status eg-admin-status--completed">{{ __('admin.quizzes.active') }}</span>
                                @else
                                    <span class="eg-admin-status eg-admin-status--muted">{{ __('admin.quizzes.inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="eg-admin-btn eg-admin-btn--sm">
                                    {{ __('admin.actions.edit') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="eg-admin-table-empty">{{ __('admin.table.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($quizzes->hasPages())
            <div class="eg-admin-pagination">{{ $quizzes->links() }}</div>
        @endif
    </div>
</div>
