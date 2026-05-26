@php
    $nav = app(\App\Services\Recovery\RecoveryJourneyService::class)->navigationState();
@endphp

<header class="eg-nav eg-nav--guided sticky-top">
    <div class="container">
        <nav class="navbar py-0 h-100" style="min-height: var(--eg-nav-height);">
            <a class="eg-brand" href="{{ route('home') }}" wire:navigate>
                <span class="eg-brand-icon" aria-hidden="true">
                    <i class="fa-solid fa-compass"></i>
                </span>
                <span data-i18n="common.brand">{{ __('common.brand') }}</span>
            </a>

            <div class="d-flex align-items-center gap-3 ms-auto">
                @include('partials.language-switcher')

                @auth
                    @if ($nav['show_profile_link'])
                        <a href="{{ route('profile') }}" class="eg-nav-auth-link eg-transition d-none d-sm-inline" wire:navigate>
                            {{ __('profile.page_title') }}
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="d-none d-sm-inline">
                        @csrf
                        <button type="submit" class="btn btn-link eg-nav-auth-link px-0">
                            {{ __('auth.logout') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="eg-nav-auth-link eg-transition" data-i18n="nav.login">{{ __('nav.login') }}</a>
                @endauth
            </div>
        </nav>
    </div>
</header>
