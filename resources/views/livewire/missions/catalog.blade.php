<div class="eg-missions-page">
    <section class="container pt-3">
        @include('partials.page-nav-actions', [
            'links' => [
                [
                    'href' => route('home', ['locale' => $locale]),
                    'label' => __('quiz.back_home'),
                    'icon' => 'fa-house',
                ],
                [
                    'href' => route('profile', ['locale' => $locale]),
                    'label' => __('missions.back_profile'),
                    'icon' => 'fa-user',
                ],
            ],
        ])
    </section>

    <section class="eg-missions-hero">
        <div class="container">
            <div class="eg-missions-hero-card eg-glass">
                <h1 class="eg-display h3 mb-2">{{ __('missions.catalog_title') }}</h1>
                <p class="eg-text-muted mb-0">{{ __('missions.catalog_subtitle') }}</p>
            </div>
        </div>
    </section>

    <section class="container pb-5">
        @if ($templates->isEmpty())
            <div class="eg-glass p-4 text-center" style="border-radius: 1rem;">
                <p class="eg-text-muted mb-0">{{ __('missions.catalog_empty') }}</p>
            </div>
        @else
        <div class="eg-missions-grid">
            @foreach ($templates as $template)
                @php
                    $title = $template->getTranslation('title', $locale, true) ?: $template->getTranslation('title', 'en', true);
                    $summary = $template->getTranslation('summary', $locale, true) ?: $template->getTranslation('summary', 'en', true);
                    $enrollment = $enrollmentsByTemplate->get($template->id);
                    $categoryName = $template->category?->getTranslation('name', $locale, true);
                @endphp
                <article class="eg-mission-card eg-glass">
                    <div class="eg-mission-card-icon" aria-hidden="true">
                        <i class="fa-solid {{ $template->icon ?? 'fa-flag' }}"></i>
                    </div>
                    @if ($template->is_featured)
                        <span class="eg-badge">{{ __('missions.featured') }}</span>
                    @endif
                    @if ($categoryName)
                        <span class="small eg-text-muted">{{ $categoryName }}</span>
                    @endif
                    <h2 class="eg-mission-card-title">{{ $title }}</h2>
                    @if ($summary)
                        <p class="eg-text-muted small mb-0 flex-grow-1">{{ $summary }}</p>
                    @endif
                    @if ($template->estimated_days)
                        <p class="small mb-0">
                            <i class="fa-regular fa-clock me-1"></i>
                            {{ __('missions.days', ['count' => $template->estimated_days]) }}
                        </p>
                    @endif
                    @if (($template->meta['ghost_mode_recommended'] ?? false) === true)
                        <p class="small eg-text-muted mb-0">
                            <i class="fa-solid fa-ghost me-1"></i>
                            {{ __('missions.ghost_hint') }}
                        </p>
                    @endif
                    <div class="mt-2">
                        @if ($enrollment)
                            <a
                                href="{{ route('missions.workspace', ['locale' => $locale, 'enrollment' => $enrollment->uuid]) }}"
                                class="btn btn-primary w-100"
                                wire:navigate
                            >
                                {{ __('missions.continue_mission') }}
                            </a>
                        @else
                            <a
                                href="{{ route('missions.show', ['locale' => $locale, 'template' => $template->slug]) }}"
                                class="btn btn-outline-light w-100"
                                wire:navigate
                            >
                                {{ __('missions.preview_title') }}
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        @endif
    </section>
</div>
