@if (! empty($report['dimension_rows']))
    <section @class(['eg-result-panel', 'eg-glass' => ($theme ?? 'light') === 'dark'])>
        <h2 class="eg-result-panel-title">{{ __('quiz.reboot.dimension_breakdown') }}</h2>
        <p class="eg-result-body-text small text-muted mb-4">{{ __('quiz.reboot.dimension_breakdown_intro') }}</p>
        <div class="row g-3">
            @foreach ($report['dimension_rows'] as $dimension)
                @php
                    $percent = (int) ($dimension['percent'] ?? 0);
                    $isReadiness = ($dimension['key'] ?? '') === 'readiness';
                @endphp
                <div class="col-md-6">
                    <div class="eg-reboot-dimension-card">
                        <div class="eg-reboot-dimension-head">
                            <span class="eg-reboot-dimension-label">{{ $dimension['label'] ?? '' }}</span>
                            <span class="eg-reboot-dimension-pct">{{ eg_num_pct($percent) }}</span>
                        </div>
                        <div @class([
                            'eg-reboot-dimension-track',
                            'is-positive' => $isReadiness,
                        ])>
                            <div
                                class="eg-reboot-dimension-fill"
                                style="width: {{ $percent }}%"
                            ></div>
                        </div>
                        @if (! empty($dimension['description']))
                            <p class="eg-reboot-dimension-desc small mb-0">{{ $dimension['description'] }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
