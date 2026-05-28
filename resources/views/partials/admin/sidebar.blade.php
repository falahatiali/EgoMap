<aside class="eg-admin-sidebar" aria-label="{{ __('admin.nav.label') }}">
    <div class="eg-admin-sidebar-brand">
        <a href="{{ route('admin.dashboard') }}" class="eg-admin-brand-link">
            <span class="eg-admin-brand-mark" aria-hidden="true">
                <i class="fa-solid fa-shield-halved"></i>
            </span>
            <span class="eg-admin-brand-text">
                <span class="eg-admin-brand-title">{{ __('admin.panel_title') }}</span>
                <span class="eg-admin-brand-sub">{{ config('app.name') }}</span>
            </span>
        </a>
    </div>

    <nav class="eg-admin-nav">
        <p class="eg-admin-nav-heading">{{ __('admin.nav.section_main') }}</p>
        <ul class="eg-admin-nav-list">
            @foreach ($navItems as $item)
                <li>
                    @if ($item['enabled'] && $item['route'] !== null)
                        <a
                            href="{{ route($item['route']) }}"
                            @class(['eg-admin-nav-link', 'is-active' => ($activeNav ?? '') === $item['key']])
                        >
                            <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @else
                        <span class="eg-admin-nav-link is-disabled" title="{{ __('admin.nav.coming_soon_hint') }}">
                            <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $item['label'] }}</span>
                            @if ($item['badge'])
                                <span class="eg-admin-nav-badge">{{ $item['badge'] }}</span>
                            @endif
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="eg-admin-sidebar-foot">
        <a href="{{ route('home', ['locale' => $siteLocale]) }}" class="eg-admin-foot-link">
            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            <span>{{ __('admin.view_site') }}</span>
        </a>
    </div>
</aside>
