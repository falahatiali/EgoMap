@props([
    'title',
    'subtitle' => null,
    'backRoute' => null,
    'backLabel' => null,
])

<header class="eg-admin-page-head">
    <div>
        @if ($backRoute)
            <a href="{{ route($backRoute) }}" class="eg-admin-back-link">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                {{ $backLabel ?? __('admin.actions.back') }}
            </a>
        @endif
        <h2 class="eg-admin-page-title mb-0">{{ $title }}</h2>
        @if ($subtitle)
            <p class="eg-admin-page-sub mb-0">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="eg-admin-page-actions">
            {{ $actions }}
        </div>
    @endif
</header>
