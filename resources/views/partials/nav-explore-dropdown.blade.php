<li class="nav-item dropdown eg-nav-dropdown-wrap d-none d-xl-block">
    <button
        type="button"
        class="eg-nav-pill eg-nav-pill--menu dropdown-toggle"
        data-bs-toggle="dropdown"
        aria-expanded="false"
        id="egNavExplore"
    >
        <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
        <span>{{ __('nav.more') }}</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end eg-nav-dropdown-menu" aria-labelledby="egNavExplore">
        <li>
            <a class="dropdown-item eg-nav-dropdown-item" href="{{ route('pricing') }}" wire:navigate>
                <i class="fa-solid fa-tag" aria-hidden="true"></i>
                {{ __('nav.pricing') }}
            </a>
        </li>
        <li>
            <a class="dropdown-item eg-nav-dropdown-item" href="{{ route('home') }}#tests">
                <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                {{ __('nav.tests') }}
            </a>
        </li>
        <li>
            <a class="dropdown-item eg-nav-dropdown-item" href="{{ route('home') }}#framework">
                <i class="fa-solid fa-route" aria-hidden="true"></i>
                {{ __('nav.how_it_works') }}
            </a>
        </li>
        <li>
            <a class="dropdown-item eg-nav-dropdown-item" href="{{ route('home') }}#features">
                <i class="fa-solid fa-sparkles" aria-hidden="true"></i>
                {{ __('nav.report_preview') }}
            </a>
        </li>
    </ul>
</li>
