<div class="eg-admin-page eg-admin-page--wide">
    @include('partials.admin.page-head', [
        'title' => __('admin.mission_engine.title'),
        'subtitle' => __('admin.mission_engine.subtitle'),
    ])

    <div class="eg-admin-toolbar">
        <a href="{{ route('admin.mission-engine.templates.create') }}" class="eg-admin-btn eg-admin-btn--primary">
            {{ __('admin.mission_engine.create') }}
        </a>
        <input
            type="search"
            class="eg-admin-input"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('admin.mission_engine.search_placeholder') }}"
        >
        <select class="eg-admin-select" wire:model.live="statusFilter">
            <option value="">{{ __('admin.filters.all_statuses') }}</option>
            @foreach ($statusOptions as $status)
                <option value="{{ $status->value }}">{{ str($status->value)->headline() }}</option>
            @endforeach
        </select>
    </div>

    <div class="eg-admin-panel">
        <div class="eg-admin-table-wrap">
            <table class="eg-admin-table">
                <thead>
                    <tr>
                        <th>{{ __('admin.mission_engine.slug') }}</th>
                        <th>{{ __('admin.mission_engine.name') }}</th>
                        <th>{{ __('admin.mission_engine.category') }}</th>
                        <th>{{ __('admin.mission_engine.difficulty') }}</th>
                        <th>{{ __('admin.mission_engine.fields') }}</th>
                        <th>{{ __('admin.mission_engine.phases') }}</th>
                        <th>{{ __('admin.mission_engine.enrollments') }}</th>
                        <th>{{ __('admin.table.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr wire:key="mission-template-{{ $template->id }}">
                            <td class="eg-admin-table-mono">{{ $template->slug }}</td>
                            <td>{{ $template->getTranslation('title', 'en', true) }}</td>
                            <td>{{ $template->category?->getTranslation('name', 'en', true) ?? '—' }}</td>
                            <td><span class="eg-admin-tag">{{ $template->difficulty->value }}</span></td>
                            <td>{{ number_format($template->fields_count) }}</td>
                            <td>{{ number_format($template->phases_count) }}</td>
                            <td>{{ number_format($template->enrollments_count) }}</td>
                            <td>
                                <span class="eg-admin-status eg-admin-status--{{ $template->status === \Modules\MissionEngine\Enums\MissionTemplateStatus::Published ? 'completed' : 'muted' }}">
                                    {{ str($template->status->value)->headline() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.mission-engine.templates.edit', $template) }}" class="eg-admin-btn eg-admin-btn--sm">
                                    {{ __('admin.actions.edit') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="eg-admin-table-empty">{{ __('admin.table.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="eg-admin-pagination">
            {{ $templates->links() }}
        </div>
    </div>
</div>
