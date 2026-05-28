@php
    $user = auth()->user();
@endphp
<header class="eg-admin-topbar">
    <div class="eg-admin-topbar-start">
        <button
            type="button"
            class="eg-admin-menu-toggle d-lg-none"
            data-admin-sidebar-toggle
            aria-label="{{ __('admin.toggle_menu') }}"
        >
            <i class="fa-solid fa-bars"></i>
        </button>
        <div>
            <p class="eg-admin-topbar-eyebrow">{{ __('admin.welcome_back') }}</p>
            <h1 class="eg-admin-topbar-title mb-0">{{ $user?->name }}</h1>
        </div>
    </div>

    <div class="eg-admin-topbar-end">
        @if ($user?->isSuperAdmin())
            <span class="eg-admin-role-pill eg-admin-role-pill--super">
                <i class="fa-solid fa-crown" aria-hidden="true"></i>
                {{ __('admin.role_super_admin') }}
            </span>
        @elseif ($user?->isAdmin())
            <span class="eg-admin-role-pill">
                <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                {{ __('admin.role_admin') }}
            </span>
        @endif

        <form method="POST" action="{{ route('logout', ['locale' => $siteLocale]) }}" class="d-inline">
            @csrf
            <button type="submit" class="eg-admin-logout-btn">
                <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                <span>{{ __('auth.logout') }}</span>
            </button>
        </form>
    </div>
</header>
