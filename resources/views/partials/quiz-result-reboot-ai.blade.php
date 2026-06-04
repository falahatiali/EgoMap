@if (! empty($content['ai_insights']))
    @php
        $ai = $content['ai_insights'];
    @endphp

    <section class="eg-result-panel eg-result-ai-panel">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <h2 class="eg-result-panel-title mb-0">{{ $ai['title'] }}</h2>
            <span class="eg-badge">{{ $ai['badge'] }}</span>
        </div>

        @if (! empty($ai['summary']))
            <p class="eg-result-body-text">{{ $ai['summary'] }}</p>
        @endif

        <div class="row g-3 mb-3">
            @if (! empty($ai['recovery_phase']))
                <div class="col-md-4">
                    <div class="eg-result-chip-card h-100">
                        <span class="small text-muted d-block mb-1">{{ __('quiz.reboot.stability_title') }}</span>
                        <span>{{ $ai['recovery_phase'] }}</span>
                    </div>
                </div>
            @endif
            @if (! empty($ai['main_risk']))
                <div class="col-md-4">
                    <div class="eg-result-chip-card h-100">
                        <span class="small text-muted d-block mb-1">{{ __('quiz.reboot.emergency_alert') }}</span>
                        <span>{{ $ai['main_risk'] }}</span>
                    </div>
                </div>
            @endif
            @if (! empty($ai['attachment_pattern']))
                <div class="col-md-4">
                    <div class="eg-result-chip-card h-100">
                        <span class="small text-muted d-block mb-1">{{ __('quiz.reboot.ai_attachment') }}</span>
                        <span>{{ $ai['attachment_pattern'] }}</span>
                    </div>
                </div>
            @endif
        </div>

        @if (! empty($ai['recommendations']))
            <h3 class="h6 mb-2">{{ __('quiz.reboot.ai_recommendations') }}</h3>
            <div class="vstack gap-2 mb-3">
                @foreach ($ai['recommendations'] as $index => $recommendation)
                    <div class="eg-result-chip-card flex-row align-items-start gap-3">
                        <span>{{ eg_num($index + 1) }}</span>
                        <span>{{ $recommendation }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if (! empty($ai['truth_flashes']))
            <h3 class="h6 mb-2">{{ __('quiz.reboot.ai_truth_title') }}</h3>
            <div class="vstack gap-2">
                @foreach ($ai['truth_flashes'] as $truth)
                    <div class="eg-result-chip-card">{{ $truth }}</div>
                @endforeach
            </div>
        @endif
    </section>
@endif
