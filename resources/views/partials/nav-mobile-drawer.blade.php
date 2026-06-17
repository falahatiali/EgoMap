@php
    use App\Services\Missions\MissionNavigationService;

    $missionNav = auth()->check()
        ? app(MissionNavigationService::class)->forUser(auth()->user())
        : null;
@endphp

<div
    class="offcanvas offcanvas-end eg-nav-drawer"
    tabindex="-1"
    id="egNavDrawer"
    aria-labelledby="egNavDrawerLabel"
>
    <div class="eg-nav-drawer__header">
        <h2 class="eg-nav-drawer__title" id="egNavDrawerLabel">{{ __('nav.menu') }}</h2>
        <button
            type="button"
            class="eg-nav-drawer__close"
            data-bs-dismiss="offcanvas"
            aria-label="{{ __('nav.close') }}"
        >
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    <div class="offcanvas-body eg-nav-drawer__body">
        @auth
            @if (isset($navProfile) && $navProfile !== null)
                <a
                    href="{{ route('profile') }}"
                    class="eg-nav-drawer__user eg-transition"
                    data-bs-dismiss="offcanvas"
                    data-bs-target="#egNavDrawer"
                    wire:navigate
                >
                    <span class="eg-nav-profile-avatar" aria-hidden="true">{{ $navProfile->initial }}</span>
                    <span class="eg-nav-drawer__user-text">
                        <span class="eg-nav-drawer__user-name">{{ $navProfile->name }}</span>
                        <span class="eg-nav-drawer__user-meta">{{ __('nav.account') }}</span>
                    </span>
                    @if ($navProfile->planBadge)
                        <span @class([
                            'eg-nav-plan-badge',
                            'eg-nav-plan-badge--'.$navProfile->planPeriod => $navProfile->planPeriod !== null,
                        ])>
                            <i class="fa-solid fa-crown" aria-hidden="true"></i>
                            <span>{{ $navProfile->planBadge }}</span>
                        </span>
                    @endif
                </a>
            @endif

            @if ($missionNav !== null)
                @include('partials.nav-drawer-link', [
                    'href' => $missionNav['href'],
                    'icon' => 'bullseye',
                    'label' => $missionNav['primary_label'],
                    'modifier' => 'missions',
                    'badge' => $missionNav['active_count'] > 0 ? eg_num($missionNav['active_count']) : null,
                ])
            @endif
        @endauth

        <nav class="eg-nav-drawer__nav" aria-label="{{ __('nav.page_navigation') }}">
            @auth
                @include('partials.nav-drawer-link', [
                    'href' => route('today', ['locale' => $locale]),
                    'icon' => 'sun',
                    'label' => __('nav.today'),
                ])
                @include('partials.nav-drawer-link', [
                    'href' => route('virtue.hub', ['locale' => $locale]),
                    'icon' => 'brain',
                    'label' => __('nav.virtue_forge'),
                ])
                @include('partials.nav-drawer-link', [
                    'href' => route('community.feed', ['locale' => $locale]),
                    'icon' => 'people-group',
                    'label' => __('nav.community'),
                ])
            @endauth

            @if ($nav['show_no_contact_link'])
                @include('partials.nav-drawer-link', [
                    'href' => route('no-contact'),
                    'icon' => 'ghost',
                    'label' => __('nav.no_contact'),
                ])
            @endif

            @if ($nav['show_explore_links'])
                <p class="eg-nav-drawer__section">{{ __('nav.explore') }}</p>
                @include('partials.nav-drawer-link', [
                    'href' => route('pricing'),
                    'icon' => 'tag',
                    'label' => __('nav.pricing'),
                ])
                @include('partials.nav-drawer-link', [
                    'href' => route('home').'#tests',
                    'icon' => 'clipboard-list',
                    'label' => __('nav.tests'),
                ])
                @include('partials.nav-drawer-link', [
                    'href' => route('home').'#framework',
                    'icon' => 'route',
                    'label' => __('nav.how_it_works'),
                ])
                @include('partials.nav-drawer-link', [
                    'href' => route('home').'#features',
                    'icon' => 'sparkles',
                    'label' => __('nav.report_preview'),
                ])
            @endif
        </nav>

        <div class="eg-nav-drawer__footer">
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="eg-nav-drawer-link eg-nav-drawer-link--logout">
                        <span class="eg-nav-drawer-link__icon" aria-hidden="true">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </span>
                        <span class="eg-nav-drawer-link__label">{{ __('auth.logout') }}</span>
                    </button>
                </form>
            @else
                <a
                    href="{{ route('login') }}"
                    class="eg-nav-drawer-link eg-nav-drawer-link--cta"
                    data-bs-dismiss="offcanvas"
                    data-bs-target="#egNavDrawer"
                    wire:navigate
                >
                    <span class="eg-nav-drawer-link__icon" aria-hidden="true">
                        <i class="fa-solid fa-right-to-bracket"></i>
                    </span>
                    <span class="eg-nav-drawer-link__label">{{ __('nav.login') }}</span>
                    <i class="fa-solid fa-chevron-right eg-nav-drawer-link__chevron" aria-hidden="true"></i>
                </a>
            @endauth
        </div>
    </div>
</div>
