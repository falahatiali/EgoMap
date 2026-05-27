@php
    use App\Support\LocaleConfig;

    $locale = LocaleConfig::resolve($quizLocale ?? app()->getLocale());
    $score = (int) ($report['stability_score'] ?? 0);
    $phase = $report['phase'] ?? [];
    $prescription = $report['first_prescription'] ?? [];
    $steps = $report['next_steps'] ?? [];
@endphp

@if (! empty($report['report_disclaimer']))
    <p class="eg-result-body-text small text-muted mb-4">
        {{ LocaleConfig::pick($report['report_disclaimer'], $locale) }}
    </p>
@endif

<section class="eg-result-panel">
    <h2 class="eg-result-panel-title">{{ __('quiz.reboot.stability_title') }}</h2>
    <div class="d-flex align-items-center gap-4">
        <div class="eg-reboot-score-ring" style="--score: {{ $score }};">
            <span class="eg-reboot-score-value">{{ $score }}%</span>
        </div>
        <p class="eg-result-body-text mb-0">
            {{ LocaleConfig::pick($phase, $locale) }}
        </p>
    </div>
    <p class="eg-result-body-text small text-muted mt-2 mb-0">{{ __('quiz.reboot.stability_note') }}</p>
</section>

@if (! empty($report['emergency']))
    <section class="eg-result-panel border border-danger-subtle bg-danger-subtle">
        <p class="mb-0 fw-semibold text-danger">{{ __('quiz.reboot.emergency_alert') }}</p>
    </section>
@endif

<section class="eg-result-panel">
    <h2 class="eg-result-panel-title">{{ __('quiz.reboot.first_prescription') }}</h2>
    <p class="eg-result-body-text mb-0">
        {{ LocaleConfig::pick($prescription, $locale) }}
    </p>
</section>

@if (! empty($content['sections']))
    @foreach ($content['sections'] as $section)
        <section class="eg-result-panel">
            <h2 class="eg-result-panel-title">
                {{ LocaleConfig::pick([
                    'en' => $section['heading_en'] ?? '',
                    'fa' => $section['heading_fa'] ?? '',
                ], $locale) }}
            </h2>
            <p class="eg-result-body-text mb-0">
                {{ LocaleConfig::pick([
                    'en' => $section['body_en'] ?? '',
                    'fa' => $section['body_fa'] ?? '',
                ], $locale) }}
            </p>
        </section>
    @endforeach
@endif

@if (! empty($steps))
    <section class="eg-result-panel">
        <h2 class="eg-result-panel-title">{{ __('quiz.reboot.next_steps') }}</h2>
        <div class="vstack gap-3">
            @foreach ($steps as $index => $step)
                <div class="eg-result-chip-card flex-row align-items-start gap-3">
                    <span class="fw-bold">{{ $index + 1 }}</span>
                    <span>{{ LocaleConfig::pick($step, $locale) }}</span>
                </div>
            @endforeach
        </div>
    </section>
@endif
