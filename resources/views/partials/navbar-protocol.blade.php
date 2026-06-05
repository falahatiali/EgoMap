@php
    $locale = app()->getLocale();
@endphp
<header class="rh-nav">
    <div class="rh-nav__inner">
        <a class="rh-nav__brand" href="{{ route('home', ['locale' => $locale]) }}" wire:navigate>
            <span class="rh-nav__mark" aria-hidden="true">
                <i class="fa-solid fa-compass"></i>
            </span>
            <span class="rh-nav__name" data-i18n="common.brand">{{ __('common.brand') }}</span>
        </a>

        <nav class="rh-nav__links" aria-label="{{ __('landing.nav_aria') }}">
            <a href="#how" class="rh-nav__link" data-i18n="landing.nav_how">{{ __('landing.nav_how') }}</a>
            <a href="#protocol-90" class="rh-nav__link" data-i18n="landing.nav_protocol">{{ __('landing.nav_protocol') }}</a>
            <a href="#emergency" class="rh-nav__link rh-nav__link--alert" data-i18n="landing.nav_emergency">{{ __('landing.nav_emergency') }}</a>
            <a href="{{ route('pricing', ['locale' => $locale]) }}" class="rh-nav__link" wire:navigate data-i18n="landing.nav_pricing">{{ __('landing.nav_pricing') }}</a>
        </nav>

        <div class="rh-nav__actions">
            @include('partials.language-switcher', ['variant' => 'nav'])

            @auth
                <a
                    href="{{ route('profile', ['locale' => $locale]) }}"
                    class="rh-nav__btn rh-nav__btn--ghost"
                    data-i18n="profile.page_title"
                >
                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                    <span>{{ __('profile.page_title') }}</span>
                </a>
                <form method="POST" action="{{ route('logout', ['locale' => $locale]) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="rh-nav__btn rh-nav__btn--ghost">
                        {{ __('auth.logout') }}
                    </button>
                </form>
            @else
                <a
                    href="{{ route('login', ['locale' => $locale]) }}"
                    class="rh-nav__btn rh-nav__btn--ghost"
                    data-i18n="landing.nav_login"
                >{{ __('landing.nav_login') }}</a>
            @endauth
            <a
                href="{{ route('onboarding', ['locale' => $locale]) }}"
                class="rh-nav__btn rh-nav__btn--cta"
                wire:navigate
            >
                <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                <span data-i18n="landing.cta_step1">{{ __('landing.cta_step1') }}</span>
            </a>
        </div>
    </div>
</header>
