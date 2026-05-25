@php
    $isDark = ($theme ?? 'light') === 'dark';
@endphp

@if (! empty($content['narrative']))
    <section @class(['eg-result-panel', 'eg-glass' => $isDark])>
        <h2 class="eg-result-panel-title">
            <i class="fa-solid fa-book-open"></i>
            {{ __('quiz.narrative_title') }}
        </h2>
        <p class="eg-result-body-text mb-0">{{ $content['narrative'] }}</p>
    </section>
@endif

@if (! empty($report['dimensions']))
    <section @class(['eg-result-panel', 'eg-glass' => $isDark])>
        <h2 class="eg-result-panel-title">{{ __('quiz.dimension_breakdown') }}</h2>
        <div class="row g-3">
            @foreach ($report['dimensions'] as $dimension)
                @php
                    $prefersRight = ($dimension['preference'] ?? '') === ($dimension['right_label'] ?? '');
                    $leftPercent = (int) ($dimension['percent'] ?? 50);
                    $rightPercent = 100 - $leftPercent;
                    $winPercent = $prefersRight ? $rightPercent : $leftPercent;
                @endphp
                <div class="col-md-6">
                    <div class="eg-axis-card">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span @class(['eg-axis-letter', 'is-active' => ! $prefersRight])>{{ $dimension['left_label'] ?? '' }}</span>
                            <span class="eg-axis-pct">{{ $winPercent }}%</span>
                            <span @class(['eg-axis-letter', 'is-active' => $prefersRight])>{{ $dimension['right_label'] ?? '' }}</span>
                        </div>
                        @if (! empty($dimension['left_name']) || ! empty($dimension['right_name']))
                            <div class="d-flex justify-content-between eg-axis-names mb-2">
                                <span @class(['small', 'fw-semibold', 'text-body-secondary' => ! $prefersRight, 'opacity-50' => $prefersRight])>{{ $dimension['left_name'] ?? '' }}</span>
                                <span @class(['small', 'fw-semibold', 'text-body-secondary' => $prefersRight, 'opacity-50' => ! $prefersRight])>{{ $dimension['right_name'] ?? '' }}</span>
                            </div>
                        @endif
                        <div class="eg-axis-track">
                            <div
                                class="eg-axis-fill {{ $prefersRight ? 'from-end' : 'from-start' }}"
                                style="width: {{ $winPercent }}%"
                            ></div>
                        </div>
                        <p class="eg-axis-pref small mb-0 mt-2">
                            {{ __('quiz.dimension_preference', ['letter' => $dimension['preference'] ?? '']) }}
                            @if (! empty($dimension['axis_name']))
                                <span class="d-block mt-1">{{ $dimension['axis_name'] }}</span>
                            @endif
                        </p>
                        @if (! empty($dimension['axis_description']))
                            <p class="eg-axis-desc small mb-0 mt-1">{{ $dimension['axis_description'] }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif

@if (! empty($content['strengths']))
    <section @class(['eg-result-panel', 'eg-glass' => $isDark])>
        <h2 class="eg-result-panel-title">
            <i class="fa-solid fa-sparkles"></i>
            {{ __('quiz.strengths_title') }}
        </h2>
        <div class="row g-3">
            @foreach ($content['strengths'] as $strength)
                <div class="col-md-4">
                    <div class="eg-result-chip-card">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ $strength }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif

