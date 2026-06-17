@if (isset($navProfile) && $navProfile !== null)
    <div class="dropdown eg-nav-user-menu">
        <button
            type="button"
            class="eg-nav-user-toggle dropdown-toggle"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            id="egNavUser"
        >
            <span class="eg-nav-profile-avatar" aria-hidden="true">{{ $navProfile->initial }}</span>
            <span class="eg-nav-user-toggle-name d-none d-xxl-inline">{{ $navProfile->name }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end eg-nav-dropdown-menu" aria-labelledby="egNavUser">
            <li class="eg-nav-dropdown-header">
                <span class="eg-nav-dropdown-header-name">{{ $navProfile->name }}</span>
                @if ($navProfile->planBadge)
                    <span @class([
                        'eg-nav-plan-badge',
                        'eg-nav-plan-badge--'.$navProfile->planPeriod => $navProfile->planPeriod !== null,
                    ])>
                        <i class="fa-solid fa-crown" aria-hidden="true"></i>
                        <span>{{ $navProfile->planBadge }}</span>
                    </span>
                @endif
            </li>
            <li><hr class="dropdown-divider eg-nav-dropdown-divider"></li>
            <li>
                <a class="dropdown-item eg-nav-dropdown-item" href="{{ route('profile') }}" wire:navigate>
                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                    {{ __('nav.account') }}
                </a>
            </li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item eg-nav-dropdown-item eg-nav-dropdown-item--button">
                        <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                        {{ __('auth.logout') }}
                    </button>
                </form>
            </li>
        </ul>
    </div>
@endif
