<div class="eg-admin-panel">
    <div class="eg-admin-panel--padded pb-0">
        @include('livewire.admin.mission-engine.templates.partials.section-lead', [
            'title' => __('admin.mission_engine.tab_enrollments'),
            'description' => __('admin.mission_engine.enrollments_intro'),
            'icon' => 'fa-users',
        ])
    </div>

    <div class="eg-admin-table-wrap">
        <table class="eg-admin-table">
            <thead>
                <tr>
                    <th>{{ __('admin.mission_engine.enrollment_user') }}</th>
                    <th>{{ __('admin.mission_engine.enrollment_status') }}</th>
                    <th>{{ __('admin.mission_engine.enrollment_started') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($enrollments as $enrollment)
                    <tr wire:key="enrollment-{{ $enrollment->id }}">
                        <td>
                            @if ($enrollment->user)
                                <span class="eg-admin-table-primary">{{ $enrollment->user->name }}</span>
                                <span class="eg-admin-table-muted d-block">{{ $enrollment->user->email }}</span>
                            @else
                                <span class="eg-admin-table-muted">{{ __('admin.mission_engine.guest_enrollment') }}</span>
                            @endif
                        </td>
                        <td><span class="eg-admin-tag">{{ str($enrollment->status->value)->headline() }}</span></td>
                        <td class="eg-admin-table-mono">{{ $enrollment->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="eg-admin-table-empty">{{ __('admin.mission_engine.enrollments_empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($enrollments && $enrollments->hasPages())
        <div class="eg-admin-pagination">
            {{ $enrollments->links() }}
        </div>
    @endif
</div>
