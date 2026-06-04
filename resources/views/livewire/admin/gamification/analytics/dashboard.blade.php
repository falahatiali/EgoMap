<div>
    @include('partials.admin.page-head', ['title' => __('admin.gamification.analytics_title'), 'subtitle' => __('admin.gamification.analytics_subtitle'), 'backRoute' => null])
    @include('partials.admin.gamification-nav', ['activeGamificationNav' => $activeGamificationNav])

    <div class="row g-3 mb-4">
        @foreach ([
            ['wallets', $summary['wallets']],
            ['transactions', $summary['transactions']],
            ['reward_rules', $summary['reward_rules']],
            ['penalty_rules', $summary['penalty_rules']],
            ['total_points', $summary['total_points']],
            ['total_coins', $summary['total_coins']],
            ['points_awarded', $summary['points_awarded']],
            ['points_penalized', $summary['points_penalized']],
        ] as [$key, $value])
            <div class="col-md-3 col-lg-3">
                <div class="eg-admin-card p-3">
                    <p class="small eg-text-muted mb-1">{{ __('admin.gamification.stat_'.$key) }}</p>
                    <p class="h4 mb-0">{{ eg_num($value) }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="eg-admin-card p-4 mb-4">
        <h2 class="h6">{{ __('admin.gamification.daily_awards') }}</h2>
        <div class="eg-admin-chart-bars">
            @php $max = max(1, collect($dailyAwards)->max(fn ($d) => max($d['points'], $d['coins']))); @endphp
            @foreach ($dailyAwards as $day)
                <div class="eg-admin-chart-bar" title="{{ $day['date'] }}">
                    <span style="height: {{ round(($day['points'] / $max) * 100) }}%" class="eg-admin-chart-bar__points"></span>
                    <span style="height: {{ round(($day['coins'] / $max) * 100) }}%" class="eg-admin-chart-bar__coins"></span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6"><div class="eg-admin-card p-3"><h2 class="h6">{{ __('admin.gamification.top_users') }}</h2><ul class="mb-0">@foreach ($topUsers as $u)<li>{{ $u['name'] }} — {{ eg_num($u['points']) }} pts / {{ eg_num($u['coins']) }} coins</li>@endforeach</ul></div></div>
        <div class="col-lg-6"><div class="eg-admin-card p-3"><h2 class="h6">{{ __('admin.gamification.top_rules') }}</h2><ul class="mb-0">@foreach ($topRules as $r)<li><code>{{ $r['rule_key'] }}</code> — {{ eg_num($r['count']) }}</li>@endforeach</ul></div></div>
        <div class="col-lg-6"><div class="eg-admin-card p-3"><h2 class="h6">{{ __('admin.gamification.top_badges') }}</h2><ul class="mb-0">@foreach ($topBadges as $b)<li>{{ $b['name'] }} — {{ eg_num($b['count']) }}</li>@endforeach</ul></div></div>
    </div>
</div>
