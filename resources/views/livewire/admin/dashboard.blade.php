@php
    $stats = $snapshot['stats'];
    $growth = $snapshot['growth'];
@endphp

<div class="eg-admin-page eg-admin-page--wide">
    <header class="eg-admin-page-head">
        <div>
            <p class="eg-admin-page-kicker">{{ __('admin.dashboard.kicker') }}</p>
            <h2 class="eg-admin-page-title">{{ __('admin.dashboard.title') }}</h2>
            <p class="eg-admin-page-sub">{{ __('admin.dashboard.subtitle') }}</p>
        </div>
        <div class="eg-admin-page-meta">
            <span class="eg-admin-live-dot" aria-hidden="true"></span>
            <span>{{ __('admin.dashboard.live') }}</span>
        </div>
    </header>

    <section class="eg-admin-stat-grid" aria-label="{{ __('admin.dashboard.stats_label') }}">
        <article class="eg-admin-stat-card">
            <div class="eg-admin-stat-icon eg-admin-stat-icon--users">
                <i class="fa-solid fa-users" aria-hidden="true"></i>
            </div>
            <p class="eg-admin-stat-label">{{ __('admin.stats.users_total') }}</p>
            <p class="eg-admin-stat-value">{{ number_format($stats['users_total']) }}</p>
            <p class="eg-admin-stat-foot">
                +{{ number_format($growth['users_today']) }} {{ __('admin.stats.today') }}
                · {{ number_format($stats['users_verified']) }} {{ __('admin.stats.verified') }}
            </p>
        </article>

        <article class="eg-admin-stat-card">
            <div class="eg-admin-stat-icon eg-admin-stat-icon--sessions">
                <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
            </div>
            <p class="eg-admin-stat-label">{{ __('admin.stats.sessions_total') }}</p>
            <p class="eg-admin-stat-value">{{ number_format($stats['sessions_total']) }}</p>
            <p class="eg-admin-stat-foot">
                +{{ number_format($growth['sessions_today']) }} {{ __('admin.stats.today') }}
                · {{ number_format($stats['sessions_completed']) }} {{ __('admin.stats.completed') }}
            </p>
        </article>

        <article class="eg-admin-stat-card">
            <div class="eg-admin-stat-icon eg-admin-stat-icon--ghost">
                <i class="fa-solid fa-ghost" aria-hidden="true"></i>
            </div>
            <p class="eg-admin-stat-label">{{ __('admin.stats.ghost_mode') }}</p>
            <p class="eg-admin-stat-value">{{ number_format($stats['ghost_mode_active']) }}</p>
            <p class="eg-admin-stat-foot">
                {{ __('admin.stats.ghost_active') }}
                · {{ number_format($stats['ghost_mode_completed']) }} {{ __('admin.stats.ghost_done') }}
            </p>
        </article>

        <article class="eg-admin-stat-card">
            <div class="eg-admin-stat-icon eg-admin-stat-icon--quizzes">
                <i class="fa-solid fa-flask" aria-hidden="true"></i>
            </div>
            <p class="eg-admin-stat-label">{{ __('admin.stats.quizzes') }}</p>
            <p class="eg-admin-stat-value">{{ number_format($stats['quizzes_total']) }}</p>
            <p class="eg-admin-stat-foot">
                {{ number_format($stats['sessions_in_progress']) }} {{ __('admin.stats.in_progress') }}
            </p>
        </article>
    </section>

    <section class="eg-admin-panels">
        <article class="eg-admin-panel">
            <header class="eg-admin-panel-head">
                <h3 class="eg-admin-panel-title">{{ __('admin.dashboard.recent_users') }}</h3>
            </header>
            <div class="eg-admin-table-wrap">
                <table class="eg-admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('admin.table.user') }}</th>
                            <th>{{ __('admin.table.roles') }}</th>
                            <th>{{ __('admin.table.joined') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($snapshot['recent_users'] as $row)
                            <tr>
                                <td>
                                    <span class="eg-admin-table-primary">{{ $row['name'] }}</span>
                                    <span class="eg-admin-table-muted">{{ $row['email'] }}</span>
                                </td>
                                <td>
                                    @foreach ($row['roles'] as $role)
                                        <span class="eg-admin-tag">{{ $role }}</span>
                                    @endforeach
                                    @if ($row['roles'] === [])
                                        <span class="eg-admin-table-muted">—</span>
                                    @endif
                                </td>
                                <td class="eg-admin-table-mono">
                                    {{ $row['created_at'] ? \Illuminate\Support\Carbon::parse($row['created_at'])->diffForHumans() : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="eg-admin-table-empty">{{ __('admin.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="eg-admin-panel">
            <header class="eg-admin-panel-head">
                <h3 class="eg-admin-panel-title">{{ __('admin.dashboard.recent_sessions') }}</h3>
            </header>
            <div class="eg-admin-table-wrap">
                <table class="eg-admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('admin.table.quiz') }}</th>
                            <th>{{ __('admin.table.account') }}</th>
                            <th>{{ __('admin.table.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($snapshot['recent_sessions'] as $row)
                            <tr>
                                <td>
                                    <span class="eg-admin-table-primary">{{ $row['quiz_name'] }}</span>
                                    <span class="eg-admin-table-muted eg-admin-table-mono">{{ Str::limit($row['uuid'], 12) }}</span>
                                </td>
                                <td class="eg-admin-table-muted">{{ $row['user_label'] }}</td>
                                <td>
                                    <span @class([
                                        'eg-admin-status',
                                        'eg-admin-status--completed' => $row['status'] === 'completed',
                                        'eg-admin-status--progress' => $row['status'] === 'in_progress',
                                        'eg-admin-status--muted' => $row['status'] === 'abandoned',
                                    ])>
                                        {{ __('admin.session_status.'.$row['status']) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="eg-admin-table-empty">{{ __('admin.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <aside class="eg-admin-callout">
        <div class="eg-admin-callout-icon" aria-hidden="true">
            <i class="fa-solid fa-sliders"></i>
        </div>
        <div>
            <h3 class="eg-admin-callout-title">{{ __('admin.dashboard.callout_title') }}</h3>
            <p class="eg-admin-callout-body mb-0">{{ __('admin.dashboard.callout_body') }}</p>
        </div>
    </aside>
</div>

@push('scripts')
    <script>
        document.querySelector('[data-admin-sidebar-toggle]')?.addEventListener('click', () => {
            document.body.classList.toggle('eg-admin-sidebar-open');
        });
    </script>
@endpush
