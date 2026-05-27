@php
    $nav = app(\App\Services\Recovery\RecoveryJourneyService::class)->navigationState();
    $hasMenu = $nav['show_explore_links'] || $nav['show_no_contact_link'];
@endphp

<header class="eg-nav sticky-top">
    <div class="container">
        <nav class="navbar navbar-expand-lg py-0 h-100" style="min-height: var(--eg-nav-height);">
            <a class="eg-brand" href="{{ route('home') }}" wire:navigate>
                <span class="eg-brand-icon" aria-hidden="true">
                    <i class="fa-solid fa-compass"></i>
                </span>
                <span data-i18n="common.brand">{{ __('common.brand') }}</span>
            </a>

            @if ($hasMenu)
                <button
                    class="navbar-toggler border-0 shadow-none"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#egNav"
                    aria-controls="egNav"
                    aria-expanded="false"
                    aria-label="Menu"
                >
                    <i class="fa-solid fa-bars text-white"></i>
                </button>
            @endif

            <div @class(['collapse navbar-collapse', 'show' => ! $hasMenu]) id="egNav">
                @if ($hasMenu)
                    <ul class="navbar-nav mx-lg-auto gap-lg-4 mt-3 mt-lg-0">
                        @if ($nav['show_no_contact_link'])
                            <li class="nav-item">
                                <a class="eg-nav-link nav-link px-0 eg-transition" href="{{ route('no-contact') }}" wire:navigate data-i18n="nav.no_contact">{{ __('nav.no_contact') }}</a>
                            </li>
                        @endif
                        @if ($nav['show_explore_links'])
                            <li class="nav-item">
                                <a class="eg-nav-link nav-link px-0 eg-transition" href="{{ route('home') }}#tests" data-i18n="nav.tests">{{ __('nav.tests') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="eg-nav-link nav-link px-0 eg-transition" href="{{ route('home') }}#framework" data-i18n="nav.how_it_works">{{ __('nav.how_it_works') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="eg-nav-link nav-link px-0 eg-transition" href="{{ route('home') }}#features" data-i18n="nav.report_preview">{{ __('nav.report_preview') }}</a>
                            </li>
                        @endif
                    </ul>
                @endif

                <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-3 mt-3 mt-lg-0 ms-lg-auto">
                    @include('partials.language-switcher')

                    @auth
                        <a href="{{ route('profile') }}" class="eg-nav-profile-link eg-transition" wire:navigate>
                            <span class="eg-nav-profile-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="d-none d-xl-inline">{{ __('profile.page_title') }}</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-link eg-nav-auth-link px-0">
                                {{ __('auth.logout') }}
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="eg-nav-auth-link eg-transition" data-i18n="nav.login">{{ __('nav.login') }}</a>
                    @endauth
                </div>
            </div>
        </nav>
    </div>
</header>
