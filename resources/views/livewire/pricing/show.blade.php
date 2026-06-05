<div class="eg-pricing-page" id="pricing">
    <section class="container pt-3">
        @include('partials.page-nav-actions', [
            'links' => [
                [
                    'href' => route('home', ['locale' => $locale]),
                    'label' => __('quiz.back_home'),
                    'icon' => 'fa-house',
                ],
            ],
        ])
    </section>

    <section class="eg-pricing-hero">
        <div class="container text-center">
            <h1 class="eg-display h2 mb-3">{{ __('pricing.hero_title') }}</h1>
            <p class="eg-text-muted eg-pricing-hero-lead mb-0">{{ __('pricing.hero_subtitle') }}</p>
        </div>
    </section>

    @if ($checkoutSuccess)
        <section class="container pb-3">
            <div class="eg-pricing-alert eg-pricing-alert--success" role="status">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                <div>
                    <strong>{{ __('pricing.checkout_success_title') }}</strong>
                    <p class="mb-0">{{ __('pricing.checkout_success_body') }}</p>
                </div>
            </div>
        </section>
    @endif

    @if ($checkoutCancelled)
        <section class="container pb-3">
            <div class="eg-pricing-alert" role="status">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <p class="mb-0">{{ __('pricing.checkout_cancelled') }}</p>
            </div>
        </section>
    @endif

    @if ($coupon)
        <section class="container pb-3">
            <div class="eg-pricing-alert eg-pricing-alert--coupon" role="status">
                <i class="fa-solid fa-tag" aria-hidden="true"></i>
                <p class="mb-0">{{ __('pricing.coupon_applied', ['code' => $coupon]) }}</p>
            </div>
        </section>
    @endif

    @if ($hasActiveSubscription)
        <section class="container pb-3">
            <div class="eg-pricing-alert eg-pricing-alert--success" role="status">
                <i class="fa-solid fa-crown" aria-hidden="true"></i>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span>{{ __('pricing.already_subscribed') }}</span>
                    <a href="{{ route('profile', ['locale' => $locale]) }}" class="btn btn-sm eg-btn-ghost" wire:navigate>
                        {{ __('pricing.already_pro_cta') }}
                    </a>
                </div>
            </div>
        </section>
    @endif

    <section class="container pb-5">
        <div class="eg-pricing-layout">
            <article class="eg-pricing-card eg-pricing-card--free">
                <span class="eg-pricing-tier">{{ __('pricing.free_title') }}</span>
                <div class="eg-pricing-price">{{ __('pricing.free_price') }}</div>
                <p class="eg-pricing-interval">{{ __('pricing.free_interval') }}</p>
                <ul class="eg-pricing-list eg-pricing-list--compact">
                    @foreach (range(1, 5) as $i)
                        <li>
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            <span>{{ __("pricing.free_feature_{$i}") }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ $quizStartUrl }}" class="btn eg-btn-ghost w-100" wire:navigate>
                    {{ __('pricing.free_cta') }}
                </a>
            </article>

            <div class="eg-pricing-pro eg-glass">
                <div class="eg-pricing-pro-header">
                    <span class="eg-pricing-tier">{{ __('pricing.pro_title') }}</span>
                    <p class="eg-pricing-pro-desc mb-0">{{ $proDescription }}</p>
                </div>

                <ul class="eg-pricing-list eg-pricing-pro-features">
                    @foreach (range(1, 7) as $i)
                        <li>
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            <span>{{ __("pricing.pro_feature_{$i}") }}</span>
                        </li>
                    @endforeach
                </ul>

                @if ($plans->isEmpty())
                    <p class="eg-pricing-desc text-center mb-4">{{ __('pricing.empty_plans') }}</p>
                    <a href="{{ $quizStartUrl }}" class="btn eg-btn-primary" wire:navigate>
                        {{ __('pricing.free_cta') }}
                    </a>
                @else
                    <div class="eg-pro-tiers">
                        @foreach ($plans as $plan)
                            <article @class([
                                'eg-pro-tier',
                                'eg-pro-tier--featured' => $plan->isQuarterly(),
                                'eg-pro-tier--value' => $plan->isYearly(),
                            ])>
                                <div class="eg-pro-tier__badge-slot">
                                    @if ($plan->isQuarterly())
                                        <span class="eg-pricing-badge">{{ __('pricing.pro_badge') }}</span>
                                    @elseif ($plan->isYearly() && $yearlySavingsPercent)
                                        <span class="eg-pricing-badge">{{ __('pricing.yearly_badge') }}</span>
                                    @endif
                                </div>

                                <h3 class="eg-pro-tier__name">{{ $plan->billingPeriodName($locale) }}</h3>

                                @if ($plan->formattedPrice($locale))
                                    <div class="eg-pro-tier__price">{{ eg_num($plan->formattedPrice($locale), $locale) }}</div>
                                @endif

                                <p class="eg-pro-tier__cadence">{{ eg_num($plan->billingCadenceLabel($locale), $locale) }}</p>

                                <p class="eg-pro-tier__savings">
                                    @if ($plan->isYearly() && $yearlySavingsPercent)
                                        {{ __('pricing.save_percent', ['percent' => eg_num($yearlySavingsPercent, $locale)]) }}
                                    @endif
                                </p>

                                @auth
                                    @if ($hasActiveSubscription)
                                        <button type="button" class="btn eg-btn-primary w-100" disabled>
                                            {{ __('pricing.already_subscribed') }}
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            class="btn eg-btn-primary w-100"
                                            wire:click="subscribe({{ $plan->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="subscribe"
                                        >
                                            <span wire:loading.remove wire:target="subscribe">{{ __('pricing.pro_cta') }}</span>
                                            <span wire:loading wire:target="subscribe">…</span>
                                        </button>
                                    @endif
                                @else
                                    <button
                                        type="button"
                                        class="btn eg-btn-primary w-100"
                                        wire:click="subscribe({{ $plan->id }})"
                                    >
                                        {{ __('pricing.pro_cta_login') }}
                                    </button>
                                @endauth
                            </article>
                        @endforeach
                    </div>
                @endif

                <p class="eg-pricing-stripe-note text-center mb-0">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    {{ __('pricing.secure_checkout') }}
                </p>
            </div>
        </div>
    </section>

    <section class="container pb-5">
        <h2 class="eg-display h4 text-center mb-4">{{ __('pricing.faq_title') }}</h2>
        <div class="eg-pricing-faq">
            @foreach (range(1, 3) as $i)
                <details class="eg-pricing-faq-item eg-glass">
                    <summary>{{ __("pricing.faq_{$i}_q") }}</summary>
                    <p class="eg-text-muted mb-0">{{ __("pricing.faq_{$i}_a") }}</p>
                </details>
            @endforeach
        </div>
    </section>
</div>
