<header class="eg-nav sticky-top">
    <div class="container">
        <nav class="navbar navbar-expand-lg py-0 h-100" style="min-height: var(--eg-nav-height);">
            <a class="eg-brand" href="{{ route('home') }}">
                <span class="eg-brand-icon" aria-hidden="true">
                    <i class="fa-solid fa-compass"></i>
                </span>
                <span data-i18n="common.brand">{{ __('common.brand') }}</span>
            </a>

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

            <div class="collapse navbar-collapse" id="egNav">
                <ul class="navbar-nav mx-lg-auto gap-lg-4 mt-3 mt-lg-0">
                    <li class="nav-item">
                        <a class="eg-nav-link nav-link px-0 eg-transition" href="{{ route('home') }}#tests" data-i18n="nav.tests">{{ __('nav.tests') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="eg-nav-link nav-link px-0 eg-transition" href="{{ route('home') }}#how-it-works" data-i18n="nav.how_it_works">{{ __('nav.how_it_works') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="eg-nav-link nav-link px-0 eg-transition" href="{{ route('home') }}#report-preview" data-i18n="nav.report_preview">{{ __('nav.report_preview') }}</a>
                    </li>
                </ul>

                <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-3 mt-3 mt-lg-0">
                    @include('partials.language-switcher')

                    @auth
                        <a href="{{ route('profile') }}" class="eg-nav-profile-link eg-transition">
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
                        <a href="{{ route('login') }}" class="eg-nav-auth-link eg-transition">{{ __('nav.login') }}</a>
                        <a href="{{ route('register') }}" class="eg-btn-ghost btn-sm text-center">{{ __('nav.register') }}</a>
                    @endauth

                    <a href="{{ route('quiz.start', 'mbti-personality') }}" class="eg-btn-primary eg-transition eg-shadow-glow btn-sm text-center d-none d-lg-inline-flex">
                        <span data-i18n="nav.start_test">{{ __('nav.start_test') }}</span>
                        <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
                    </a>
                </div>
            </div>
        </nav>
    </div>
</header>