@if (! empty($content['growth_areas']))
    <section @class(['eg-result-panel', 'eg-glass' => $isDark])>
        <h2 class="eg-result-panel-title">
            <i class="fa-solid fa-seedling"></i>
            {{ __('quiz.growth_title') }}
        </h2>
        <div class="row g-3">
            @foreach ($content['growth_areas'] as $area)
                <div class="col-md-6">
                    <div class="eg-result-note-card">
                        <span>{{ $area }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif

<div class="row g-4">
    @if (! empty($content['work_style']))
        <div class="col-lg-6">
            <section @class(['eg-result-panel', 'h-100', 'eg-glass' => $isDark])>
                <h2 class="eg-result-panel-title">
                    <i class="fa-solid fa-briefcase"></i>
                    {{ __('quiz.work_style_title') }}
                </h2>
                <p class="eg-result-body-text mb-0">{{ $content['work_style'] }}</p>
            </section>
        </div>
    @endif

    @if (! empty($content['relationships']))
        <div class="col-lg-6">
            <section @class(['eg-result-panel', 'h-100', 'eg-glass' => $isDark])>
                <h2 class="eg-result-panel-title">
                    <i class="fa-solid fa-heart"></i>
                    {{ __('quiz.relationships_title') }}
                </h2>
                <p class="eg-result-body-text mb-0">{{ $content['relationships'] }}</p>
            </section>
        </div>
    @endif
</div>

<div class="row g-4">
    @if (! empty($content['communication_style']))
        <div class="col-lg-6">
            <section @class(['eg-result-panel', 'h-100', 'eg-glass' => $isDark])>
                <h2 class="eg-result-panel-title">
                    <i class="fa-solid fa-comments"></i>
                    {{ __('quiz.communication_title') }}
                </h2>
                <p class="eg-result-body-text mb-0">{{ $content['communication_style'] }}</p>
            </section>
        </div>
    @endif

    @if (! empty($content['under_stress']))
        <div class="col-lg-6">
            <section @class(['eg-result-panel', 'h-100', 'eg-glass' => $isDark])>
                <h2 class="eg-result-panel-title">
                    <i class="fa-solid fa-cloud-bolt"></i>
                    {{ __('quiz.under_stress_title') }}
                </h2>
                <p class="eg-result-body-text mb-0">{{ $content['under_stress'] }}</p>
            </section>
        </div>
    @endif
</div>

@if (! empty($content['ideal_environment']))
    <section @class(['eg-result-panel', 'eg-glass' => $isDark])>
        <h2 class="eg-result-panel-title">
            <i class="fa-solid fa-mountain-sun"></i>
            {{ __('quiz.ideal_environment_title') }}
        </h2>
        <p class="eg-result-body-text mb-0">{{ $content['ideal_environment'] }}</p>
    </section>
@endif

@if (! empty($content['featured_people']))
    <section @class(['eg-result-panel', 'eg-result-famous', 'eg-glass' => $isDark])>
        <h2 class="eg-result-panel-title">
            <i class="fa-solid fa-star"></i>
            {{ __('quiz.famous_title') }}
        </h2>
        <div class="row g-3">
            @foreach ($content['featured_people'] as $person)
                <div class="col-md-4">
                    <article class="eg-famous-card h-100">
                        <div class="eg-famous-card-header">
                            <div class="eg-famous-avatar" aria-hidden="true">
                                {{ mb_substr((string) ($person['name'] ?? '?'), 0, 1) }}
                            </div>
                            <div>
                                <h3 class="eg-famous-name h6 mb-0">{{ $person['name'] ?? '' }}</h3>
                                <p class="eg-famous-role small mb-0">{{ $person['role'] ?? '' }}</p>
                            </div>
                            @if (! empty($person['match_score']))
                                <span class="eg-famous-match">{{ $person['match_score'] }}%</span>
                            @endif
                        </div>
                        <p class="eg-famous-bio small mb-2">{{ $person['bio'] ?? '' }}</p>
                        @if (! empty($person['era']))
                            <p class="eg-famous-era small mb-0">{{ $person['era'] }}</p>
                        @endif
                    </article>
                </div>
            @endforeach
        </div>
    </section>
@elseif (! empty($content['famous_examples']))
    <section @class(['eg-result-panel', 'eg-result-famous', 'eg-glass' => $isDark])>
        <h2 class="eg-result-panel-title">
            <i class="fa-solid fa-star"></i>
            {{ __('quiz.famous_title') }}
        </h2>
        <div class="d-flex flex-wrap gap-2">
            @foreach ($content['famous_examples'] as $name)
                <span class="eg-famous-pill">{{ $name }}</span>
            @endforeach
        </div>
    </section>
@endif
