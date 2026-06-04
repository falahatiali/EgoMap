<div>
    @include('partials.admin.page-head', [
        'title' => __('admin.gamification.catalog_title'),
        'subtitle' => __('admin.gamification.catalog_subtitle'),
        'backRoute' => null,
    ])

    @include('partials.admin.gamification-nav', ['activeGamificationNav' => $activeGamificationNav])

    <div class="row g-3 mb-4">
        @foreach ([
            ['rewards', $overview['rewards_count']],
            ['penalties', $overview['penalties_count']],
            ['badges', $overview['badges']],
            ['shop', $overview['shop_items']],
        ] as [$label, $value])
            <div class="col-md-3">
                <div class="eg-admin-card p-3">
                    <p class="small eg-text-muted mb-1">{{ __('admin.gamification.catalog_stat_'.$label) }}</p>
                    <p class="h4 mb-0">{{ eg_num($value) }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="eg-admin-card p-3">
                <h2 class="h6 text-success">{{ __('admin.gamification.catalog_rewards') }} ({{ count($rewards) }})</h2>
                <div class="table-responsive">
                    <table class="table table-sm eg-admin-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('admin.gamification.col_key') }}</th>
                                <th>{{ __('admin.gamification.col_event') }}</th>
                                <th>{{ __('admin.gamification.col_effects') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rewards as $rule)
                                <tr @class(['opacity-50' => ! $rule['is_active']])>
                                    <td><a href="{{ $rule['edit_url'] }}"><code>{{ $rule['key'] }}</code></a>@if (! $rule['is_active']) <span class="badge bg-secondary">{{ __('admin.inactive') }}</span>@endif</td>
                                    <td class="small">{{ $rule['event'] }}</td>
                                    <td class="small">{{ $rule['effects_summary'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="eg-text-muted">{{ __('admin.gamification.no_rules') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="eg-admin-card p-3">
                <h2 class="h6 text-danger">{{ __('admin.gamification.catalog_penalties') }} ({{ count($penalties) }})</h2>
                <div class="table-responsive">
                    <table class="table table-sm eg-admin-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('admin.gamification.col_key') }}</th>
                                <th>{{ __('admin.gamification.col_event') }}</th>
                                <th>{{ __('admin.gamification.col_effects') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($penalties as $rule)
                                <tr @class(['opacity-50' => ! $rule['is_active']])>
                                    <td><a href="{{ $rule['edit_url'] }}"><code>{{ $rule['key'] }}</code></a>@if (! $rule['is_active']) <span class="badge bg-secondary">{{ __('admin.inactive') }}</span>@endif</td>
                                    <td class="small">{{ $rule['event'] }}</td>
                                    <td class="small">{{ $rule['effects_summary'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="eg-text-muted">{{ __('admin.gamification.no_rules') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="eg-admin-card p-3 mb-4">
        <h2 class="h6">{{ __('admin.gamification.catalog_events') }}</h2>
        @foreach ($eventsMap as $group)
            <div class="mb-3">
                <p class="mb-1"><code>{{ $group['event'] }}</code>
                    <span class="small eg-text-muted">— {{ count($group['rules']) }} {{ __('admin.gamification.catalog_rules_attached') }}</span>
                </p>
                @if ($group['rules'] !== [])
                    <ul class="small mb-0">
                        @foreach ($group['rules'] as $rule)
                            <li>
                                <span @class(['text-success' => $rule['type'] === 'reward', 'text-danger' => $rule['type'] === 'penalty'])>{{ $rule['type'] }}</span>:
                                {{ $rule['name'] }} ({{ $rule['effects_summary'] }})
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="eg-admin-card p-3">
                <h2 class="h6">{{ __('admin.gamification.catalog_api') }}</h2>
                <p class="small eg-text-muted">{{ __('admin.gamification.catalog_api_hint') }}</p>
                <table class="table table-sm eg-admin-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('admin.gamification.col_method') }}</th>
                            <th>{{ __('admin.gamification.col_signature') }}</th>
                            <th>HTTP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($apiReference as $row)
                            <tr>
                                <td><code>{{ $row['method'] }}</code></td>
                                <td class="small font-monospace">{{ $row['signature'] }}</td>
                                <td class="small">{{ $row['http'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="eg-admin-card p-3">
                <h2 class="h6">{{ __('admin.gamification.catalog_effect_fields') }}</h2>
                <ul class="small mb-0">
                    @foreach ($effectFields as $field)
                        <li><code>{{ $field['key'] }}</code> ({{ $field['type'] }}) — {{ $field['description'] }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
