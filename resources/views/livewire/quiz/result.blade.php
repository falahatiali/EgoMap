@php
    use App\Support\LocaleConfig;

    $typeCode = $report['type_code'] ?? '—';
    $quizLocale = LocaleConfig::resolve(request()->route('locale', app()->getLocale()));
@endphp

<div
    class="eg-result-page"
    style="--eg-result-accent: {{ $palette['accent'] }}; --eg-result-soft: {{ $palette['soft'] }}; --eg-result-glow: {{ $palette['glow'] }};"
>
    @php
        $resultNavLinks = [
            [
                'href' => route('home'),
                'label' => __('quiz.back_home'),
                'icon' => 'fa-house',
                'primary' => true,
            ],
            [
                'href' => route('home').'#tests',
                'label' => __('profile.browse_tests'),
                'icon' => 'fa-flask',
            ],
        ];

        if (auth()->check()) {
            array_unshift($resultNavLinks, [
                'href' => route('profile'),
                'label' => __('profile.page_title'),
                'icon' => 'fa-user',
                'wireNavigate' => true,
            ]);
        }
    @endphp

    <section class="container eg-result-shell-bar">
        @include('partials.page-nav-actions', [
            'variant' => 'light',
            'links' => $resultNavLinks,
        ])
    </section>

    <div class="eg-result-hero">
        <div class="container">
            <div class="eg-result-hero-inner">
                <p class="eg-result-eyebrow">{{ $content['hero_label'] ?? __('quiz.your_result') }}</p>
                <div class="eg-result-type-badge">
                    @if (($report['template'] ?? '') === 'reboot_protocol')
                        {{ $content['type_label'] ?? ($report['title'] ?? $typeCode) }}
                    @else
                        {{ $typeCode }}
                    @endif
                </div>
                <h1 class="eg-result-title">{{ $content['archetype'] ?? ($report['title'] ?? '') }}</h1>
                <p class="eg-result-summary">{{ $content['tagline'] ?? ($report['summary'] ?? '') }}</p>
                @if (! empty($content['mantra']))
                    <p class="eg-result-mantra">{{ $content['mantra'] }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="container eg-result-content">
        @if (($report['template'] ?? '') === 'reboot_protocol')
            @include('partials.quiz-result-reboot-protocol', [
                'report' => $report,
                'content' => $content,
                'quizLocale' => $quizLocale,
            ])
            @include('partials.quiz-result-reboot-dimensions', [
                'report' => $report,
                'theme' => 'light',
            ])
        @else
            @include('partials.quiz-result-details', [
                'report' => $report,
                'content' => $content,
                'theme' => 'light',
            ])
        @endif

        <section class="eg-result-panel eg-result-email-panel">
            @if ($emailSent)
                <div class="eg-result-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <div>
                        <h3 class="h5 mb-1">{{ __('quiz.email_sent_title') }}</h3>
                        <p class="mb-0">{{ __('quiz.email_sent', ['email' => $email]) }}</p>
                    </div>
                </div>
            @else
                <div class="row align-items-center g-4">
                    <div class="col-lg-7">
                        <h2 class="eg-result-panel-title mb-2">{{ __('quiz.full_report_title') }}</h2>
                        <p class="eg-result-body-text mb-0">{{ __('quiz.full_report_description') }}</p>
                    </div>
                    <div class="col-lg-5">
                        <form wire:submit="sendFullReport" class="eg-result-email-form">
                            <label for="quiz-email" class="form-label">{{ __('quiz.email_label') }}</label>
                            <input
                                id="quiz-email"
                                type="email"
                                wire:model="email"
                                class="form-control form-control-lg @error('email') is-invalid @enderror"
                                placeholder="{{ __('quiz.email_placeholder') }}"
                                autocomplete="email"
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <button type="submit" class="btn eg-result-submit-btn w-100 mt-3" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="sendFullReport">{{ __('quiz.send_full_report') }}</span>
                                <span wire:loading wire:target="sendFullReport">
                                    <i class="fa-solid fa-spinner fa-spin me-1"></i>
                                    {{ __('quiz.sending') }}
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </section>

        @guest
            <section class="eg-result-account-cta">
                <h2 class="h5 mb-2">{{ __('auth.create_account_cta_title') }}</h2>
                <p class="eg-result-body-text mb-3">{{ __('auth.create_account_cta_body') }}</p>
                <a href="{{ route('register') }}" class="btn eg-result-submit-btn">
                    {{ __('auth.create_account_cta_button') }}
                </a>
            </section>
        @endguest

        <section class="pb-5">
            @include('partials.page-nav-actions', [
                'variant' => 'light',
                'align' => 'center',
                'links' => $resultNavLinks,
            ])
        </section>
    </div>
</div>
