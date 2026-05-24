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
                        <a class="eg-nav-link nav-link px-0 eg-transition" href="#how-it-works" data-i18n="nav.how_it_works">{{ __('nav.how_it_works') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="eg-nav-link nav-link px-0 eg-transition" href="#report-preview" data-i18n="nav.report_preview">{{ __('nav.report_preview') }}</a>
                    </li>
                </ul>

                <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-3 mt-3 mt-lg-0">
                    @include('partials.language-switcher')
                    <a href="#start" class="eg-btn-primary eg-transition eg-shadow-glow btn-sm text-center d-none d-lg-inline-flex">
                        <span data-i18n="nav.start_test">{{ __('nav.start_test') }}</span>
                        <i class="fa-solid eg-icon-directional" data-icon-directional></i>
                    </a>
                </div>
            </div>
        </nav>
    </div>
</header>
