@php
    $nav = app(\App\Services\Recovery\RecoveryJourneyService::class)->navigationState();
    $hasMenu = $nav['show_explore_links'] || $nav['show_no_contact_link'] || auth()->check();
    $locale = app()->getLocale();
@endphp

<header class="eg-nav sticky-top">
    <div class="container-fluid eg-nav__container">
        <nav class="navbar navbar-expand-xl py-0 eg-nav__bar w-100">
            <a class="eg-brand eg-nav__brand" href="{{ route('home') }}" wire:navigate>
                <span class="eg-brand-icon" aria-hidden="true">
                    <i class="fa-solid fa-compass"></i>
                </span>
                <span class="eg-brand-text" data-i18n="common.brand">{{ __('common.brand') }}</span>
            </a>

            @if ($hasMenu)
                <button
                    class="eg-nav__toggle d-xl-none ms-auto border-0 shadow-none"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#egNavDrawer"
                    aria-controls="egNavDrawer"
                    aria-label="{{ __('nav.menu') }}"
                >
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                </button>
            @elseif (auth()->guest())
                <a
                    href="{{ route('login') }}"
                    class="eg-nav-pill eg-nav-pill--cta eg-transition d-xl-none ms-auto"
                    data-i18n="nav.login"
                    wire:navigate
                >
                    {{ __('nav.login') }}
                </a>
            @endif

            <div class="eg-nav__desktop d-none d-xl-flex align-items-center ms-auto">
                @if ($hasMenu)
                    <ul class="navbar-nav eg-nav__links flex-nowrap">
                        @auth
                            @include('partials.nav-app-link', [
                                'href' => route('today', ['locale' => $locale]),
                                'icon' => 'sun',
                                'label' => __('nav.today'),
                            ])
                            @include('partials.nav-missions-link')
                            @include('partials.nav-app-link', [
                                'href' => route('virtue.hub', ['locale' => $locale]),
                                'icon' => 'brain',
                                'label' => __('nav.virtue_forge'),
                            ])
                            @include('partials.nav-app-link', [
                                'href' => route('community.feed', ['locale' => $locale]),
                                'icon' => 'people-group',
                                'label' => __('nav.community'),
                            ])
                        @endauth

                        @if ($nav['show_no_contact_link'])
                            @include('partials.nav-app-link', [
                                'href' => route('no-contact'),
                                'icon' => 'ghost',
                                'label' => __('nav.no_contact'),
                                'modifier' => 'ghost',
                            ])
                        @endif

                        @if ($nav['show_explore_links'])
                            @include('partials.nav-explore-dropdown')
                        @endif
                    </ul>
                @endif

                <div class="eg-nav__actions">
                    @auth
                        @include('partials.nav-user-menu')
                    @else
                        <a href="{{ route('login') }}" class="eg-nav-pill eg-nav-pill--cta eg-transition" data-i18n="nav.login" wire:navigate>
                            {{ __('nav.login') }}
                        </a>
                    @endauth
                </div>
            </div>
        </nav>
    </div>

    @if ($hasMenu)
        @include('partials.nav-mobile-drawer', [
            'nav' => $nav,
            'locale' => $locale,
        ])
    @endif
</header>
