@props(['title', 'description' => null, 'icon' => 'fa-layer-group'])

<header class="eg-admin-section-lead">
    <div class="eg-admin-section-lead__icon" aria-hidden="true">
        <i class="fa-solid {{ $icon }}"></i>
    </div>
    <div>
        <h3 class="eg-admin-section-lead__title">{{ $title }}</h3>
        @if ($description)
            <p class="eg-admin-section-lead__text">{{ $description }}</p>
        @endif
    </div>
</header>
