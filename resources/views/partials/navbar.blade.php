@php
    $nav = app(\App\Services\Recovery\RecoveryJourneyService::class)->navigationState();
    $hasMenu = $nav['show_explore_links'] || $nav['show_no_contact_link'] || auth()->check();
    $locale = app()->getLocale();
@endphp

<header class="eg-nav sticky-top">
    <div class="container-fluid eg-nav__container">
        <nav class="navbar navbar-expand-xl py-0 eg-nav__bar">
            <a class="eg-brand eg-nav__brand" href="{{ route('home') }}" wire:navigate>
                <span class="eg-brand-icon" aria-hidden="true">
                    <i class="fa-solid fa-compass"></i>
                </span>
                <span class="eg-brand-text" data-i18n="common.brand">{{ __('common.brand') }}</span>
            </a>

            @if ($hasMenu)
                <button
                    class="navbar-toggler eg-nav__toggle border-0 shadow-none"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#egNav"
                    aria-controls="egNav"
                    aria-expanded="false"
                    aria-label="{{ __('nav.menu') }}"
                >
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                </button>
            @endif

            <div @class(['collapse navbar-collapse eg-nav__collapse', 'show' => ! $hasMenu]) id="egNav">
                @if ($hasMenu)
                    <ul class="navbar-nav eg-nav__links flex-xl-nowrap">
                        @auth
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
                            <li class="nav-item eg-nav__item d-xl-none">
                                <span class="eg-nav__section-label">{{ __('nav.explore') }}</span>
                            </li>
                            @include('partials.nav-app-link', [
                                'href' => route('pricing'),
                                'icon' => 'tag',
                                'label' => __('nav.pricing'),
                                'mobileOnly' => true,
                            ])
                            @include('partials.nav-app-link', [
                                'href' => route('home').'#tests',
                                'icon' => 'clipboard-list',
                                'label' => __('nav.tests'),
                                'mobileOnly' => true,
                            ])
                            @include('partials.nav-app-link', [
                                'href' => route('home').'#framework',
                                'icon' => 'route',
                                'label' => __('nav.how_it_works'),
                                'mobileOnly' => true,
                            ])
                            @include('partials.nav-app-link', [
                                'href' => route('home').'#features',
                                'icon' => 'sparkles',
                                'label' => __('nav.report_preview'),
                                'mobileOnly' => true,
                            ])
                            @include('partials.nav-explore-dropdown')
                        @endif
                    </ul>
                @endif

                <div class="eg-nav__actions">
                    @auth
                        <div class="d-xl-none w-100">
                            @include('partials.nav-missions-link', ['variant' => 'button'])
                        </div>
                        <div class="d-xl-none w-100">
                            @include('partials.nav-profile-link')
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="d-xl-none w-100">
                            @csrf
                            <button type="submit" class="eg-nav-pill eg-nav-pill--ghost eg-nav-pill--block">
                                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                                <span>{{ __('auth.logout') }}</span>
                            </button>
                        </form>
                        <div class="d-none d-xl-block">
                            @include('partials.nav-user-menu')
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="eg-nav-pill eg-nav-pill--cta eg-transition" data-i18n="nav.login">
                            {{ __('nav.login') }}
                        </a>
                    @endauth
                </div>
            </div>
        </nav>
    </div>
</header>
