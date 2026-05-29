@php
    $title = $template->getTranslation('title', $locale, true) ?: $template->getTranslation('title', 'en', true);
    $summary = $template->getTranslation('summary', $locale, true) ?: $template->getTranslation('summary', 'en', true);
    $description = $template->getTranslation('description', $locale, true) ?: $template->getTranslation('description', 'en', true);
@endphp

<div class="eg-missions-page">
    <section class="container pt-3">
        @include('partials.page-nav-actions', [
            'links' => [
                [
                    'href' => route('missions.catalog', ['locale' => $locale]),
                    'label' => __('missions.back_catalog'),
                    'icon' => 'fa-compass',
                ],
            ],
        ])
    </section>

    <section class="container pb-5">
        <div class="eg-glass p-4 mb-4" style="border-radius: 1rem;">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="eg-mission-card-icon" aria-hidden="true">
                    <i class="fa-solid {{ $template->icon ?? 'fa-dumbbell' }}"></i>
                </div>
                <div>
                    <h1 class="eg-display h3 mb-2">{{ $title }}</h1>
                    @if ($summary)
                        <p class="eg-text-muted mb-0">{{ $summary }}</p>
                    @endif
                </div>
            </div>

            @if ($description)
                <p class="mb-4">{{ $description }}</p>
            @endif

            @if ($template->phases->isNotEmpty())
                <h2 class="h5 mb-2">{{ __('missions.phases') }}</h2>
                <ul class="mb-4">
                    @foreach ($template->phases as $phase)
                        <li class="mb-1">
                            <strong>{{ $phase->getTranslation('title', $locale, true) ?: $phase->getTranslation('title', 'en', true) }}</strong>
                            @if ($phase->duration_days)
                                <span class="eg-text-muted small">— {{ __('missions.days', ['count' => $phase->duration_days]) }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            <h2 class="h5 mb-2">{{ __('missions.includes') }}</h2>
            <ul class="row row-cols-1 row-cols-md-2 g-2 mb-4 list-unstyled">
                @foreach ($enabledCapabilities as $cap)
                    @php
                        $capName = $cap->capabilityType->getTranslation('name', $locale, true)
                            ?: $cap->capabilityType->getTranslation('name', 'en', true);
                    @endphp
                    <li class="col">
                        <span class="d-inline-flex align-items-center gap-2">
                            <i class="fa-solid {{ $cap->capabilityType->icon ?? 'fa-circle' }} text-success"></i>
                            {{ $capName }}
                        </span>
                    </li>
                @endforeach
            </ul>

            <div class="d-flex flex-wrap gap-2">
                @auth
                    @if ($activeEnrollment)
                        <button type="button" class="btn btn-primary" wire:click="openWorkspace">
                            {{ __('missions.continue_mission') }}
                        </button>
                        <span class="align-self-center small eg-text-muted">{{ __('missions.already_active') }}</span>
                    @else
                        <button type="button" class="btn btn-primary" wire:click="startMission">
                            {{ __('missions.start_mission') }}
                        </button>
                    @endif
                @else
                    <a href="{{ route('login', ['locale' => $locale]) }}" class="btn btn-primary" wire:navigate>
                        {{ __('missions.login_to_start') }}
                    </a>
                @endauth
            </div>
        </div>
    </section>
</div>
